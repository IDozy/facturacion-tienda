<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pago;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PagoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Pago::with(['comprobante', 'medioPago', 'caja']);

        // Filtros
        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->has('comprobante_id')) {
            $query->where('comprobante_id', $request->comprobante_id);
        }

        if ($request->has('medio_pago_id')) {
            $query->where('medio_pago_id', $request->medio_pago_id);
        }

        if ($request->has('caja_id')) {
            $query->where('caja_id', $request->caja_id);
        }

        if ($request->has('fecha_desde') && $request->has('fecha_hasta')) {
            $query->delPeriodo($request->fecha_desde, $request->fecha_hasta);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('numero_referencia', 'like', "%{$search}%")
                    ->orWhereHas('comprobante', function ($q2) use ($search) {
                        $q2->where('serie', 'like', "%{$search}%")
                            ->orWhere('numero', 'like', "%{$search}%");
                    });
            });
        }

        // Ordenamiento
        $sortBy = $request->get('sort_by', 'fecha_pago');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginación
        $perPage = $request->get('per_page', 15);
        $pagos = $query->paginate($perPage);

        return response()->json($pagos);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'comprobante_id' => 'required|exists:comprobantes,id',
            'medio_pago_id' => 'required|exists:medios_pago,id',
            'caja_id' => 'required|exists:cajas,id',
            'monto' => 'required|numeric|min:0.01',
            'fecha_pago' => 'required|date',
            'numero_referencia' => 'nullable|string|max:100',
            'estado' => 'sometimes|in:pendiente,confirmado,anulado',
            'cuota_numero' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $pago = Pago::create($request->all());

            // Si el estado es confirmado desde la creación, establecer fecha de confirmación
            if ($pago->estado === 'confirmado') {
                $pago->update(['fecha_confirmacion' => now()]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Pago registrado exitosamente',
                'data' => $pago->load(['comprobante', 'medioPago', 'caja'])
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al registrar el pago',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Pago $pago)
    {
        return response()->json([
            'data' => $pago->load(['comprobante', 'medioPago', 'caja'])
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Pago $pago)
    {
        $validator = Validator::make($request->all(), [
            'comprobante_id' => 'sometimes|required|exists:comprobantes,id',
            'medio_pago_id' => 'sometimes|required|exists:medios_pago,id',
            'caja_id' => 'sometimes|required|exists:cajas,id',
            'monto' => 'sometimes|required|numeric|min:0.01',
            'fecha_pago' => 'sometimes|required|date',
            'numero_referencia' => 'nullable|string|max:100',
            'estado' => 'sometimes|in:pendiente,confirmado,anulado',
            'cuota_numero' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $pago->update($request->all());

            DB::commit();

            return response()->json([
                'message' => 'Pago actualizado exitosamente',
                'data' => $pago->fresh(['comprobante', 'medioPago', 'caja'])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al actualizar el pago',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Pago $pago)
    {
        DB::beginTransaction();
        try {
            $pago->delete();

            // El observer actualizará automáticamente el saldo del comprobante
            DB::commit();

            return response()->json([
                'message' => 'Pago eliminado exitosamente'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al eliminar el pago',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Confirmar pago
     */
    public function confirmar(Pago $pago)
    {
        if ($pago->esConfirmado()) {
            return response()->json([
                'message' => 'El pago ya está confirmado'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $pago->confirmar();

            DB::commit();

            return response()->json([
                'message' => 'Pago confirmado exitosamente',
                'data' => $pago->fresh()
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al confirmar el pago',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Anular pago
     */
    public function anular(Pago $pago)
    {
        if ($pago->estado === 'anulado') {
            return response()->json([
                'message' => 'El pago ya está anulado'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $pago->anular();

            DB::commit();

            return response()->json([
                'message' => 'Pago anulado exitosamente',
                'data' => $pago->fresh()
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al anular el pago',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener pagos de un comprobante
     */
    public function porComprobante($comprobanteId)
    {
        $pagos = Pago::where('comprobante_id', $comprobanteId)
            ->with(['medioPago', 'caja'])
            ->orderBy('fecha_pago', 'desc')
            ->get();

        $totalPagado = $pagos->where('estado', 'confirmado')->sum('monto');
        $totalPendiente = $pagos->where('estado', 'pendiente')->sum('monto');

        return response()->json([
            'data' => $pagos,
            'resumen' => [
                'total_pagado' => $totalPagado,
                'total_pendiente' => $totalPendiente,
                'cantidad_pagos' => $pagos->count()
            ]
        ]);
    }

    /**
     * Obtener pagos de una caja
     */
    public function porCaja($cajaId, Request $request)
    {
        $query = Pago::where('caja_id', $cajaId)
            ->with(['comprobante', 'medioPago']);

        if ($request->has('fecha_desde') && $request->has('fecha_hasta')) {
            $query->delPeriodo($request->fecha_desde, $request->fecha_hasta);
        }

        $pagos = $query->orderBy('fecha_pago', 'desc')->get();

        $totalConfirmados = $pagos->where('estado', 'confirmado')->sum('monto');
        $totalPendientes = $pagos->where('estado', 'pendiente')->sum('monto');

        return response()->json([
            'data' => $pagos,
            'resumen' => [
                'total_confirmados' => $totalConfirmados,
                'total_pendientes' => $totalPendientes,
                'cantidad_pagos' => $pagos->count()
            ]
        ]);
    }

    /**
     * Estadísticas de pagos
     */
    public function estadisticas(Request $request)
    {
        $query = Pago::query();

        if ($request->has('fecha_desde') && $request->has('fecha_hasta')) {
            $query->delPeriodo($request->fecha_desde, $request->fecha_hasta);
        }

        $totalConfirmados = (clone $query)->confirmados()->sum('monto');
        $totalPendientes = (clone $query)->pendientes()->sum('monto');
        $cantidadConfirmados = (clone $query)->confirmados()->count();
        $cantidadPendientes = (clone $query)->pendientes()->count();

        // Pagos por medio de pago
        $porMedioPago = (clone $query)->confirmados()
            ->select('medio_pago_id', DB::raw('SUM(monto) as total'), DB::raw('COUNT(*) as cantidad'))
            ->groupBy('medio_pago_id')
            ->with('medioPago')
            ->get();

        // Pagos por día (últimos 30 días si no se especifica rango)
        if (!$request->has('fecha_desde')) {
            $query->where('fecha_pago', '>=', now()->subDays(30));
        }

        $porDia = (clone $query)->confirmados()
            ->select(
                DB::raw('DATE(fecha_pago) as fecha'),
                DB::raw('SUM(monto) as total'),
                DB::raw('COUNT(*) as cantidad')
            )
            ->groupBy('fecha')
            ->orderBy('fecha', 'desc')
            ->get();

        return response()->json([
            'confirmados' => [
                'total' => $totalConfirmados,
                'cantidad' => $cantidadConfirmados
            ],
            'pendientes' => [
                'total' => $totalPendientes,
                'cantidad' => $cantidadPendientes
            ],
            'por_medio_pago' => $porMedioPago,
            'por_dia' => $porDia
        ]);
    }

    /**
     * Registrar múltiples pagos (cuotas)
     */
    public function registrarCuotas(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'comprobante_id' => 'required|exists:comprobantes,id',
            'medio_pago_id' => 'required|exists:medios_pago,id',
            'caja_id' => 'required|exists:cajas,id',
            'cuotas' => 'required|array|min:1',
            'cuotas.*.monto' => 'required|numeric|min:0.01',
            'cuotas.*.fecha_pago' => 'required|date',
            'cuotas.*.numero_referencia' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $pagos = [];
            foreach ($request->cuotas as $index => $cuota) {
                $pago = Pago::create([
                    'comprobante_id' => $request->comprobante_id,
                    'medio_pago_id' => $request->medio_pago_id,
                    'caja_id' => $request->caja_id,
                    'monto' => $cuota['monto'],
                    'fecha_pago' => $cuota['fecha_pago'],
                    'numero_referencia' => $cuota['numero_referencia'] ?? null,
                    'cuota_numero' => $index + 1,
                    'estado' => 'pendiente',
                ]);
                $pagos[] = $pago;
            }

            DB::commit();

            return response()->json([
                'message' => 'Cuotas registradas exitosamente',
                'data' => $pagos,
                'cantidad' => count($pagos)
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al registrar las cuotas',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}