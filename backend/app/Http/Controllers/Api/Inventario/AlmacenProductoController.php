<?php

namespace App\Http\Controllers\Api\Inventario;

use App\Http\Controllers\Controller;
use App\Models\Inventario\AlmacenProducto;
use App\Models\Inventario\Almacen;
use App\Models\Inventario\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AlmacenProductoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = AlmacenProducto::with(['almacen', 'producto']);

        // Filtros
        if ($request->has('almacen_id')) {
            $query->where('almacen_id', $request->almacen_id);
        }

        if ($request->has('producto_id')) {
            $query->where('producto_id', $request->producto_id);
        }

        if ($request->has('con_stock')) {
            $query->where('stock_actual', '>', 0);
        }

        if ($request->has('sin_stock')) {
            $query->where('stock_actual', '<=', 0);
        }

        if ($request->has('bajo_stock')) {
            $query->whereHas('producto', function ($q) {
                $q->whereRaw('almacen_productos.stock_actual <= productos.stock_minimo');
            });
        }

        // Búsqueda
        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('producto', function ($q) use ($search) {
                $q->where('codigo', 'like', "%{$search}%")
                    ->orWhere('nombre', 'like', "%{$search}%");
            });
        }

        // Ordenamiento
        $sortBy = $request->get('sort_by', 'stock_actual');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginación
        $perPage = $request->get('per_page', 50);
        $almacenProductos = $query->paginate($perPage);

        return response()->json($almacenProductos);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'almacen_id' => 'required|exists:almacenes,id',
            'producto_id' => 'required|exists:productos,id',
            'stock_actual' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        // Verificar si ya existe
        $existe = AlmacenProducto::where('almacen_id', $request->almacen_id)
            ->where('producto_id', $request->producto_id)
            ->exists();

        if ($existe) {
            return response()->json([
                'message' => 'El producto ya existe en este almacén'
            ], 400);
        }

        try {
            $almacenProducto = AlmacenProducto::create($request->all());

            return response()->json([
                'message' => 'Stock del producto creado exitosamente',
                'data' => $almacenProducto->load(['almacen', 'producto'])
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear el stock',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(AlmacenProducto $almacenProducto)
    {
        return response()->json([
            'data' => $almacenProducto->load(['almacen', 'producto']),
            'es_bajo_stock' => $almacenProducto->esBajoStock(),
            'valor_inventario' => $almacenProducto->valorInventario()
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, AlmacenProducto $almacenProducto)
    {
        $validator = Validator::make($request->all(), [
            'stock_actual' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $almacenProducto->update($request->all());

            return response()->json([
                'message' => 'Stock actualizado exitosamente',
                'data' => $almacenProducto->fresh(['almacen', 'producto'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar el stock',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AlmacenProducto $almacenProducto)
    {
        if ($almacenProducto->stock_actual > 0) {
            return response()->json([
                'message' => 'No se puede eliminar un producto con stock',
                'stock_actual' => $almacenProducto->stock_actual
            ], 400);
        }

        try {
            $almacenProducto->delete();

            return response()->json([
                'message' => 'Stock del producto eliminado exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar el stock',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ajustar stock
     */
    public function ajustarStock(Request $request, AlmacenProducto $almacenProducto)
    {
        $validator = Validator::make($request->all(), [
            'cantidad' => 'required|numeric',
            'tipo' => 'required|in:suma,resta,establecer',
            'observacion' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $stockAnterior = $almacenProducto->stock_actual;
            
            $almacenProducto->ajustarStock($request->cantidad, $request->tipo);

            // Crear movimiento de stock
            if ($request->tipo === 'suma' || $request->tipo === 'establecer') {
                $diferencia = $almacenProducto->stock_actual - $stockAnterior;
                if ($diferencia > 0) {
                    \App\Models\Inventario\MovimientoStock::create([
                        'producto_id' => $almacenProducto->producto_id,
                        'almacen_id' => $almacenProducto->almacen_id,
                        'tipo' => 'entrada',
                        'cantidad' => abs($diferencia),
                        'costo_unitario' => $almacenProducto->producto->precio_compra,
                        'observacion' => $request->observacion ?? 'Ajuste manual de stock',
                    ]);
                }
            } elseif ($request->tipo === 'resta') {
                \App\Models\Inventario\MovimientoStock::create([
                    'producto_id' => $almacenProducto->producto_id,
                    'almacen_id' => $almacenProducto->almacen_id,
                    'tipo' => 'salida',
                    'cantidad' => $request->cantidad,
                    'costo_unitario' => $almacenProducto->producto->precio_compra,
                    'observacion' => $request->observacion ?? 'Ajuste manual de stock',
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Stock ajustado exitosamente',
                'data' => $almacenProducto->fresh(),
                'stock_anterior' => $stockAnterior,
                'stock_nuevo' => $almacenProducto->stock_actual
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al ajustar el stock',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener stock de un producto en todos los almacenes
     */
    public function stockPorProducto($productoId)
    {
        $almacenes = AlmacenProducto::where('producto_id', $productoId)
            ->with(['almacen'])
            ->get()
            ->map(function ($ap) {
                return [
                    'almacen_id' => $ap->almacen_id,
                    'almacen' => $ap->almacen->nombre,
                    'stock_actual' => $ap->stock_actual,
                    'es_bajo_stock' => $ap->esBajoStock(),
                    'valor_inventario' => $ap->valorInventario(),
                ];
            });

        $stockTotal = $almacenes->sum('stock_actual');
        $valorTotal = $almacenes->sum('valor_inventario');

        return response()->json([
            'producto_id' => $productoId,
            'almacenes' => $almacenes,
            'stock_total' => $stockTotal,
            'valor_total' => $valorTotal
        ]);
    }

    /**
     * Obtener todos los productos de un almacén
     */
    public function productosPorAlmacen($almacenId, Request $request)
    {
        $query = AlmacenProducto::where('almacen_id', $almacenId)
            ->with(['producto.categoria']);

        if ($request->has('con_stock')) {
            $query->where('stock_actual', '>', 0);
        }

        if ($request->has('bajo_stock')) {
            $query->whereHas('producto', function ($q) {
                $q->whereRaw('almacen_productos.stock_actual <= productos.stock_minimo');
            });
        }

        $productos = $query->orderBy('stock_actual', 'desc')->get();

        $stockTotal = $productos->sum('stock_actual');
        $valorTotal = $productos->sum(fn($ap) => $ap->valorInventario());

        return response()->json([
            'almacen_id' => $almacenId,
            'data' => $productos,
            'resumen' => [
                'total_productos' => $productos->count(),
                'stock_total' => $stockTotal,
                'valor_total' => $valorTotal
            ]
        ]);
    }

    /**
     * Productos con bajo stock
     */
    public function bajoStock(Request $request)
    {
        $query = AlmacenProducto::with(['almacen', 'producto'])
            ->whereHas('producto', function ($q) {
                $q->whereRaw('almacen_productos.stock_actual <= productos.stock_minimo');
            });

        if ($request->has('almacen_id')) {
            $query->where('almacen_id', $request->almacen_id);
        }

        $productos = $query->orderBy('stock_actual', 'asc')->get();

        return response()->json([
            'data' => $productos->map(function ($ap) {
                return [
                    'almacen_producto_id' => $ap->id,
                    'almacen' => $ap->almacen->nombre,
                    'producto_codigo' => $ap->producto->codigo,
                    'producto_nombre' => $ap->producto->nombre,
                    'stock_actual' => $ap->stock_actual,
                    'stock_minimo' => $ap->producto->stock_minimo,
                    'diferencia' => $ap->stock_actual - $ap->producto->stock_minimo,
                ];
            }),
            'count' => $productos->count()
        ]);
    }

    /**
     * Productos sin stock
     */
    public function sinStock(Request $request)
    {
        $query = AlmacenProducto::with(['almacen', 'producto'])
            ->where('stock_actual', '<=', 0);

        if ($request->has('almacen_id')) {
            $query->where('almacen_id', $request->almacen_id);
        }

        $productos = $query->get();

        return response()->json([
            'data' => $productos,
            'count' => $productos->count()
        ]);
    }

    /**
     * Valorización de inventario
     */
    public function valorizacion(Request $request)
    {
        $query = AlmacenProducto::with(['almacen', 'producto'])
            ->where('stock_actual', '>', 0);

        if ($request->has('almacen_id')) {
            $query->where('almacen_id', $request->almacen_id);
        }

        $items = $query->get();

        $valorizacion = $items->map(function ($ap) {
            return [
                'almacen' => $ap->almacen->nombre,
                'producto_codigo' => $ap->producto->codigo,
                'producto_nombre' => $ap->producto->nombre,
                'stock_actual' => $ap->stock_actual,
                'precio_promedio' => $ap->producto->precio_promedio,
                'valor_total' => $ap->valorInventario(),
            ];
        });

        $valorTotal = $valorizacion->sum('valor_total');

        // Agrupar por almacén
        $porAlmacen = $items->groupBy('almacen_id')->map(function ($productos, $almacenId) {
            return [
                'almacen' => $productos->first()->almacen->nombre,
                'cantidad_productos' => $productos->count(),
                'stock_total' => $productos->sum('stock_actual'),
                'valor_total' => $productos->sum(fn($ap) => $ap->valorInventario()),
            ];
        })->values();

        return response()->json([
            'data' => $valorizacion,
            'por_almacen' => $porAlmacen,
            'valor_total_inventario' => $valorTotal
        ]);
    }

    /**
     * Verificar disponibilidad
     */
    public function verificarDisponibilidad(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'almacen_id' => 'required|exists:almacenes,id',
            'producto_id' => 'required|exists:productos,id',
            'cantidad' => 'required|numeric|min:0.001',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $almacenProducto = AlmacenProducto::where('almacen_id', $request->almacen_id)
            ->where('producto_id', $request->producto_id)
            ->first();

        if (!$almacenProducto) {
            return response()->json([
                'disponible' => false,
                'stock_actual' => 0,
                'cantidad_solicitada' => $request->cantidad,
                'mensaje' => 'El producto no existe en este almacén'
            ]);
        }

        $disponible = $almacenProducto->tieneStockSuficiente($request->cantidad);

        return response()->json([
            'disponible' => $disponible,
            'stock_actual' => $almacenProducto->stock_actual,
            'cantidad_solicitada' => $request->cantidad,
            'faltante' => $disponible ? 0 : $request->cantidad - $almacenProducto->stock_actual,
            'mensaje' => $disponible ? 'Stock disponible' : 'Stock insuficiente'
        ]);
    }

    /**
     * Transferir stock entre almacenes
     */
    public function transferir(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'almacen_origen_id' => 'required|exists:almacenes,id',
            'almacen_destino_id' => 'required|exists:almacenes,id|different:almacen_origen_id',
            'producto_id' => 'required|exists:productos,id',
            'cantidad' => 'required|numeric|min:0.001',
            'observacion' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Verificar stock origen
            $origen = AlmacenProducto::where('almacen_id', $request->almacen_origen_id)
                ->where('producto_id', $request->producto_id)
                ->firstOrFail();

            if (!$origen->tieneStockSuficiente($request->cantidad)) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Stock insuficiente en el almacén de origen',
                    'stock_disponible' => $origen->stock_actual
                ], 400);
            }

            // Restar del origen
            $origen->ajustarStock($request->cantidad, 'resta');

            // Sumar al destino
            $destino = AlmacenProducto::firstOrCreate(
                [
                    'almacen_id' => $request->almacen_destino_id,
                    'producto_id' => $request->producto_id
                ],
                ['stock_actual' => 0]
            );

            $destino->ajustarStock($request->cantidad, 'suma');

            DB::commit();

            return response()->json([
                'message' => 'Transferencia realizada exitosamente',
                'origen' => [
                    'almacen_id' => $origen->almacen_id,
                    'stock_nuevo' => $origen->stock_actual
                ],
                'destino' => [
                    'almacen_id' => $destino->almacen_id,
                    'stock_nuevo' => $destino->stock_actual
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al transferir el stock',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Estadísticas generales
     */
    public function estadisticas(Request $request)
    {
        $query = AlmacenProducto::with(['almacen', 'producto']);

        if ($request->has('almacen_id')) {
            $query->where('almacen_id', $request->almacen_id);
        }

        $total = (clone $query)->count();
        $conStock = (clone $query)->where('stock_actual', '>', 0)->count();
        $sinStock = (clone $query)->where('stock_actual', '<=', 0)->count();
        
        $bajoStock = (clone $query)->whereHas('producto', function ($q) {
            $q->whereRaw('almacen_productos.stock_actual <= productos.stock_minimo');
        })->count();

        $valorTotal = (clone $query)->get()->sum(fn($ap) => $ap->valorInventario());

        return response()->json([
            'total_registros' => $total,
            'con_stock' => $conStock,
            'sin_stock' => $sinStock,
            'bajo_stock' => $bajoStock,
            'valor_total_inventario' => $valorTotal
        ]);
    }
}