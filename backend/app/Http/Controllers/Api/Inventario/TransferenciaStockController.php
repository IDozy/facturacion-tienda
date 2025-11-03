<?php

namespace App\Http\Controllers\Api\Inventario;

use App\Http\Controllers\Controller;
use App\Models\Inventario\TransferenciaStock;
use App\Models\Inventario\AlmacenProducto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TransferenciaStockController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = TransferenciaStock::with(['almacenOrigen', 'almacenDestino', 'usuario']);

        // Filtros
        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->has('almacen_origen_id')) {
            $query->where('almacen_origen_id', $request->almacen_origen_id);
        }

        if ($request->has('almacen_destino_id')) {
            $query->where('almacen_destino_id', $request->almacen_destino_id);
        }

        if ($request->has('usuario_id')) {
            $query->where('usuario_id', $request->usuario_id);
        }

        if ($request->has('fecha_desde') && $request->has('fecha_hasta')) {
            $query->whereBetween('fecha_transferencia', [$request->fecha_desde, $request->fecha_hasta]);
        }

        // Ordenamiento
        $sortBy = $request->get('sort_by', 'fecha_transferencia');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginación
        $perPage = $request->get('per_page', 15);
        $transferencias = $query->paginate($perPage);

        return response()->json($transferencias);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'almacen_origen_id' => 'required|exists:almacenes,id',
            'almacen_destino_id' => 'required|exists:almacenes,id|different:almacen_origen_id',
            'observacion' => 'nullable|string',
            'fecha_transferencia' => 'nullable|date',
            'detalles' => 'required|array|min:1',
            'detalles.*.producto_id' => 'required|exists:productos,id',
            'detalles.*.cantidad' => 'required|numeric|min:0.01',
            'detalles.*.costo_unitario' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Preparar detalles con almacenes
            $detalles = collect($request->detalles)->map(function ($detalle) use ($request) {
                return array_merge($detalle, [
                    'almacen_origen_id' => $request->almacen_origen_id,
                    'almacen_destino_id' => $request->almacen_destino_id,
                ]);
            })->toArray();

            $transferencia = TransferenciaStock::crearConMovimientos([
                'almacen_origen_id' => $request->almacen_origen_id,
                'almacen_destino_id' => $request->almacen_destino_id,
                'observacion' => $request->observacion,
                'fecha_transferencia' => $request->fecha_transferencia ?? now(),
            ], $detalles);

            // Aplicar automáticamente si se solicita
            if ($request->get('aplicar', false)) {
                $transferencia->aplicar();
            }

            DB::commit();

            return response()->json([
                'message' => 'Transferencia creada exitosamente',
                'data' => $transferencia->load(['almacenOrigen', 'almacenDestino', 'usuario', 'movimientosStock'])
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al crear la transferencia',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(TransferenciaStock $transferenciaStock)
    {
        return response()->json([
            'data' => $transferenciaStock->load([
                'almacenOrigen',
                'almacenDestino',
                'usuario',
                'movimientosStock.producto'
            ])
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TransferenciaStock $transferenciaStock)
    {
        if (!$transferenciaStock->esPendiente()) {
            return response()->json([
                'message' => 'Solo se pueden editar transferencias pendientes'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'observacion' => 'nullable|string',
            'fecha_transferencia' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $transferenciaStock->update($request->only(['observacion', 'fecha_transferencia']));

            return response()->json([
                'message' => 'Transferencia actualizada exitosamente',
                'data' => $transferenciaStock->fresh(['almacenOrigen', 'almacenDestino', 'usuario'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar la transferencia',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TransferenciaStock $transferenciaStock)
    {
        if (!$transferenciaStock->esPendiente()) {
            return response()->json([
                'message' => 'Solo se pueden eliminar transferencias pendientes'
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Eliminar movimientos asociados
            $transferenciaStock->movimientosStock()->delete();
            $transferenciaStock->delete();

            DB::commit();

            return response()->json([
                'message' => 'Transferencia eliminada exitosamente'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al eliminar la transferencia',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Aplicar transferencia
     */
    public function aplicar(TransferenciaStock $transferenciaStock)
    {
        if ($transferenciaStock->esAplicada()) {
            return response()->json([
                'message' => 'La transferencia ya está aplicada'
            ], 400);
        }

        if ($transferenciaStock->esAnulada()) {
            return response()->json([
                'message' => 'No se puede aplicar una transferencia anulada'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $transferenciaStock->aplicar();

            DB::commit();

            return response()->json([
                'message' => 'Transferencia aplicada exitosamente',
                'data' => $transferenciaStock->fresh()
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al aplicar la transferencia',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Anular transferencia
     */
    public function anular(TransferenciaStock $transferenciaStock)
    {
        if (!$transferenciaStock->esAplicada()) {
            return response()->json([
                'message' => 'Solo se pueden anular transferencias aplicadas'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $transferenciaStock->anular();

            DB::commit();

            return response()->json([
                'message' => 'Transferencia anulada exitosamente',
                'data' => $transferenciaStock->fresh()
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al anular la transferencia',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener movimientos de la transferencia
     */
    public function movimientos(TransferenciaStock $transferenciaStock)
    {
        $movimientos = $transferenciaStock->movimientosStock()
            ->with(['producto', 'almacen'])
            ->orderBy('tipo', 'desc')
            ->get()
            ->groupBy('producto_id')
            ->map(function ($movs) {
                return [
                    'producto' => $movs->first()->producto,
                    'salida' => $movs->where('tipo', 'salida')->first(),
                    'entrada' => $movs->where('tipo', 'entrada')->first(),
                ];
            })->values();

        return response()->json([
            'data' => $movimientos
        ]);
    }

    /**
     * Validar stock disponible
     */
    public function validarStock(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'almacen_origen_id' => 'required|exists:almacenes,id',
            'productos' => 'required|array',
            'productos.*.producto_id' => 'required|exists:productos,id',
            'productos.*.cantidad' => 'required|numeric|min:0.01',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $errores = [];
        $valido = true;

        foreach ($request->productos as $producto) {
            $stock = AlmacenProducto::getStock($request->almacen_origen_id, $producto['producto_id']);
            
            if ($stock < $producto['cantidad']) {
                $valido = false;
                $errores[] = [
                    'producto_id' => $producto['producto_id'],
                    'cantidad_solicitada' => $producto['cantidad'],
                    'stock_disponible' => $stock,
                    'faltante' => $producto['cantidad'] - $stock
                ];
            }
        }

        return response()->json([
            'valido' => $valido,
            'errores' => $errores
        ]);
    }

    /**
     * Estadísticas de transferencias
     */
    public function estadisticas(Request $request)
    {
        $query = TransferenciaStock::query();

        if ($request->has('fecha_desde') && $request->has('fecha_hasta')) {
            $query->whereBetween('fecha_transferencia', [$request->fecha_desde, $request->fecha_hasta]);
        }

        $total = (clone $query)->count();
        $pendientes = (clone $query)->where('estado', 'pendiente')->count();
        $aplicadas = (clone $query)->where('estado', 'aplicada')->count();
        $anuladas = (clone $query)->where('estado', 'anulada')->count();

        $porAlmacenOrigen = (clone $query)
            ->select('almacen_origen_id')
            ->with('almacenOrigen')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('almacen_origen_id')
            ->orderBy('total', 'desc')
            ->get();

        $porAlmacenDestino = (clone $query)
            ->select('almacen_destino_id')
            ->with('almacenDestino')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('almacen_destino_id')
            ->orderBy('total', 'desc')
            ->get();

        return response()->json([
            'total' => $total,
            'pendientes' => $pendientes,
            'aplicadas' => $aplicadas,
            'anuladas' => $anuladas,
            'por_almacen_origen' => $porAlmacenOrigen,
            'por_almacen_destino' => $porAlmacenDestino
        ]);
    }

    /**
     * Transferencias entre dos almacenes
     */
    public function entreAlmacenes($almacenOrigenId, $almacenDestinoId, Request $request)
    {
        $query = TransferenciaStock::where('almacen_origen_id', $almacenOrigenId)
            ->where('almacen_destino_id', $almacenDestinoId)
            ->with(['usuario', 'movimientosStock']);

        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->has('fecha_desde') && $request->has('fecha_hasta')) {
            $query->whereBetween('fecha_transferencia', [$request->fecha_desde, $request->fecha_hasta]);
        }

        $transferencias = $query->orderBy('fecha_transferencia', 'desc')->get();

        return response()->json([
            'data' => $transferencias,
            'count' => $transferencias->count()
        ]);
    }
}