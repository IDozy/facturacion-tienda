<?php

namespace App\Http\Controllers\Api\Compras;

use App\Http\Controllers\Controller;
use App\Models\Compras\Compra;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CompraController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Compra::with(['proveedor', 'almacen', 'empresa']);

        // Filtros
        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->has('proveedor_id')) {
            $query->delProveedor($request->proveedor_id);
        }

        if ($request->has('almacen_id')) {
            $query->where('almacen_id', $request->almacen_id);
        }

        if ($request->has('fecha_desde') && $request->has('fecha_hasta')) {
            $query->delPeriodo($request->fecha_desde, $request->fecha_hasta);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('proveedor', function ($q) use ($search) {
                $q->where('razon_social', 'like', "%{$search}%")
                    ->orWhere('numero_documento', 'like', "%{$search}%");
            });
        }

        // Ordenamiento
        $sortBy = $request->get('sort_by', 'fecha_emision');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginación
        $perPage = $request->get('per_page', 15);
        $compras = $query->paginate($perPage);

        return response()->json($compras);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'proveedor_id' => 'required|exists:proveedores,id',
            'almacen_id' => 'required|exists:almacenes,id',
            'fecha_emision' => 'required|date',
            'detalles' => 'required|array|min:1',
            'detalles.*.producto_id' => 'required|exists:productos,id',
            'detalles.*.cantidad' => 'required|numeric|min:0.001',
            'detalles.*.precio_unitario' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Crear compra
            $compra = Compra::create([
                'proveedor_id' => $request->proveedor_id,
                'empresa_id' => Auth::user()->empresa_id,
                'almacen_id' => $request->almacen_id,
                'fecha_emision' => $request->fecha_emision,
                'estado' => 'registrada',
                'total' => 0,
            ]);

            // Crear detalles
            foreach ($request->detalles as $detalleData) {
                $compra->detalles()->create($detalleData);
            }

            // Calcular total
            $total = $compra->calcularTotal();
            $compra->update(['total' => $total]);

            // Generar movimientos de stock
            $compra->generarMovimientosStock();

            DB::commit();

            return response()->json([
                'message' => 'Compra registrada exitosamente',
                'data' => $compra->load(['proveedor', 'almacen', 'detalles.producto'])
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al registrar la compra',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Compra $compra)
    {
        return response()->json([
            'data' => $compra->load([
                'proveedor',
                'almacen',
                'detalles.producto',
                'movimientosStock'
            ]),
            'es_registrada' => $compra->esRegistrada(),
            'es_anulada' => $compra->esAnulada()
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Compra $compra)
    {
        if ($compra->esAnulada()) {
            return response()->json([
                'message' => 'No se puede modificar una compra anulada'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'fecha_emision' => 'sometimes|required|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $compra->update($request->only(['fecha_emision']));

            return response()->json([
                'message' => 'Compra actualizada exitosamente',
                'data' => $compra->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar la compra',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Compra $compra)
    {
        if ($compra->esAnulada()) {
            return response()->json([
                'message' => 'La compra ya está anulada'
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Anular compra (esto revierte el stock)
            $compra->anular();

            // Eliminar detalles
            $compra->detalles()->delete();

            // Eliminar compra
            $compra->delete();

            DB::commit();

            return response()->json([
                'message' => 'Compra eliminada exitosamente'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al eliminar la compra',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Anular compra
     */
    public function anular(Compra $compra)
    {
        if ($compra->esAnulada()) {
            return response()->json([
                'message' => 'La compra ya está anulada'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $compra->anular();

            DB::commit();

            return response()->json([
                'message' => 'Compra anulada exitosamente',
                'data' => $compra->fresh()
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al anular la compra',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Recalcular total
     */
    public function recalcularTotal(Compra $compra)
    {
        if ($compra->esAnulada()) {
            return response()->json([
                'message' => 'No se puede recalcular el total de una compra anulada'
            ], 400);
        }

        try {
            $total = $compra->calcularTotal();
            $compra->update(['total' => $total]);

            return response()->json([
                'message' => 'Total recalculado exitosamente',
                'data' => $compra->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al recalcular el total',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Compras registradas
     */
    public function registradas(Request $request)
    {
        $query = Compra::registradas()
            ->with(['proveedor', 'almacen']);

        if ($request->has('fecha_desde') && $request->has('fecha_hasta')) {
            $query->delPeriodo($request->fecha_desde, $request->fecha_hasta);
        }

        $compras = $query->orderBy('fecha_emision', 'desc')
            ->paginate($request->get('per_page', 50));

        return response()->json($compras);
    }

    /**
     * Compras anuladas
     */
    public function anuladas(Request $request)
    {
        $query = Compra::anuladas()
            ->with(['proveedor', 'almacen']);

        if ($request->has('fecha_desde') && $request->has('fecha_hasta')) {
            $query->delPeriodo($request->fecha_desde, $request->fecha_hasta);
        }

        $compras = $query->orderBy('fecha_emision', 'desc')
            ->paginate($request->get('per_page', 50));

        return response()->json($compras);
    }

    /**
     * Compras por proveedor
     */
    public function porProveedor($proveedorId, Request $request)
    {
        $query = Compra::delProveedor($proveedorId)
            ->with(['almacen']);

        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->has('fecha_desde') && $request->has('fecha_hasta')) {
            $query->delPeriodo($request->fecha_desde, $request->fecha_hasta);
        }

        $compras = $query->orderBy('fecha_emision', 'desc')->get();

        $totalCompras = $compras->where('estado', 'registrada')->sum('total');

        return response()->json([
            'data' => $compras,
            'resumen' => [
                'cantidad' => $compras->count(),
                'total_compras' => $totalCompras
            ]
        ]);
    }

    /**
     * Compras por almacén
     */
    public function porAlmacen($almacenId, Request $request)
    {
        $query = Compra::where('almacen_id', $almacenId)
            ->with(['proveedor']);

        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->has('fecha_desde') && $request->has('fecha_hasta')) {
            $query->delPeriodo($request->fecha_desde, $request->fecha_hasta);
        }

        $compras = $query->orderBy('fecha_emision', 'desc')->get();

        return response()->json([
            'data' => $compras,
            'count' => $compras->count()
        ]);
    }

    /**
     * Compras del período
     */
    public function delPeriodo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fecha_desde' => 'required|date',
            'fecha_hasta' => 'required|date|after_or_equal:fecha_desde',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $compras = Compra::delPeriodo($request->fecha_desde, $request->fecha_hasta)
            ->with(['proveedor', 'almacen'])
            ->orderBy('fecha_emision', 'desc')
            ->get();

        $totalCompras = $compras->where('estado', 'registrada')->sum('total');

        return response()->json([
            'periodo' => [
                'desde' => $request->fecha_desde,
                'hasta' => $request->fecha_hasta
            ],
            'data' => $compras,
            'resumen' => [
                'cantidad' => $compras->count(),
                'total' => $totalCompras
            ]
        ]);
    }

    /**
     * Estadísticas de compras
     */
    public function estadisticas(Request $request)
    {
        $query = Compra::where('empresa_id', Auth::user()->empresa_id);

        if ($request->has('fecha_desde') && $request->has('fecha_hasta')) {
            $query->delPeriodo($request->fecha_desde, $request->fecha_hasta);
        }

        $totalCompras = (clone $query)->count();
        $registradas = (clone $query)->registradas()->count();
        $anuladas = (clone $query)->anuladas()->count();
        $totalMonto = (clone $query)->registradas()->sum('total');

        $porProveedor = (clone $query)->registradas()
            ->select('proveedor_id')
            ->with('proveedor')
            ->selectRaw('COUNT(*) as cantidad')
            ->selectRaw('SUM(total) as monto_total')
            ->groupBy('proveedor_id')
            ->orderBy('monto_total', 'desc')
            ->limit(10)
            ->get();

        $porAlmacen = (clone $query)->registradas()
            ->select('almacen_id')
            ->with('almacen')
            ->selectRaw('COUNT(*) as cantidad')
            ->selectRaw('SUM(total) as monto_total')
            ->groupBy('almacen_id')
            ->orderBy('cantidad', 'desc')
            ->get();

        $porMes = (clone $query)->registradas()
            ->selectRaw('YEAR(fecha_emision) as año')
            ->selectRaw('MONTH(fecha_emision) as mes')
            ->selectRaw('COUNT(*) as cantidad')
            ->selectRaw('SUM(total) as monto_total')
            ->groupBy('año', 'mes')
            ->orderBy('año', 'desc')
            ->orderBy('mes', 'desc')
            ->limit(12)
            ->get();

        return response()->json([
            'total_compras' => $totalCompras,
            'registradas' => $registradas,
            'anuladas' => $anuladas,
            'total_monto' => $totalMonto,
            'por_proveedor' => $porProveedor,
            'por_almacen' => $porAlmacen,
            'por_mes' => $porMes
        ]);
    }

    /**
     * Compras del mes
     */
    public function delMes(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'año' => 'required|integer|min:2000|max:2100',
            'mes' => 'required|integer|min:1|max:12',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $compras = Compra::whereYear('fecha_emision', $request->año)
            ->whereMonth('fecha_emision', $request->mes)
            ->with(['proveedor', 'almacen'])
            ->orderBy('fecha_emision', 'desc')
            ->get();

        $totalMonto = $compras->where('estado', 'registrada')->sum('total');

        return response()->json([
            'año' => $request->año,
            'mes' => $request->mes,
            'data' => $compras,
            'resumen' => [
                'cantidad' => $compras->count(),
                'total' => $totalMonto
            ]
        ]);
    }

    /**
     * Compras del día
     */
    public function delDia(Request $request)
    {
        $fecha = $request->get('fecha', now()->toDateString());

        $compras = Compra::whereDate('fecha_emision', $fecha)
            ->with(['proveedor', 'almacen'])
            ->orderBy('created_at', 'desc')
            ->get();

        $totalMonto = $compras->where('estado', 'registrada')->sum('total');

        return response()->json([
            'fecha' => $fecha,
            'data' => $compras,
            'resumen' => [
                'cantidad' => $compras->count(),
                'registradas' => $compras->where('estado', 'registrada')->count(),
                'anuladas' => $compras->where('estado', 'anulada')->count(),
                'total' => $totalMonto
            ]
        ]);
    }

    /**
     * Exportar compras
     */
    public function exportar(Request $request)
    {
        $query = Compra::with(['proveedor', 'almacen']);

        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->has('fecha_desde') && $request->has('fecha_hasta')) {
            $query->delPeriodo($request->fecha_desde, $request->fecha_hasta);
        }

        $compras = $query->orderBy('fecha_emision', 'desc')->get()->map(function ($compra) {
            return [
                'id' => $compra->id,
                'fecha' => $compra->fecha_emision->format('Y-m-d'),
                'proveedor_documento' => $compra->proveedor->numero_documento,
                'proveedor_nombre' => $compra->proveedor->razon_social,
                'almacen' => $compra->almacen->nombre,
                'total' => $compra->total,
                'estado' => $compra->estado,
            ];
        });

        return response()->json([
            'data' => $compras,
            'count' => $compras->count()
        ]);
    }

    /**
     * Detalles de la compra
     */
    public function detalles(Compra $compra)
    {
        $detalles = $compra->detalles()
            ->with(['producto'])
            ->get();

        $totalCantidad = $detalles->sum('cantidad');
        $totalSubtotal = $detalles->sum('subtotal');

        return response()->json([
            'data' => $detalles,
            'resumen' => [
                'cantidad_items' => $detalles->count(),
                'total_cantidad' => $totalCantidad,
                'total_subtotal' => $totalSubtotal
            ]
        ]);
    }

    /**
     * Reporte de compras
     */
    public function reporte(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fecha_desde' => 'required|date',
            'fecha_hasta' => 'required|date|after_or_equal:fecha_desde',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $compras = Compra::delPeriodo($request->fecha_desde, $request->fecha_hasta)
            ->with(['proveedor', 'almacen', 'detalles.producto'])
            ->get();

        $registradas = $compras->where('estado', 'registrada');
        $anuladas = $compras->where('estado', 'anulada');

        return response()->json([
            'periodo' => [
                'desde' => $request->fecha_desde,
                'hasta' => $request->fecha_hasta
            ],
            'resumen' => [
                'total_compras' => $compras->count(),
                'registradas' => $registradas->count(),
                'anuladas' => $anuladas->count(),
                'monto_total' => $registradas->sum('total'),
            ],
            'compras' => $compras
        ]);
    }

    /**
     * Verificar stock generado
     */
    public function verificarStock(Compra $compra)
    {
        $movimientos = $compra->movimientosStock()
            ->with(['producto', 'almacen'])
            ->get();

        return response()->json([
            'data' => $movimientos,
            'count' => $movimientos->count(),
            'total_cantidad' => $movimientos->sum('cantidad')
        ]);
    }
}