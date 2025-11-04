<?php

namespace App\Http\Controllers\Api\Contabilidad;

use App\Http\Controllers\Controller;
use App\Models\Contabilidad\AsientoDetalle;
use App\Models\Contabilidad\Asiento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AsientoDetalleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = AsientoDetalle::with(['asiento', 'cuenta']);

        // Filtros
        if ($request->has('asiento_id')) {
            $query->where('asiento_id', $request->asiento_id);
        }

        if ($request->has('cuenta_id')) {
            $query->where('cuenta_id', $request->cuenta_id);
        }

        if ($request->has('tipo')) {
            if ($request->tipo === 'cargo') {
                $query->where('debe', '>', 0);
            } elseif ($request->tipo === 'abono') {
                $query->where('haber', '>', 0);
            }
        }

        // Ordenamiento
        $sortBy = $request->get('sort_by', 'id');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginación
        $perPage = $request->get('per_page', 50);
        $detalles = $query->paginate($perPage);

        return response()->json($detalles);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'asiento_id' => 'required|exists:asientos,id',
            'cuenta_id' => 'required|exists:plan_cuentas,id',
            'descripcion' => 'nullable|string',
            'debe' => 'required|numeric|min:0',
            'haber' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        // Validar que no tenga debe y haber al mismo tiempo
        if ($request->debe > 0 && $request->haber > 0) {
            return response()->json([
                'message' => 'No se puede tener valores en debe y haber al mismo tiempo'
            ], 400);
        }

        // Validar que tenga al menos uno
        if ($request->debe == 0 && $request->haber == 0) {
            return response()->json([
                'message' => 'Debe especificar un monto en debe o haber'
            ], 400);
        }

        // Verificar que el asiento no esté registrado
        $asiento = Asiento::find($request->asiento_id);
        if ($asiento && $asiento->estado === 'registrado') {
            return response()->json([
                'message' => 'No se pueden agregar detalles a un asiento registrado'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $detalle = AsientoDetalle::create($request->all());

            DB::commit();

            return response()->json([
                'message' => 'Detalle agregado exitosamente',
                'data' => $detalle->load(['asiento', 'cuenta'])
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al agregar el detalle',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(AsientoDetalle $asientoDetalle)
    {
        return response()->json([
            'data' => $asientoDetalle->load(['asiento', 'cuenta']),
            'es_cargo' => $asientoDetalle->esCargo(),
            'es_abono' => $asientoDetalle->esAbono(),
            'monto' => $asientoDetalle->monto,
            'tipo_movimiento' => $asientoDetalle->tipo_movimiento,
            'cuenta_completa' => $asientoDetalle->cuenta_completa
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, AsientoDetalle $asientoDetalle)
    {
        // Verificar que el asiento no esté registrado
        if ($asientoDetalle->asiento && $asientoDetalle->asiento->estado === 'registrado') {
            return response()->json([
                'message' => 'No se pueden modificar detalles de un asiento registrado'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'cuenta_id' => 'sometimes|required|exists:plan_cuentas,id',
            'descripcion' => 'nullable|string',
            'debe' => 'sometimes|required|numeric|min:0',
            'haber' => 'sometimes|required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        // Validar debe y haber
        $debe = $request->get('debe', $asientoDetalle->debe);
        $haber = $request->get('haber', $asientoDetalle->haber);

        if ($debe > 0 && $haber > 0) {
            return response()->json([
                'message' => 'No se puede tener valores en debe y haber al mismo tiempo'
            ], 400);
        }

        if ($debe == 0 && $haber == 0) {
            return response()->json([
                'message' => 'Debe especificar un monto en debe o haber'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $asientoDetalle->update($request->all());

            DB::commit();

            return response()->json([
                'message' => 'Detalle actualizado exitosamente',
                'data' => $asientoDetalle->fresh(['asiento', 'cuenta'])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al actualizar el detalle',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AsientoDetalle $asientoDetalle)
    {
        // Verificar que el asiento no esté registrado
        if ($asientoDetalle->asiento && $asientoDetalle->asiento->estado === 'registrado') {
            return response()->json([
                'message' => 'No se pueden eliminar detalles de un asiento registrado'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $asientoDetalle->delete();

            DB::commit();

            return response()->json([
                'message' => 'Detalle eliminado exitosamente'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al eliminar el detalle',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cambiar tipo (cargo a abono o viceversa)
     */
    public function cambiarTipo(AsientoDetalle $asientoDetalle)
    {
        // Verificar que el asiento no esté registrado
        if ($asientoDetalle->asiento && $asientoDetalle->asiento->estado === 'registrado') {
            return response()->json([
                'message' => 'No se puede cambiar el tipo de un detalle de asiento registrado'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $tipoAnterior = $asientoDetalle->tipo_movimiento;
            $asientoDetalle->cambiarTipo();

            DB::commit();

            return response()->json([
                'message' => 'Tipo cambiado exitosamente',
                'tipo_anterior' => $tipoAnterior,
                'tipo_nuevo' => $asientoDetalle->tipo_movimiento,
                'data' => $asientoDetalle->fresh()
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al cambiar el tipo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Detalles por asiento
     */
    public function porAsiento($asientoId)
    {
        $detalles = AsientoDetalle::where('asiento_id', $asientoId)
            ->with(['cuenta'])
            ->orderBy('id')
            ->get();

        $totalDebe = $detalles->sum('debe');
        $totalHaber = $detalles->sum('haber');
        $diferencia = $totalDebe - $totalHaber;

        return response()->json([
            'data' => $detalles,
            'resumen' => [
                'cantidad_detalles' => $detalles->count(),
                'total_debe' => $totalDebe,
                'total_haber' => $totalHaber,
                'diferencia' => $diferencia,
                'esta_cuadrado' => abs($diferencia) < 0.01
            ]
        ]);
    }

    /**
     * Detalles por cuenta
     */
    public function porCuenta($cuentaId, Request $request)
    {
        $query = AsientoDetalle::where('cuenta_id', $cuentaId)
            ->with(['asiento']);

        if ($request->has('fecha_desde') && $request->has('fecha_hasta')) {
            $query->whereHas('asiento', function ($q) use ($request) {
                $q->whereBetween('fecha_asiento', [$request->fecha_desde, $request->fecha_hasta]);
            });
        }

        $detalles = $query->orderBy('created_at', 'desc')->get();

        $totalDebe = $detalles->sum('debe');
        $totalHaber = $detalles->sum('haber');
        $saldo = $totalDebe - $totalHaber;

        return response()->json([
            'data' => $detalles,
            'resumen' => [
                'cantidad_movimientos' => $detalles->count(),
                'total_debe' => $totalDebe,
                'total_haber' => $totalHaber,
                'saldo' => $saldo
            ]
        ]);
    }

    /**
     * Cargos (debe)
     */
    public function cargos(Request $request)
    {
        $query = AsientoDetalle::with(['asiento', 'cuenta'])
            ->where('debe', '>', 0);

        if ($request->has('cuenta_id')) {
            $query->where('cuenta_id', $request->cuenta_id);
        }

        $detalles = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 50));

        return response()->json($detalles);
    }

    /**
     * Abonos (haber)
     */
    public function abonos(Request $request)
    {
        $query = AsientoDetalle::with(['asiento', 'cuenta'])
            ->where('haber', '>', 0);

        if ($request->has('cuenta_id')) {
            $query->where('cuenta_id', $request->cuenta_id);
        }

        $detalles = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 50));

        return response()->json($detalles);
    }

    /**
     * Estadísticas de detalles
     */
    public function estadisticas(Request $request)
    {
        $query = AsientoDetalle::query();

        if ($request->has('fecha_desde') && $request->has('fecha_hasta')) {
            $query->whereHas('asiento', function ($q) use ($request) {
                $q->whereBetween('fecha_asiento', [$request->fecha_desde, $request->fecha_hasta]);
            });
        }

        $totalDetalles = (clone $query)->count();
        $totalDebe = (clone $query)->sum('debe');
        $totalHaber = (clone $query)->sum('haber');
        $totalCargos = (clone $query)->where('debe', '>', 0)->count();
        $totalAbonos = (clone $query)->where('haber', '>', 0)->count();

        $cuentasMasUsadas = (clone $query)
            ->select('cuenta_id')
            ->with('cuenta')
            ->selectRaw('COUNT(*) as cantidad')
            ->selectRaw('SUM(debe) as total_debe')
            ->selectRaw('SUM(haber) as total_haber')
            ->groupBy('cuenta_id')
            ->orderBy('cantidad', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'total_detalles' => $totalDetalles,
            'total_debe' => $totalDebe,
            'total_haber' => $totalHaber,
            'diferencia' => $totalDebe - $totalHaber,
            'total_cargos' => $totalCargos,
            'total_abonos' => $totalAbonos,
            'cuentas_mas_usadas' => $cuentasMasUsadas
        ]);
    }

    /**
     * Validar cuadre
     */
    public function validarCuadre($asientoId)
    {
        $detalles = AsientoDetalle::where('asiento_id', $asientoId)->get();

        $totalDebe = $detalles->sum('debe');
        $totalHaber = $detalles->sum('haber');
        $diferencia = abs($totalDebe - $totalHaber);
        $estaCuadrado = $diferencia < 0.01;

        return response()->json([
            'esta_cuadrado' => $estaCuadrado,
            'total_debe' => $totalDebe,
            'total_haber' => $totalHaber,
            'diferencia' => $diferencia,
            'mensaje' => $estaCuadrado 
                ? 'El asiento está cuadrado' 
                : "Existe una diferencia de {$diferencia}"
        ]);
    }

    /**
     * Duplicar detalle
     */
    public function duplicar(AsientoDetalle $asientoDetalle)
    {
        // Verificar que el asiento no esté registrado
        if ($asientoDetalle->asiento && $asientoDetalle->asiento->estado === 'registrado') {
            return response()->json([
                'message' => 'No se puede duplicar un detalle de asiento registrado'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $nuevoDetalle = $asientoDetalle->replicate();
            $nuevoDetalle->save();

            DB::commit();

            return response()->json([
                'message' => 'Detalle duplicado exitosamente',
                'data' => $nuevoDetalle->load(['asiento', 'cuenta'])
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al duplicar el detalle',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mayor contable (libro mayor)
     */
    public function mayorContable(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cuenta_id' => 'required|exists:plan_cuentas,id',
            'fecha_desde' => 'required|date',
            'fecha_hasta' => 'required|date|after_or_equal:fecha_desde',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $detalles = AsientoDetalle::where('cuenta_id', $request->cuenta_id)
            ->with(['asiento.diario'])
            ->whereHas('asiento', function ($q) use ($request) {
                $q->where('estado', 'registrado')
                    ->whereBetween('fecha_asiento', [$request->fecha_desde, $request->fecha_hasta]);
            })
            ->orderBy('created_at')
            ->get();

        $saldo = 0;
        $movimientos = $detalles->map(function ($detalle) use (&$saldo) {
            $saldo += ($detalle->debe - $detalle->haber);
            
            return [
                'fecha' => $detalle->asiento->fecha_asiento,
                'diario' => $detalle->asiento->diario->nombre,
                'numero_asiento' => $detalle->asiento->numero,
                'descripcion' => $detalle->descripcion,
                'debe' => $detalle->debe,
                'haber' => $detalle->haber,
                'saldo' => $saldo
            ];
        });

        return response()->json([
            'cuenta' => $request->cuenta_id,
            'periodo' => [
                'desde' => $request->fecha_desde,
                'hasta' => $request->fecha_hasta
            ],
            'movimientos' => $movimientos,
            'saldo_final' => $saldo
        ]);
    }

    /**
     * Exportar detalles
     */
    public function exportar(Request $request)
    {
        $query = AsientoDetalle::with(['asiento.diario', 'cuenta']);

        if ($request->has('asiento_id')) {
            $query->where('asiento_id', $request->asiento_id);
        }

        if ($request->has('cuenta_id')) {
            $query->where('cuenta_id', $request->cuenta_id);
        }

        $detalles = $query->orderBy('created_at')->get()->map(function ($detalle) {
            return [
                'fecha' => $detalle->asiento?->fecha_asiento,
                'diario' => $detalle->asiento?->diario?->nombre,
                'asiento' => $detalle->asiento?->numero,
                'cuenta_codigo' => $detalle->cuenta?->codigo,
                'cuenta_nombre' => $detalle->cuenta?->nombre,
                'descripcion' => $detalle->descripcion,
                'debe' => $detalle->debe,
                'haber' => $detalle->haber,
                'tipo' => $detalle->tipo_movimiento
            ];
        });

        return response()->json([
            'data' => $detalles,
            'count' => $detalles->count()
        ]);
    }
}