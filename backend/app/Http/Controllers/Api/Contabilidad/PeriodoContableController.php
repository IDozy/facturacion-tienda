<?php

namespace App\Http\Controllers\Api\Contabilidad;

use App\Http\Controllers\Controller;
use App\Models\Contabilidad\PeriodoContable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PeriodoContableController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = PeriodoContable::with(['empresa', 'cerradoPor']);

        // Filtros
        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->has('año')) {
            $query->delAño($request->año);
        }

        if ($request->has('mes')) {
            $query->where('mes', $request->mes);
        }

        if ($request->has('empresa_id')) {
            $query->where('empresa_id', $request->empresa_id);
        }

        // Ordenamiento
        $sortBy = $request->get('sort_by', 'año');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);
        
        if ($sortBy !== 'mes') {
            $query->orderBy('mes', $sortOrder);
        }

        // Paginación
        $perPage = $request->get('per_page', 15);
        
        if ($request->get('all') === 'true') {
            $periodos = $query->get();
            return response()->json(['data' => $periodos]);
        }

        $periodos = $query->paginate($perPage);

        return response()->json($periodos);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $empresaId = Auth::user()->empresa_id;

        $validator = Validator::make($request->all(), [
            'mes' => 'required|integer|min:1|max:12',
            'año' => 'required|integer|min:2000|max:2100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        // Verificar si ya existe
        $existe = PeriodoContable::where('empresa_id', $empresaId)
            ->where('mes', $request->mes)
            ->where('año', $request->año)
            ->exists();

        if ($existe) {
            return response()->json([
                'message' => 'El período contable ya existe'
            ], 400);
        }

        try {
            $periodo = PeriodoContable::create([
                'empresa_id' => $empresaId,
                'mes' => $request->mes,
                'año' => $request->año,
            ]);

            return response()->json([
                'message' => 'Período contable creado exitosamente',
                'data' => $periodo->load(['empresa'])
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear el período contable',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(PeriodoContable $periodoContable)
    {
        return response()->json([
            'data' => $periodoContable->load(['empresa', 'cerradoPor']),
            'nombre' => $periodoContable->nombre,
            'rango' => $periodoContable->rango,
            'esta_abierto' => $periodoContable->estaAbierto(),
            'esta_cerrado' => $periodoContable->estaCerrado(),
            'es_actual' => $periodoContable->esActual(),
            'cantidad_asientos' => $periodoContable->cantidadAsientos()
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PeriodoContable $periodoContable)
    {
        if ($periodoContable->estaCerrado()) {
            return response()->json([
                'message' => 'No se puede modificar un período cerrado'
            ], 400);
        }

        $empresaId = Auth::user()->empresa_id;

        $validator = Validator::make($request->all(), [
            'mes' => 'sometimes|required|integer|min:1|max:12',
            'año' => 'sometimes|required|integer|min:2000|max:2100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        // Verificar duplicados si se cambia mes o año
        if ($request->has('mes') || $request->has('año')) {
            $existe = PeriodoContable::where('empresa_id', $empresaId)
                ->where('mes', $request->get('mes', $periodoContable->mes))
                ->where('año', $request->get('año', $periodoContable->año))
                ->where('id', '!=', $periodoContable->id)
                ->exists();

            if ($existe) {
                return response()->json([
                    'message' => 'Ya existe un período con ese mes y año'
                ], 400);
            }
        }

        try {
            $periodoContable->update($request->only(['mes', 'año']));

            return response()->json([
                'message' => 'Período contable actualizado exitosamente',
                'data' => $periodoContable->fresh(['empresa'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar el período contable',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PeriodoContable $periodoContable)
    {
        if ($periodoContable->estaCerrado()) {
            return response()->json([
                'message' => 'No se puede eliminar un período cerrado'
            ], 400);
        }

        if ($periodoContable->asientos()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar un período con asientos registrados',
                'cantidad_asientos' => $periodoContable->asientos()->count()
            ], 400);
        }

        try {
            $periodoContable->delete();

            return response()->json([
                'message' => 'Período contable eliminado exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar el período contable',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cerrar período
     */
    public function cerrar(PeriodoContable $periodoContable)
    {
        if ($periodoContable->estaCerrado()) {
            return response()->json([
                'message' => 'El período ya está cerrado'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $periodoContable->cerrar();

            DB::commit();

            return response()->json([
                'message' => 'Período cerrado exitosamente',
                'data' => $periodoContable->fresh(['cerradoPor'])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al cerrar el período',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reabrir período
     */
    public function reabrir(PeriodoContable $periodoContable)
    {
        if ($periodoContable->estaAbierto()) {
            return response()->json([
                'message' => 'El período ya está abierto'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $periodoContable->reabrir();

            DB::commit();

            return response()->json([
                'message' => 'Período reabierto exitosamente',
                'data' => $periodoContable->fresh()
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al reabrir el período',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener período actual
     */
    public function actual()
    {
        $periodo = PeriodoContable::actual()
            ->where('empresa_id', Auth::user()->empresa_id)
            ->first();

        if (!$periodo) {
            return response()->json([
                'message' => 'No existe un período activo para la fecha actual'
            ], 404);
        }

        return response()->json([
            'data' => $periodo,
            'nombre' => $periodo->nombre,
            'es_actual' => true
        ]);
    }

    /**
     * Períodos abiertos
     */
    public function abiertos()
    {
        $periodos = PeriodoContable::abiertos()
            ->where('empresa_id', Auth::user()->empresa_id)
            ->orderBy('año', 'desc')
            ->orderBy('mes', 'desc')
            ->get();

        return response()->json([
            'data' => $periodos,
            'count' => $periodos->count()
        ]);
    }

    /**
     * Períodos cerrados
     */
    public function cerrados()
    {
        $periodos = PeriodoContable::cerrados()
            ->where('empresa_id', Auth::user()->empresa_id)
            ->with(['cerradoPor'])
            ->orderBy('año', 'desc')
            ->orderBy('mes', 'desc')
            ->get();

        return response()->json([
            'data' => $periodos,
            'count' => $periodos->count()
        ]);
    }

    /**
     * Períodos del año
     */
    public function delAño($año)
    {
        $periodos = PeriodoContable::delAño($año)
            ->where('empresa_id', Auth::user()->empresa_id)
            ->orderBy('mes')
            ->get();

        return response()->json([
            'data' => $periodos,
            'año' => $año,
            'count' => $periodos->count()
        ]);
    }

    /**
     * Generar libros electrónicos
     */
    public function generarLibros(PeriodoContable $periodoContable)
    {
        if ($periodoContable->estaAbierto()) {
            return response()->json([
                'message' => 'Solo se pueden generar libros de períodos cerrados'
            ], 400);
        }

        try {
            $periodoContable->generarLibrosElectronicos();

            return response()->json([
                'message' => 'Libros electrónicos generados exitosamente',
                'libros' => $periodoContable->librosElectronicos
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al generar los libros electrónicos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Asientos del período
     */
    public function asientos(PeriodoContable $periodoContable, Request $request)
    {
        $query = $periodoContable->asientos()->with(['usuario']);

        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        $asientos = $query->orderBy('fecha_asiento', 'desc')
            ->paginate($request->get('per_page', 50));

        return response()->json($asientos);
    }

    /**
     * Estadísticas del período
     */
    public function estadisticas(PeriodoContable $periodoContable)
    {
        $totalAsientos = $periodoContable->asientos()->count();
        $asientosBorrador = $periodoContable->asientos()->where('estado', 'borrador')->count();
        $asientosRegistrados = $periodoContable->asientos()->where('estado', 'registrado')->count();

        $librosGenerados = $periodoContable->librosElectronicos()->count();
        $librosEnviados = $periodoContable->librosElectronicos()
            ->where('estado', 'enviado_sunat')
            ->count();

        return response()->json([
            'total_asientos' => $totalAsientos,
            'asientos_borrador' => $asientosBorrador,
            'asientos_registrados' => $asientosRegistrados,
            'libros_generados' => $librosGenerados,
            'libros_enviados' => $librosEnviados,
            'periodo' => [
                'nombre' => $periodoContable->nombre,
                'rango' => $periodoContable->rango,
                'estado' => $periodoContable->estado,
                'es_actual' => $periodoContable->esActual()
            ]
        ]);
    }

    /**
     * Crear múltiples períodos
     */
    public function crearMultiples(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'año' => 'required|integer|min:2000|max:2100',
            'meses' => 'required|array|min:1',
            'meses.*' => 'integer|min:1|max:12',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $creados = 0;
            $errores = [];
            $empresaId = Auth::user()->empresa_id;

            foreach ($request->meses as $mes) {
                try {
                    // Verificar si ya existe
                    $existe = PeriodoContable::where('empresa_id', $empresaId)
                        ->where('mes', $mes)
                        ->where('año', $request->año)
                        ->exists();

                    if ($existe) {
                        $errores[] = [
                            'mes' => $mes,
                            'error' => 'El período ya existe'
                        ];
                        continue;
                    }

                    PeriodoContable::create([
                        'empresa_id' => $empresaId,
                        'mes' => $mes,
                        'año' => $request->año,
                    ]);

                    $creados++;
                } catch (\Exception $e) {
                    $errores[] = [
                        'mes' => $mes,
                        'error' => $e->getMessage()
                    ];
                }
            }

            DB::commit();

            return response()->json([
                'message' => "Se crearon {$creados} período(s)",
                'creados' => $creados,
                'errores' => $errores
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al crear los períodos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear períodos del año completo
     */
    public function crearAñoCompleto(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'año' => 'required|integer|min:2000|max:2100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $meses = range(1, 12);
        
        return $this->crearMultiples(new Request([
            'año' => $request->año,
            'meses' => $meses
        ]));
    }

    /**
     * Verificar si puede cerrar
     */
    public function verificarCierre(PeriodoContable $periodoContable)
    {
        $asientosBorrador = $periodoContable->asientos()
            ->where('estado', 'borrador')
            ->count();

        $puedeCerrar = $asientosBorrador === 0;

        return response()->json([
            'puede_cerrar' => $puedeCerrar,
            'asientos_borrador' => $asientosBorrador,
            'mensaje' => $puedeCerrar 
                ? 'El período puede ser cerrado' 
                : "Existen {$asientosBorrador} asiento(s) en borrador"
        ]);
    }

    /**
     * Verificar si puede reabrir
     */
    public function verificarReapertura(PeriodoContable $periodoContable)
    {
        $posterioresCerrados = PeriodoContable::where('empresa_id', $periodoContable->empresa_id)
            ->where(function ($q) use ($periodoContable) {
                $q->where('año', '>', $periodoContable->año)
                    ->orWhere(function ($q2) use ($periodoContable) {
                        $q2->where('año', $periodoContable->año)
                            ->where('mes', '>', $periodoContable->mes);
                    });
            })
            ->where('estado', 'cerrado')
            ->count();

        $puedeReabrir = $posterioresCerrados === 0;

        return response()->json([
            'puede_reabrir' => $puedeReabrir,
            'periodos_posteriores_cerrados' => $posterioresCerrados,
            'mensaje' => $puedeReabrir 
                ? 'El período puede ser reabierto' 
                : "Existen {$posterioresCerrados} período(s) posterior(es) cerrado(s)"
        ]);
    }

    /**
     * Resumen general
     */
    public function resumen(Request $request)
    {
        $query = PeriodoContable::where('empresa_id', Auth::user()->empresa_id);

        if ($request->has('año')) {
            $query->delAño($request->año);
        }

        $total = (clone $query)->count();
        $abiertos = (clone $query)->abiertos()->count();
        $cerrados = (clone $query)->cerrados()->count();

        $porAño = (clone $query)->select('año')
            ->selectRaw('COUNT(*) as cantidad')
            ->selectRaw('SUM(CASE WHEN estado = "abierto" THEN 1 ELSE 0 END) as abiertos')
            ->selectRaw('SUM(CASE WHEN estado = "cerrado" THEN 1 ELSE 0 END) as cerrados')
            ->groupBy('año')
            ->orderBy('año', 'desc')
            ->get();

        return response()->json([
            'total_periodos' => $total,
            'abiertos' => $abiertos,
            'cerrados' => $cerrados,
            'por_año' => $porAño
        ]);
    }
}