<?php

namespace App\Http\Controllers\Api\Contabilidad;

use App\Http\Controllers\Controller;
use App\Models\Contabilidad\Diario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class DiarioController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Diario::with(['empresa'])
            ->withCount('asientos');

        // Filtros
        if ($request->has('activo')) {
            $query->where('activo', $request->activo);
        }

        if ($request->has('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->has('codigo')) {
            $query->porCodigo($request->codigo);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('codigo', 'like', "%{$search}%")
                    ->orWhere('nombre', 'like', "%{$search}%")
                    ->orWhere('descripcion', 'like', "%{$search}%");
            });
        }

        // Ordenamiento
        $sortBy = $request->get('sort_by', 'codigo');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginación
        $perPage = $request->get('per_page', 15);
        
        if ($request->get('all') === 'true') {
            $diarios = $query->activos()->get();
            return response()->json(['data' => $diarios]);
        }

        $diarios = $query->paginate($perPage);

        return response()->json($diarios);
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
                'max:10',
                Rule::unique('diarios')->where(function ($query) use ($empresaId) {
                    return $query->where('empresa_id', $empresaId);
                })
            ],
            'nombre' => 'required|string|max:255',
            'tipo' => 'required|in:manual,automatico',
            'prefijo' => 'nullable|string|max:10',
            'correlativo_actual' => 'nullable|integer|min:0',
            'descripcion' => 'nullable|string',
            'activo' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $diario = Diario::create($request->all());

            return response()->json([
                'message' => 'Diario creado exitosamente',
                'data' => $diario->load(['empresa'])
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear el diario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Diario $diario)
    {
        return response()->json([
            'data' => $diario->load(['empresa']),
            'cantidad_asientos' => $diario->cantidadAsientos(),
            'ultimo_asiento' => $diario->ultimoAsiento(),
            'numero_formateado' => $diario->numeroFormateado(),
            'puede_eliminar' => $diario->puedeEliminar()
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Diario $diario)
    {
        $empresaId = Auth::user()->empresa_id;

        $validator = Validator::make($request->all(), [
            'codigo' => [
                'sometimes',
                'required',
                'string',
                'max:10',
                Rule::unique('diarios')->ignore($diario->id)->where(function ($query) use ($empresaId) {
                    return $query->where('empresa_id', $empresaId);
                })
            ],
            'nombre' => 'sometimes|required|string|max:255',
            'tipo' => 'sometimes|required|in:manual,automatico',
            'prefijo' => 'nullable|string|max:10',
            'descripcion' => 'nullable|string',
            'activo' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $diario->update($request->all());

            return response()->json([
                'message' => 'Diario actualizado exitosamente',
                'data' => $diario->fresh(['empresa'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar el diario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Diario $diario)
    {
        if (!$diario->puedeEliminar()) {
            return response()->json([
                'message' => 'No se puede eliminar un diario con asientos registrados',
                'cantidad_asientos' => $diario->cantidadAsientos()
            ], 400);
        }

        try {
            $diario->delete();

            return response()->json([
                'message' => 'Diario eliminado exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar el diario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle estado del diario
     */
    public function toggleEstado(Diario $diario)
    {
        try {
            if ($diario->estaActivo()) {
                $diario->desactivar();
            } else {
                $diario->activar();
            }

            return response()->json([
                'message' => "Diario " . ($diario->activo ? 'activado' : 'desactivado'),
                'data' => $diario
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al cambiar el estado',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generar siguiente número
     */
    public function generarNumero(Diario $diario)
    {
        if (!$diario->estaActivo()) {
            return response()->json([
                'message' => 'El diario no está activo'
            ], 400);
        }

        try {
            $numero = $diario->generarSiguienteNumero();
            $numeroFormateado = $diario->numeroFormateado($numero);

            return response()->json([
                'numero' => $numero,
                'numero_formateado' => $numeroFormateado,
                'prefijo' => $diario->prefijo
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al generar el número',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener siguiente número sin incrementar
     */
    public function siguienteNumero(Diario $diario)
    {
        $siguienteNumero = $diario->correlativo_actual + 1;
        $numeroFormateado = $diario->numeroFormateado($siguienteNumero);

        return response()->json([
            'siguiente_numero' => $siguienteNumero,
            'numero_formateado' => $numeroFormateado,
            'correlativo_actual' => $diario->correlativo_actual
        ]);
    }

    /**
     * Restablecer correlativo
     */
    public function restablecerCorrelativo(Request $request, Diario $diario)
    {
        $validator = Validator::make($request->all(), [
            'correlativo' => 'required|integer|min:0',
            'confirmacion' => 'required|boolean|accepted',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $diario->update(['correlativo_actual' => $request->correlativo]);

            return response()->json([
                'message' => 'Correlativo restablecido exitosamente',
                'data' => $diario->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al restablecer el correlativo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Asientos del diario
     */
    public function asientos(Diario $diario, Request $request)
    {
        $query = $diario->asientos()->with(['usuario', 'periodoContable']);

        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->has('fecha_desde') && $request->has('fecha_hasta')) {
            $query->whereBetween('fecha_asiento', [$request->fecha_desde, $request->fecha_hasta]);
        }

        $asientos = $query->orderBy('fecha_asiento', 'desc')
            ->paginate($request->get('per_page', 50));

        return response()->json($asientos);
    }

    /**
     * Estadísticas del diario
     */
    public function estadisticas(Diario $diario, Request $request)
    {
        $query = $diario->asientos();

        if ($request->has('fecha_desde') && $request->has('fecha_hasta')) {
            $query->whereBetween('fecha_asiento', [$request->fecha_desde, $request->fecha_hasta]);
        }

        $totalAsientos = (clone $query)->count();
        $asientosBorrador = (clone $query)->where('estado', 'borrador')->count();
        $asientosRegistrados = (clone $query)->where('estado', 'registrado')->count();

        return response()->json([
            'total_asientos' => $totalAsientos,
            'asientos_borrador' => $asientosBorrador,
            'asientos_registrados' => $asientosRegistrados,
            'correlativo_actual' => $diario->correlativo_actual,
            'ultimo_asiento' => $diario->ultimoAsiento()
        ]);
    }

    /**
     * Diarios manuales
     */
    public function manuales()
    {
        $diarios = Diario::manuales()
            ->where('empresa_id', Auth::user()->empresa_id)
            ->activos()
            ->orderBy('codigo')
            ->get();

        return response()->json([
            'data' => $diarios,
            'count' => $diarios->count()
        ]);
    }

    /**
     * Diarios automáticos
     */
    public function automaticos()
    {
        $diarios = Diario::automaticos()
            ->where('empresa_id', Auth::user()->empresa_id)
            ->activos()
            ->orderBy('codigo')
            ->get();

        return response()->json([
            'data' => $diarios,
            'count' => $diarios->count()
        ]);
    }

    /**
     * Estadísticas generales
     */
    public function estadisticasGenerales()
    {
        $query = Diario::where('empresa_id', Auth::user()->empresa_id);

        $total = (clone $query)->count();
        $activos = (clone $query)->activos()->count();
        $inactivos = (clone $query)->where('activo', false)->count();
        $manuales = (clone $query)->manuales()->count();
        $automaticos = (clone $query)->automaticos()->count();

        $masUsados = (clone $query)
            ->withCount('asientos')
            ->orderBy('asientos_count', 'desc')
            ->limit(5)
            ->get();

        return response()->json([
            'total_diarios' => $total,
            'activos' => $activos,
            'inactivos' => $inactivos,
            'manuales' => $manuales,
            'automaticos' => $automaticos,
            'mas_usados' => $masUsados
        ]);
    }

    /**
     * Crear diarios por defecto
     */
    public function crearPorDefecto()
    {
        $empresaId = Auth::user()->empresa_id;

        $diariosDefecto = [
            [
                'codigo' => 'DV',
                'nombre' => 'Diario de Ventas',
                'tipo' => 'automatico',
                'prefijo' => 'DV-',
                'descripcion' => 'Registro automático de ventas',
            ],
            [
                'codigo' => 'DC',
                'nombre' => 'Diario de Compras',
                'tipo' => 'automatico',
                'prefijo' => 'DC-',
                'descripcion' => 'Registro automático de compras',
            ],
            [
                'codigo' => 'DB',
                'nombre' => 'Diario de Bancos',
                'tipo' => 'manual',
                'prefijo' => 'DB-',
                'descripcion' => 'Registro de operaciones bancarias',
            ],
            [
                'codigo' => 'DG',
                'nombre' => 'Diario General',
                'tipo' => 'manual',
                'prefijo' => 'DG-',
                'descripcion' => 'Diario para operaciones generales',
            ],
            [
                'codigo' => 'DA',
                'nombre' => 'Diario de Ajustes',
                'tipo' => 'manual',
                'prefijo' => 'DA-',
                'descripcion' => 'Diario para ajustes contables',
            ],
            [
                'codigo' => 'DC',
                'nombre' => 'Diario de Caja',
                'tipo' => 'automatico',
                'prefijo' => 'DCJ-',
                'descripcion' => 'Registro automático de caja',
            ],
        ];

        DB::beginTransaction();
        try {
            $creados = 0;
            $errores = [];

            foreach ($diariosDefecto as $index => $diarioData) {
                try {
                    // Verificar si ya existe
                    $existe = Diario::where('empresa_id', $empresaId)
                        ->where('codigo', $diarioData['codigo'])
                        ->exists();

                    if ($existe) {
                        $errores[] = [
                            'codigo' => $diarioData['codigo'],
                            'error' => 'El diario ya existe'
                        ];
                        continue;
                    }

                    Diario::create(array_merge($diarioData, [
                        'empresa_id' => $empresaId
                    ]));

                    $creados++;
                } catch (\Exception $e) {
                    $errores[] = [
                        'codigo' => $diarioData['codigo'] ?? 'N/A',
                        'error' => $e->getMessage()
                    ];
                }
            }

            DB::commit();

            return response()->json([
                'message' => "Se crearon {$creados} diario(s) por defecto",
                'creados' => $creados,
                'errores' => $errores
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al crear los diarios por defecto',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Tipos de diario disponibles
     */
    public function tipos()
    {
        $tipos = [
            ['value' => 'manual', 'label' => 'Manual'],
            ['value' => 'automatico', 'label' => 'Automático'],
        ];

        return response()->json([
            'data' => $tipos
        ]);
    }

    /**
     * Validar código único
     */
    public function validarCodigo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'codigo' => 'required|string',
            'diario_id' => 'nullable|exists:diarios,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $query = Diario::where('empresa_id', Auth::user()->empresa_id)
            ->where('codigo', $request->codigo);

        if ($request->has('diario_id')) {
            $query->where('id', '!=', $request->diario_id);
        }

        $existe = $query->exists();

        return response()->json([
            'disponible' => !$existe,
            'mensaje' => $existe 
                ? 'El código ya está en uso' 
                : 'El código está disponible'
        ]);
    }

    /**
     * Exportar diarios
     */
    public function exportar(Request $request)
    {
        $query = Diario::where('empresa_id', Auth::user()->empresa_id)
            ->withCount('asientos');

        if ($request->has('activo')) {
            $query->where('activo', $request->activo);
        }

        if ($request->has('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        $diarios = $query->orderBy('codigo')->get()->map(function ($diario) {
            return [
                'codigo' => $diario->codigo,
                'nombre' => $diario->nombre,
                'tipo' => $diario->tipo,
                'prefijo' => $diario->prefijo,
                'correlativo_actual' => $diario->correlativo_actual,
                'cantidad_asientos' => $diario->asientos_count,
                'activo' => $diario->activo ? 'Si' : 'No',
            ];
        });

        return response()->json([
            'data' => $diarios,
            'count' => $diarios->count()
        ]);
    }
}