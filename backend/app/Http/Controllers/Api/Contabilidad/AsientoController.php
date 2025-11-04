<?php

namespace App\Http\Controllers\Api\Contabilidad;

use App\Http\Controllers\Controller;
use App\Models\Contabilidad\Asiento;
use App\Models\Facturacion\Comprobante;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AsientoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Asiento::with(['diario', 'periodoContable', 'registradoPor']);

        // Filtros
        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->has('diario_id')) {
            $query->delDiario($request->diario_id);
        }

        if ($request->has('periodo_contable_id')) {
            $query->delPeriodo($request->periodo_contable_id);
        }

        if ($request->has('fecha_desde') && $request->has('fecha_hasta')) {
            $query->whereBetween('fecha', [$request->fecha_desde, $request->fecha_hasta]);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('numero', 'like', "%{$search}%")
                    ->orWhere('glosa', 'like', "%{$search}%");
            });
        }

        // Ordenamiento
        $sortBy = $request->get('sort_by', 'fecha');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginación
        $perPage = $request->get('per_page', 15);
        $asientos = $query->paginate($perPage);

        return response()->json($asientos);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'diario_id' => 'required|exists:diarios,id',
            'fecha' => 'required|date',
            'glosa' => 'required|string',
            'periodo_contable_id' => 'nullable|exists:periodos_contables,id',
            'comprobante_id' => 'nullable|exists:comprobantes,id',
            'detalles' => 'required|array|min:2',
            'detalles.*.cuenta_id' => 'required|exists:plan_cuentas,id',
            'detalles.*.descripcion' => 'nullable|string',
            'detalles.*.debe' => 'required|numeric|min:0',
            'detalles.*.haber' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        // Validar que cada detalle tenga debe o haber pero no ambos
        foreach ($request->detalles as $index => $detalle) {
            if ($detalle['debe'] > 0 && $detalle['haber'] > 0) {
                return response()->json([
                    'message' => "El detalle #{$index} no puede tener valores en debe y haber al mismo tiempo"
                ], 400);
            }
            if ($detalle['debe'] == 0 && $detalle['haber'] == 0) {
                return response()->json([
                    'message' => "El detalle #{$index} debe especificar un monto en debe o haber"
                ], 400);
            }
        }

        DB::beginTransaction();
        try {
            // Crear asiento
            $asiento = Asiento::create([
                'diario_id' => $request->diario_id,
                'fecha' => $request->fecha,
                'glosa' => $request->glosa,
                'periodo_contable_id' => $request->periodo_contable_id,
                'comprobante_id' => $request->comprobante_id,
                'estado' => 'borrador',
            ]);

            // Crear detalles
            foreach ($request->detalles as $detalleData) {
                $asiento->detalles()->create($detalleData);
            }

            // Calcular totales
            $asiento->calcularTotales();

            DB::commit();

            return response()->json([
                'message' => 'Asiento creado exitosamente',
                'data' => $asiento->load(['diario', 'periodoContable', 'detalles.cuenta'])
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al crear el asiento',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Asiento $asiento)
    {
        return response()->json([
            'data' => $asiento->load([
                'diario',
                'periodoContable',
                'comprobante',
                'detalles.cuenta',
                'registradoPor'
            ]),
            'esta_cuadrado' => $asiento->estaCuadrado(),
            'es_borrador' => $asiento->esBorrador(),
            'es_registrado' => $asiento->esRegistrado(),
            'es_anulado' => $asiento->esAnulado()
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Asiento $asiento)
    {
        if ($asiento->esRegistrado()) {
            return response()->json([
                'message' => 'No se puede modificar un asiento registrado'
            ], 400);
        }

        if ($asiento->esAnulado()) {
            return response()->json([
                'message' => 'No se puede modificar un asiento anulado'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'fecha' => 'sometimes|required|date',
            'glosa' => 'sometimes|required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $asiento->update($request->only(['fecha', 'glosa']));

            return response()->json([
                'message' => 'Asiento actualizado exitosamente',
                'data' => $asiento->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar el asiento',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Asiento $asiento)
    {
        if ($asiento->esRegistrado()) {
            return response()->json([
                'message' => 'No se puede eliminar un asiento registrado'
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Eliminar detalles
            $asiento->detalles()->delete();
            $asiento->delete();

            DB::commit();

            return response()->json([
                'message' => 'Asiento eliminado exitosamente'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al eliminar el asiento',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Registrar asiento
     */
    public function registrar(Asiento $asiento)
    {
        if ($asiento->esRegistrado()) {
            return response()->json([
                'message' => 'El asiento ya está registrado'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $asiento->registrar();

            DB::commit();

            return response()->json([
                'message' => 'Asiento registrado exitosamente',
                'data' => $asiento->fresh(['registradoPor'])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al registrar el asiento',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Anular asiento
     */
    public function anular(Asiento $asiento)
    {
        if ($asiento->esAnulado()) {
            return response()->json([
                'message' => 'El asiento ya está anulado'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $asiento->anular();

            DB::commit();

            return response()->json([
                'message' => 'Asiento anulado exitosamente',
                'data' => $asiento->fresh()
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al anular el asiento',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Duplicar asiento
     */
    public function duplicar(Asiento $asiento)
    {
        DB::beginTransaction();
        try {
            $nuevoAsiento = $asiento->duplicar();

            DB::commit();

            return response()->json([
                'message' => 'Asiento duplicado exitosamente',
                'data' => $nuevoAsiento->load(['diario', 'detalles.cuenta'])
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al duplicar el asiento',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Recalcular totales
     */
    public function recalcularTotales(Asiento $asiento)
    {
        if ($asiento->esRegistrado()) {
            return response()->json([
                'message' => 'No se pueden recalcular totales de un asiento registrado'
            ], 400);
        }

        try {
            $asiento->calcularTotales();

            return response()->json([
                'message' => 'Totales recalculados exitosamente',
                'data' => $asiento->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al recalcular totales',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validar cuadre
     */
    public function validarCuadre(Asiento $asiento)
    {
        $estaCuadrado = $asiento->estaCuadrado();
        $diferencia = abs($asiento->total_debe - $asiento->total_haber);

        return response()->json([
            'esta_cuadrado' => $estaCuadrado,
            'total_debe' => $asiento->total_debe,
            'total_haber' => $asiento->total_haber,
            'diferencia' => $diferencia,
            'mensaje' => $estaCuadrado 
                ? 'El asiento está cuadrado' 
                : "Existe una diferencia de {$diferencia}"
        ]);
    }

    /**
     * Generar desde comprobante
     */
    public function generarDesdeComprobante(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'comprobante_id' => 'required|exists:comprobantes,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $comprobante = Comprobante::find($request->comprobante_id);

        // Verificar si ya tiene asiento
        if ($comprobante->asientos()->exists()) {
            return response()->json([
                'message' => 'El comprobante ya tiene un asiento contable asociado'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $asiento = Asiento::generarDesdeComprobante($comprobante);

            DB::commit();

            return response()->json([
                'message' => 'Asiento generado exitosamente desde comprobante',
                'data' => $asiento->load(['diario', 'detalles.cuenta'])
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al generar el asiento',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Asientos borrador
     */
    public function borradores()
    {
        $asientos = Asiento::borradores()
            ->with(['diario', 'periodoContable'])
            ->orderBy('fecha', 'desc')
            ->get();

        return response()->json([
            'data' => $asientos,
            'count' => $asientos->count()
        ]);
    }

    /**
     * Asientos registrados
     */
    public function registrados(Request $request)
    {
        $query = Asiento::registrados()
            ->with(['diario', 'periodoContable', 'registradoPor']);

        if ($request->has('fecha_desde') && $request->has('fecha_hasta')) {
            $query->whereBetween('fecha', [$request->fecha_desde, $request->fecha_hasta]);
        }

        $asientos = $query->orderBy('fecha', 'desc')
            ->paginate($request->get('per_page', 50));

        return response()->json($asientos);
    }

    /**
     * Asientos por período
     */
    public function porPeriodo($periodoId, Request $request)
    {
        $query = Asiento::delPeriodo($periodoId)
            ->with(['diario']);

        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        $asientos = $query->orderBy('fecha', 'desc')->get();

        $totalDebe = $asientos->sum('total_debe');
        $totalHaber = $asientos->sum('total_haber');

        return response()->json([
            'data' => $asientos,
            'resumen' => [
                'cantidad' => $asientos->count(),
                'total_debe' => $totalDebe,
                'total_haber' => $totalHaber
            ]
        ]);
    }

    /**
     * Asientos por diario
     */
    public function porDiario($diarioId, Request $request)
    {
        $query = Asiento::delDiario($diarioId)
            ->with(['periodoContable']);

        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->has('fecha_desde') && $request->has('fecha_hasta')) {
            $query->whereBetween('fecha', [$request->fecha_desde, $request->fecha_hasta]);
        }

        $asientos = $query->orderBy('fecha', 'desc')->get();

        return response()->json([
            'data' => $asientos,
            'count' => $asientos->count()
        ]);
    }

    /**
     * Estadísticas
     */
    public function estadisticas(Request $request)
    {
        $query = Asiento::query();

        if ($request->has('fecha_desde') && $request->has('fecha_hasta')) {
            $query->whereBetween('fecha', [$request->fecha_desde, $request->fecha_hasta]);
        }

        $total = (clone $query)->count();
        $borradores = (clone $query)->borradores()->count();
        $registrados = (clone $query)->registrados()->count();
        $anulados = (clone $query)->anulados()->count();

        $totalDebe = (clone $query)->registrados()->sum('total_debe');
        $totalHaber = (clone $query)->registrados()->sum('total_haber');

        $porDiario = (clone $query)->select('diario_id')
            ->with('diario')
            ->selectRaw('COUNT(*) as cantidad')
            ->selectRaw('SUM(total_debe) as total_debe')
            ->selectRaw('SUM(total_haber) as total_haber')
            ->groupBy('diario_id')
            ->orderBy('cantidad', 'desc')
            ->get();

        $porPeriodo = (clone $query)->select('periodo_contable_id')
            ->with('periodoContable')
            ->selectRaw('COUNT(*) as cantidad')
            ->groupBy('periodo_contable_id')
            ->orderBy('cantidad', 'desc')
            ->get();

        return response()->json([
            'total_asientos' => $total,
            'borradores' => $borradores,
            'registrados' => $registrados,
            'anulados' => $anulados,
            'total_debe' => $totalDebe,
            'total_haber' => $totalHaber,
            'por_diario' => $porDiario,
            'por_periodo' => $porPeriodo
        ]);
    }

    /**
     * Libro diario
     */
    public function libroDiario(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fecha_desde' => 'required|date',
            'fecha_hasta' => 'required|date|after_or_equal:fecha_desde',
            'diario_id' => 'nullable|exists:diarios,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $query = Asiento::registrados()
            ->with(['diario', 'detalles.cuenta'])
            ->whereBetween('fecha', [$request->fecha_desde, $request->fecha_hasta]);

        if ($request->has('diario_id')) {
            $query->delDiario($request->diario_id);
        }

        $asientos = $query->orderBy('fecha')->orderBy('numero')->get();

        return response()->json([
            'periodo' => [
                'desde' => $request->fecha_desde,
                'hasta' => $request->fecha_hasta
            ],
            'data' => $asientos,
            'count' => $asientos->count()
        ]);
    }

    /**
     * Exportar asientos
     */
    public function exportar(Request $request)
    {
        $query = Asiento::with(['diario', 'periodoContable']);

        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->has('fecha_desde') && $request->has('fecha_hasta')) {
            $query->whereBetween('fecha', [$request->fecha_desde, $request->fecha_hasta]);
        }

        $asientos = $query->orderBy('fecha')->get()->map(function ($asiento) {
            return [
                'numero' => $asiento->numero,
                'fecha' => $asiento->fecha->format('Y-m-d'),
                'diario' => $asiento->diario?->nombre,
                'periodo' => $asiento->periodoContable?->nombre,
                'glosa' => $asiento->glosa,
                'total_debe' => $asiento->total_debe,
                'total_haber' => $asiento->total_haber,
                'estado' => $asiento->estado,
            ];
        });

        return response()->json([
            'data' => $asientos,
            'count' => $asientos->count()
        ]);
    }

    /**
     * Asientos descuadrados
     */
    public function descuadrados()
    {
        $asientos = Asiento::borradores()
            ->with(['diario'])
            ->get()
            ->filter(fn($a) => !$a->estaCuadrado());

        return response()->json([
            'data' => $asientos,
            'count' => $asientos->count()
        ]);
    }

    /**
     * Registrar múltiples
     */
    public function registrarMultiples(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'asiento_ids' => 'required|array|min:1',
            'asiento_ids.*' => 'exists:asientos,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $registrados = 0;
            $errores = [];

            foreach ($request->asiento_ids as $asientoId) {
                try {
                    $asiento = Asiento::find($asientoId);
                    
                    if ($asiento->esRegistrado()) {
                        $errores[] = [
                            'asiento_id' => $asientoId,
                            'error' => 'El asiento ya está registrado'
                        ];
                        continue;
                    }

                    $asiento->registrar();
                    $registrados++;
                } catch (\Exception $e) {
                    $errores[] = [
                        'asiento_id' => $asientoId,
                        'error' => $e->getMessage()
                    ];
                }
            }

            DB::commit();

            return response()->json([
                'message' => "Se registraron {$registrados} asiento(s)",
                'registrados' => $registrados,
                'errores' => $errores
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al registrar los asientos',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}