<?php

namespace App\Http\Controllers\Api\Contabilidad;

use App\Http\Controllers\Controller;
use App\Models\Contabilidad\PlanCuenta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PlanCuentaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = PlanCuenta::with(['padre', 'empresa']);

        // Filtros
        if ($request->has('tipo')) {
            $query->porTipo($request->tipo);
        }

        if ($request->has('nivel')) {
            $query->where('nivel', $request->nivel);
        }

        if ($request->has('padre_id')) {
            if ($request->padre_id === 'null') {
                $query->raices();
            } else {
                $query->where('padre_id', $request->padre_id);
            }
        }

        if ($request->has('es_auxiliar')) {
            $query->where('es_auxiliar', $request->es_auxiliar);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('codigo', 'like', "%{$search}%")
                    ->orWhere('nombre', 'like', "%{$search}%");
            });
        }

        // Ordenamiento
        $sortBy = $request->get('sort_by', 'codigo');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginación
        $perPage = $request->get('per_page', 50);
        
        if ($request->get('all') === 'true') {
            $cuentas = $query->get();
            return response()->json(['data' => $cuentas]);
        }

        $cuentas = $query->paginate($perPage);

        return response()->json($cuentas);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $empresaId = Auth::user()->empresa_id;

        $validator = Validator::make($request->all(), [
            'codigo' => [
                'required',
                'string',
                'max:20',
                Rule::unique('plan_cuentas')->where(function ($query) use ($empresaId) {
                    return $query->where('empresa_id', $empresaId);
                })
            ],
            'nombre' => 'required|string|max:255',
            'tipo' => 'required|in:activo,pasivo,patrimonio,ingreso,gasto',
            'padre_id' => 'nullable|exists:plan_cuentas,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $cuenta = PlanCuenta::create($request->all());

            return response()->json([
                'message' => 'Cuenta creada exitosamente',
                'data' => $cuenta->load(['padre'])
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear la cuenta',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(PlanCuenta $planCuenta)
    {
        return response()->json([
            'data' => $planCuenta->load(['padre', 'hijos']),
            'es_hoja' => $planCuenta->esHoja(),
            'tiene_movimientos' => $planCuenta->tieneMovimientos(),
            'puede_eliminar' => $planCuenta->puedeEliminar(),
            'nombre_completo' => $planCuenta->nombre_completo,
            'codigo_completo' => $planCuenta->codigo_completo,
            'ruta' => $planCuenta->obtenerRuta()
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PlanCuenta $planCuenta)
    {
        $empresaId = Auth::user()->empresa_id;

        $validator = Validator::make($request->all(), [
            'codigo' => [
                'sometimes',
                'required',
                'string',
                'max:20',
                Rule::unique('plan_cuentas')->ignore($planCuenta->id)->where(function ($query) use ($empresaId) {
                    return $query->where('empresa_id', $empresaId);
                })
            ],
            'nombre' => 'sometimes|required|string|max:255',
            'tipo' => 'sometimes|required|in:activo,pasivo,patrimonio,ingreso,gasto',
            'padre_id' => 'nullable|exists:plan_cuentas,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        // Validar que el padre no cree un ciclo
        if ($request->has('padre_id') && $request->padre_id) {
            if (!$planCuenta->validarPadre($request->padre_id)) {
                return response()->json([
                    'message' => 'No se puede asignar este padre porque crearía una referencia circular'
                ], 400);
            }
        }

        try {
            $planCuenta->update($request->all());

            // Limpiar caché de saldo
            $this->limpiarCacheSaldo($planCuenta->id);

            return response()->json([
                'message' => 'Cuenta actualizada exitosamente',
                'data' => $planCuenta->fresh(['padre'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar la cuenta',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PlanCuenta $planCuenta)
    {
        if (!$planCuenta->puedeEliminar()) {
            return response()->json([
                'message' => 'No se puede eliminar esta cuenta porque tiene cuentas hijas o movimientos asociados',
                'tiene_hijos' => !$planCuenta->esHoja(),
                'tiene_movimientos' => $planCuenta->tieneMovimientos()
            ], 400);
        }

        try {
            $planCuenta->delete();

            return response()->json([
                'message' => 'Cuenta eliminada exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar la cuenta',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener árbol jerárquico
     */
    public function arbol(Request $request)
    {
        $query = PlanCuenta::raices();

        if ($request->has('tipo')) {
            $query->porTipo($request->tipo);
        }

        $raices = $query->orderBy('codigo')->get();

        $construirArbol = function ($cuentas) use (&$construirArbol) {
            return $cuentas->map(function ($cuenta) use ($construirArbol) {
                return [
                    'id' => $cuenta->id,
                    'codigo' => $cuenta->codigo,
                    'nombre' => $cuenta->nombre,
                    'tipo' => $cuenta->tipo,
                    'nivel' => $cuenta->nivel,
                    'es_auxiliar' => $cuenta->es_auxiliar,
                    'es_hoja' => $cuenta->esHoja(),
                    'hijos' => $construirArbol($cuenta->hijos()->orderBy('codigo')->get())
                ];
            });
        };

        $arbol = $construirArbol($raices);

        return response()->json([
            'data' => $arbol
        ]);
    }

    /**
     * Obtener cuentas hijas
     */
    public function hijos($planCuentaId)
    {
        $cuenta = PlanCuenta::findOrFail($planCuentaId);
        $hijos = $cuenta->hijos()->orderBy('codigo')->get();

        return response()->json([
            'data' => $hijos,
            'count' => $hijos->count()
        ]);
    }

    /**
     * Obtener ruta jerárquica
     */
    public function ruta(PlanCuenta $planCuenta)
    {
        $ruta = $planCuenta->obtenerRuta();

        return response()->json([
            'data' => $ruta,
            'nombre_completo' => $planCuenta->nombre_completo,
            'codigo_completo' => $planCuenta->codigo_completo
        ]);
    }

    /**
     * Calcular saldo de una cuenta
     */
    public function saldo(Request $request, PlanCuenta $planCuenta)
    {
        $periodoId = $request->get('periodo_id');
        $saldo = $planCuenta->saldo($periodoId);

        return response()->json([
            'cuenta_id' => $planCuenta->id,
            'codigo' => $planCuenta->codigo,
            'nombre' => $planCuenta->nombre,
            'tipo' => $planCuenta->tipo,
            'saldo' => $saldo,
            'periodo_id' => $periodoId
        ]);
    }

    /**
     * Obtener balance general
     */
    public function balanceGeneral(Request $request)
    {
        $periodoId = $request->get('periodo_id');

        $activos = PlanCuenta::activos()
            ->auxiliares()
            ->orderBy('codigo')
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'codigo' => $c->codigo,
                'nombre' => $c->nombre,
                'saldo' => $c->saldo($periodoId)
            ])
            ->filter(fn($c) => abs($c['saldo']) > 0.01);

        $pasivos = PlanCuenta::pasivos()
            ->auxiliares()
            ->orderBy('codigo')
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'codigo' => $c->codigo,
                'nombre' => $c->nombre,
                'saldo' => $c->saldo($periodoId)
            ])
            ->filter(fn($c) => abs($c['saldo']) > 0.01);

        $patrimonio = PlanCuenta::patrimonio()
            ->auxiliares()
            ->orderBy('codigo')
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'codigo' => $c->codigo,
                'nombre' => $c->nombre,
                'saldo' => $c->saldo($periodoId)
            ])
            ->filter(fn($c) => abs($c['saldo']) > 0.01);

        $totalActivos = $activos->sum('saldo');
        $totalPasivos = $pasivos->sum('saldo');
        $totalPatrimonio = $patrimonio->sum('saldo');

        return response()->json([
            'activos' => $activos,
            'pasivos' => $pasivos,
            'patrimonio' => $patrimonio,
            'totales' => [
                'activos' => $totalActivos,
                'pasivos' => $totalPasivos,
                'patrimonio' => $totalPatrimonio,
                'diferencia' => $totalActivos - ($totalPasivos + $totalPatrimonio)
            ]
        ]);
    }

    /**
     * Obtener estado de resultados
     */
    public function estadoResultados(Request $request)
    {
        $periodoId = $request->get('periodo_id');

        $ingresos = PlanCuenta::ingresos()
            ->auxiliares()
            ->orderBy('codigo')
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'codigo' => $c->codigo,
                'nombre' => $c->nombre,
                'saldo' => $c->saldo($periodoId)
            ])
            ->filter(fn($c) => abs($c['saldo']) > 0.01);

        $gastos = PlanCuenta::gastos()
            ->auxiliares()
            ->orderBy('codigo')
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'codigo' => $c->codigo,
                'nombre' => $c->nombre,
                'saldo' => $c->saldo($periodoId)
            ])
            ->filter(fn($c) => abs($c['saldo']) > 0.01);

        $totalIngresos = $ingresos->sum('saldo');
        $totalGastos = $gastos->sum('saldo');
        $utilidadNeta = $totalIngresos - $totalGastos;

        return response()->json([
            'ingresos' => $ingresos,
            'gastos' => $gastos,
            'totales' => [
                'ingresos' => $totalIngresos,
                'gastos' => $totalGastos,
                'utilidad_neta' => $utilidadNeta
            ]
        ]);
    }

    /**
     * Cuentas por tipo
     */
    public function porTipo($tipo)
    {
        if (!in_array($tipo, ['activo', 'pasivo', 'patrimonio', 'ingreso', 'gasto'])) {
            return response()->json([
                'message' => 'Tipo de cuenta no válido'
            ], 400);
        }

        $cuentas = PlanCuenta::porTipo($tipo)
            ->orderBy('codigo')
            ->get();

        return response()->json([
            'data' => $cuentas,
            'tipo' => $tipo,
            'count' => $cuentas->count()
        ]);
    }

    /**
     * Cuentas auxiliares
     */
    public function auxiliares(Request $request)
    {
        $query = PlanCuenta::auxiliares();

        if ($request->has('tipo')) {
            $query->porTipo($request->tipo);
        }

        $cuentas = $query->orderBy('codigo')->get();

        return response()->json([
            'data' => $cuentas,
            'count' => $cuentas->count()
        ]);
    }

    /**
     * Estadísticas del plan de cuentas
     */
    public function estadisticas()
    {
        $total = PlanCuenta::count();
        $auxiliares = PlanCuenta::auxiliares()->count();

        $porTipo = PlanCuenta::select('tipo')
            ->selectRaw('COUNT(*) as cantidad')
            ->groupBy('tipo')
            ->get();

        $porNivel = PlanCuenta::select('nivel')
            ->selectRaw('COUNT(*) as cantidad')
            ->groupBy('nivel')
            ->orderBy('nivel')
            ->get();

        $raices = PlanCuenta::raices()->count();

        return response()->json([
            'total_cuentas' => $total,
            'cuentas_auxiliares' => $auxiliares,
            'cuentas_raiz' => $raices,
            'por_tipo' => $porTipo,
            'por_nivel' => $porNivel
        ]);
    }

    /**
     * Importar plan de cuentas
     */
    public function importar(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cuentas' => 'required|array',
            'cuentas.*.codigo' => 'required|string|max:20',
            'cuentas.*.nombre' => 'required|string|max:255',
            'cuentas.*.tipo' => 'required|in:activo,pasivo,patrimonio,ingreso,gasto',
            'cuentas.*.codigo_padre' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $importadas = 0;
            $errores = [];
            $empresaId = Auth::user()->empresa_id;

            // Ordenar por nivel (primero las raíces, luego las hijas)
            $cuentasOrdenadas = collect($request->cuentas)->sortBy(function ($cuenta) {
                return strlen($cuenta['codigo']);
            });

            foreach ($cuentasOrdenadas as $index => $cuentaData) {
                try {
                    // Buscar cuenta padre si existe
                    $padreId = null;
                    if (!empty($cuentaData['codigo_padre'])) {
                        $padre = PlanCuenta::where('empresa_id', $empresaId)
                            ->where('codigo', $cuentaData['codigo_padre'])
                            ->first();
                        $padreId = $padre?->id;
                    }

                    // Verificar si ya existe
                    $existe = PlanCuenta::where('empresa_id', $empresaId)
                        ->where('codigo', $cuentaData['codigo'])
                        ->exists();

                    if ($existe) {
                        $errores[] = [
                            'index' => $index,
                            'codigo' => $cuentaData['codigo'],
                            'error' => 'La cuenta ya existe'
                        ];
                        continue;
                    }

                    PlanCuenta::create([
                        'codigo' => $cuentaData['codigo'],
                        'nombre' => $cuentaData['nombre'],
                        'tipo' => $cuentaData['tipo'],
                        'padre_id' => $padreId,
                        'empresa_id' => $empresaId,
                    ]);

                    $importadas++;
                } catch (\Exception $e) {
                    $errores[] = [
                        'index' => $index,
                        'codigo' => $cuentaData['codigo'] ?? 'N/A',
                        'error' => $e->getMessage()
                    ];
                }
            }

            DB::commit();

            return response()->json([
                'message' => "Se importaron {$importadas} cuentas",
                'importadas' => $importadas,
                'errores' => $errores
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al importar el plan de cuentas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validar padre
     */
    public function validarPadre(Request $request, PlanCuenta $planCuenta)
    {
        $validator = Validator::make($request->all(), [
            'padre_id' => 'required|exists:plan_cuentas,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $esValido = $planCuenta->validarPadre($request->padre_id);

        return response()->json([
            'valido' => $esValido,
            'mensaje' => $esValido 
                ? 'El padre es válido' 
                : 'Este padre crearía una referencia circular'
        ]);
    }

    /**
     * Limpiar caché de saldo
     */
    private function limpiarCacheSaldo($cuentaId)
    {
        Cache::forget("cuenta_{$cuentaId}_saldo");
        // También limpiar con periodo
        for ($i = 1; $i <= 100; $i++) {
            Cache::forget("cuenta_{$cuentaId}_saldo_p{$i}");
        }
    }

    /**
     * Tipos de cuenta disponibles
     */
    public function tipos()
    {
        $tipos = [
            ['value' => 'activo', 'label' => 'Activo'],
            ['value' => 'pasivo', 'label' => 'Pasivo'],
            ['value' => 'patrimonio', 'label' => 'Patrimonio'],
            ['value' => 'ingreso', 'label' => 'Ingreso'],
            ['value' => 'gasto', 'label' => 'Gasto'],
        ];

        return response()->json([
            'data' => $tipos
        ]);
    }
}