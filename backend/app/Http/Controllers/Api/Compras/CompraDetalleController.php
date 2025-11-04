<?php

namespace App\Http\Controllers\Api\Compras;

use App\Http\Controllers\Controller;
use App\Models\Compras\CompraDetalle;
use App\Models\Compras\Compra;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CompraDetalleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = CompraDetalle::with(['compra', 'producto']);

        // Filtros
        if ($request->has('compra_id')) {
            $query->where('compra_id', $request->compra_id);
        }

        if ($request->has('producto_id')) {
            $query->where('producto_id', $request->producto_id);
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
            'compra_id' => 'required|exists:compras,id',
            'producto_id' => 'required|exists:productos,id',
            'cantidad' => 'required|numeric|min:0.001',
            'precio_unitario' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        // Verificar que la compra no esté anulada
        $compra = Compra::find($request->compra_id);
        if ($compra && $compra->esAnulada()) {
            return response()->json([
                'message' => 'No se pueden agregar detalles a una compra anulada'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $detalle = CompraDetalle::create($request->all());

            // Recalcular total de la compra
            if ($compra) {
                $total = $compra->calcularTotal();
                $compra->update(['total' => $total]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Detalle agregado exitosamente',
                'data' => $detalle->load(['compra', 'producto'])
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
    public function show(CompraDetalle $compraDetalle)
    {
        return response()->json([
            'data' => $compraDetalle->load(['compra', 'producto']),
            'nombre_producto' => $compraDetalle->nombre_producto,
            'total' => $compraDetalle->total
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CompraDetalle $compraDetalle)
    {
        // Verificar que la compra no esté anulada
        if ($compraDetalle->compra && $compraDetalle->compra->esAnulada()) {
            return response()->json([
                'message' => 'No se pueden modificar detalles de una compra anulada'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'cantidad' => 'sometimes|required|numeric|min:0.001',
            'precio_unitario' => 'sometimes|required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $compraDetalle->update($request->all());

            // Recalcular total de la compra
            if ($compraDetalle->compra) {
                $total = $compraDetalle->compra->calcularTotal();
                $compraDetalle->compra->update(['total' => $total]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Detalle actualizado exitosamente',
                'data' => $compraDetalle->fresh(['compra', 'producto'])
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
    public function destroy(CompraDetalle $compraDetalle)
    {
        // Verificar que la compra no esté anulada
        if ($compraDetalle->compra && $compraDetalle->compra->esAnulada()) {
            return response()->json([
                'message' => 'No se pueden eliminar detalles de una compra anulada'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $compra = $compraDetalle->compra;
            $compraDetalle->delete();

            // Recalcular total de la compra
            if ($compra) {
                $total = $compra->calcularTotal();
                $compra->update(['total' => $total]);
            }

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
     * Recalcular subtotal
     */
    public function recalcularSubtotal(CompraDetalle $compraDetalle)
    {
        // Verificar que la compra no esté anulada
        if ($compraDetalle->compra && $compraDetalle->compra->esAnulada()) {
            return response()->json([
                'message' => 'No se puede recalcular un detalle de compra anulada'
            ], 400);
        }

        try {
            $compraDetalle->recalcularSubtotal();

            return response()->json([
                'message' => 'Subtotal recalculado exitosamente',
                'data' => $compraDetalle->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al recalcular el subtotal',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Detalles por compra
     */
    public function porCompra($compraId)
    {
        $detalles = CompraDetalle::where('compra_id', $compraId)
            ->with(['producto'])
            ->orderBy('id')
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
     * Detalles por producto
     */
    public function porProducto($productoId, Request $request)
    {
        $query = CompraDetalle::where('producto_id', $productoId)
            ->with(['compra.proveedor']);

        if ($request->has('fecha_desde') && $request->has('fecha_hasta')) {
            $query->whereHas('compra', function ($q) use ($request) {
                $q->whereBetween('fecha_emision', [$request->fecha_desde, $request->fecha_hasta]);
            });
        }

        $detalles = $query->orderBy('created_at', 'desc')->get();

        $cantidadTotal = $detalles->sum('cantidad');
        $montoTotal = $detalles->sum('subtotal');
        $precioPromedio = $cantidadTotal > 0 ? $montoTotal / $cantidadTotal : 0;

        return response()->json([
            'data' => $detalles,
            'resumen' => [
                'cantidad_compras' => $detalles->count(),
                'cantidad_total' => $cantidadTotal,
                'monto_total' => $montoTotal,
                'precio_promedio' => $precioPromedio
            ]
        ]);
    }

    /**
     * Productos más comprados
     */
    public function productosMasComprados(Request $request)
    {
        $query = CompraDetalle::with(['producto'])
            ->whereHas('compra', function ($q) {
                $q->where('estado', 'registrada');
            });

        if ($request->has('fecha_desde') && $request->has('fecha_hasta')) {
            $query->whereHas('compra', function ($q) use ($request) {
                $q->whereBetween('fecha_emision', [$request->fecha_desde, $request->fecha_hasta]);
            });
        }

        $productos = $query->select('producto_id')
            ->selectRaw('SUM(cantidad) as cantidad_total')
            ->selectRaw('SUM(subtotal) as monto_total')
            ->selectRaw('COUNT(*) as veces_comprado')
            ->selectRaw('AVG(precio_unitario) as precio_promedio')
            ->groupBy('producto_id')
            ->orderBy('cantidad_total', 'desc')
            ->limit($request->get('limit', 10))
            ->get();

        return response()->json([
            'data' => $productos
        ]);
    }

    /**
     * Estadísticas de detalles
     */
    public function estadisticas(Request $request)
    {
        $query = CompraDetalle::whereHas('compra', function ($q) {
            $q->where('estado', 'registrada');
        });

        if ($request->has('fecha_desde') && $request->has('fecha_hasta')) {
            $query->whereHas('compra', function ($q) use ($request) {
                $q->whereBetween('fecha_emision', [$request->fecha_desde, $request->fecha_hasta]);
            });
        }

        $totalDetalles = (clone $query)->count();
        $cantidadTotal = (clone $query)->sum('cantidad');
        $subtotalTotal = (clone $query)->sum('subtotal');

        $productosUnicos = (clone $query)->distinct('producto_id')->count('producto_id');

        $precioPromedio = $cantidadTotal > 0 ? $subtotalTotal / $cantidadTotal : 0;

        return response()->json([
            'total_detalles' => $totalDetalles,
            'cantidad_total' => $cantidadTotal,
            'subtotal_total' => $subtotalTotal,
            'productos_unicos' => $productosUnicos,
            'precio_promedio' => $precioPromedio
        ]);
    }

    /**
     * Historial de precios
     */
    public function historialPrecios($productoId, Request $request)
    {
        $query = CompraDetalle::where('producto_id', $productoId)
            ->with(['compra.proveedor'])
            ->whereHas('compra', function ($q) {
                $q->where('estado', 'registrada');
            });

        if ($request->has('fecha_desde') && $request->has('fecha_hasta')) {
            $query->whereHas('compra', function ($q) use ($request) {
                $q->whereBetween('fecha_emision', [$request->fecha_desde, $request->fecha_hasta]);
            });
        }

        $detalles = $query->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($detalle) {
                return [
                    'fecha' => $detalle->compra->fecha_emision,
                    'proveedor' => $detalle->compra->proveedor->razon_social,
                    'cantidad' => $detalle->cantidad,
                    'precio_unitario' => $detalle->precio_unitario,
                    'subtotal' => $detalle->subtotal
                ];
            });

        $precioMinimo = $detalles->min('precio_unitario');
        $precioMaximo = $detalles->max('precio_unitario');
        $precioPromedio = $detalles->avg('precio_unitario');

        return response()->json([
            'data' => $detalles,
            'analisis' => [
                'precio_minimo' => $precioMinimo,
                'precio_maximo' => $precioMaximo,
                'precio_promedio' => $precioPromedio
            ]
        ]);
    }

    /**
     * Exportar detalles
     */
    public function exportar(Request $request)
    {
        $query = CompraDetalle::with(['compra.proveedor', 'producto']);

        if ($request->has('compra_id')) {
            $query->where('compra_id', $request->compra_id);
        }

        if ($request->has('producto_id')) {
            $query->where('producto_id', $request->producto_id);
        }

        $detalles = $query->orderBy('created_at')->get()->map(function ($detalle) {
            return [
                'compra_id' => $detalle->compra_id,
                'fecha' => $detalle->compra?->fecha_emision,
                'proveedor' => $detalle->compra?->proveedor?->razon_social,
                'producto_codigo' => $detalle->producto?->codigo,
                'producto_nombre' => $detalle->producto?->nombre,
                'cantidad' => $detalle->cantidad,
                'precio_unitario' => $detalle->precio_unitario,
                'subtotal' => $detalle->subtotal
            ];
        });

        return response()->json([
            'data' => $detalles,
            'count' => $detalles->count()
        ]);
    }

    /**
     * Comparar precios entre proveedores
     */
    public function compararPrecios($productoId, Request $request)
    {
        $detalles = CompraDetalle::where('producto_id', $productoId)
            ->with(['compra.proveedor'])
            ->whereHas('compra', function ($q) {
                $q->where('estado', 'registrada');
            })
            ->get();

        if ($request->has('fecha_desde') && $request->has('fecha_hasta')) {
            $detalles = $detalles->filter(function ($detalle) use ($request) {
                return $detalle->compra->fecha_emision >= $request->fecha_desde
                    && $detalle->compra->fecha_emision <= $request->fecha_hasta;
            });
        }

        $porProveedor = $detalles->groupBy('compra.proveedor_id')
            ->map(function ($items, $proveedorId) {
                $proveedor = $items->first()->compra->proveedor;
                return [
                    'proveedor_id' => $proveedorId,
                    'proveedor_nombre' => $proveedor->razon_social,
                    'cantidad_compras' => $items->count(),
                    'cantidad_total' => $items->sum('cantidad'),
                    'precio_minimo' => $items->min('precio_unitario'),
                    'precio_maximo' => $items->max('precio_unitario'),
                    'precio_promedio' => $items->avg('precio_unitario'),
                    'ultima_compra' => $items->sortByDesc('created_at')->first()->compra->fecha_emision
                ];
            })
            ->sortBy('precio_promedio')
            ->values();

        return response()->json([
            'producto_id' => $productoId,
            'comparacion' => $porProveedor
        ]);
    }

    /**
     * Últimas compras de un producto
     */
    public function ultimasCompras($productoId, Request $request)
    {
        $limit = $request->get('limit', 10);

        $detalles = CompraDetalle::where('producto_id', $productoId)
            ->with(['compra.proveedor'])
            ->whereHas('compra', function ($q) {
                $q->where('estado', 'registrada');
            })
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'data' => $detalles,
            'count' => $detalles->count()
        ]);
    }

    /**
     * Validar precios
     */
    public function validarPrecios(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'producto_id' => 'required|exists:productos,id',
            'precio_unitario' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        // Obtener últimas 10 compras del producto
        $ultimasCompras = CompraDetalle::where('producto_id', $request->producto_id)
            ->whereHas('compra', function ($q) {
                $q->where('estado', 'registrada');
            })
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        if ($ultimasCompras->isEmpty()) {
            return response()->json([
                'es_valido' => true,
                'mensaje' => 'No hay historial de compras para comparar'
            ]);
        }

        $precioPromedio = $ultimasCompras->avg('precio_unitario');
        $precioMinimo = $ultimasCompras->min('precio_unitario');
        $precioMaximo = $ultimasCompras->max('precio_unitario');

        $variacion = (($request->precio_unitario - $precioPromedio) / $precioPromedio) * 100;

        $esValido = abs($variacion) <= 20; // Variación máxima del 20%

        return response()->json([
            'es_valido' => $esValido,
            'precio_solicitado' => $request->precio_unitario,
            'precio_promedio' => $precioPromedio,
            'precio_minimo' => $precioMinimo,
            'precio_maximo' => $precioMaximo,
            'variacion_porcentaje' => round($variacion, 2),
            'mensaje' => $esValido 
                ? 'El precio está dentro del rango esperado' 
                : 'El precio tiene una variación significativa'
        ]);
    }
}