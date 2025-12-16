<?php

namespace App\Http\Controllers\Api\Inventario;

use App\Http\Controllers\Controller;
use App\Models\Inventario\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ProductoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Producto::with(['categoria', 'empresa']);

        // Filtros
        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->has('categoria_id')) {
            $query->where('categoria_id', $request->categoria_id);
        }

        if ($request->has('bajo_stock')) {
            $query->bajoStock();
        }

        if ($request->has('search')) {
            $query->buscar($request->search);
        }

        // Ordenamiento
        $sortBy = $request->get('sort_by', 'nombre');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginación
        $perPage = $request->get('per_page', 15);
        
        if ($request->get('all') === 'true') {
            $productos = $query->activos()->get();
            return response()->json(['data' => $productos]);
        }

        $productos = $query->paginate($perPage);

        return response()->json($productos);
    }

    /**
     * Resumen rápido de productos para dashboards.
     */
    public function resumen()
    {
        $empresaId = Auth::user()->empresa_id;

        $productos = Producto::where('empresa_id', $empresaId)->get();

        $total = $productos->count();
        $activos = $productos->where('estado', 'activo')->count();
        $inactivos = $productos->where('estado', 'inactivo')->count();
        $bajoStock = $productos->filter(fn ($producto) => $producto->es_bajo_stock)->count();
        $stockTotal = $productos->sum(fn ($producto) => $producto->stock_actual);
        $valorInventario = $productos->sum(fn ($producto) => $producto->stock_actual * $producto->precio_promedio);

        return response()->json([
            'total' => $total,
            'activos' => $activos,
            'inactivos' => $inactivos,
            'bajo_stock' => $bajoStock,
            'stock_total' => round($stockTotal, 3),
            'valor_inventario' => round($valorInventario, 2),
        ]);
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
                'max:50',
                Rule::unique('productos')->where(function ($query) use ($empresaId) {
                    return $query->where('empresa_id', $empresaId);
                })
            ],
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'categoria_id' => 'required|exists:categorias,id',
            'unidad_medida' => 'required|string|max:10',
            'precio_compra' => 'required|numeric|min:0',
            'precio_venta' => 'required|numeric|min:0',
            'stock_minimo' => 'nullable|numeric|min:0',
            'cod_producto_sunat' => 'nullable|string|max:20',
            'estado' => 'sometimes|in:activo,inactivo',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $producto = Producto::create($request->all());

            return response()->json([
                'message' => 'Producto creado exitosamente',
                'data' => $producto->load(['categoria', 'empresa'])
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear el producto',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Producto $producto)
    {
        // Verificar que pertenece a la misma empresa
        if ($producto->empresa_id !== Auth::user()->empresa_id) {
            return response()->json([
                'message' => 'No autorizado'
            ], 403);
        }

        return response()->json([
            'data' => $producto->load(['categoria', 'empresa', 'almacenes.almacen']),
            'stock_actual' => $producto->stock_actual,
            'precio_promedio' => $producto->precio_promedio,
            'margen' => $producto->margen,
            'es_bajo_stock' => $producto->es_bajo_stock
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Producto $producto)
    {
        // Verificar que pertenece a la misma empresa
        if ($producto->empresa_id !== Auth::user()->empresa_id) {
            return response()->json([
                'message' => 'No autorizado'
            ], 403);
        }

        $empresaId = Auth::user()->empresa_id;

        $validator = Validator::make($request->all(), [
            'codigo' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('productos')->ignore($producto->id)->where(function ($query) use ($empresaId) {
                    return $query->where('empresa_id', $empresaId);
                })
            ],
            'nombre' => 'sometimes|required|string|max:255',
            'descripcion' => 'nullable|string',
            'categoria_id' => 'sometimes|required|exists:categorias,id',
            'unidad_medida' => 'sometimes|required|string|max:10',
            'precio_compra' => 'sometimes|required|numeric|min:0',
            'precio_venta' => 'sometimes|required|numeric|min:0',
            'stock_minimo' => 'nullable|numeric|min:0',
            'cod_producto_sunat' => 'nullable|string|max:20',
            'estado' => 'sometimes|in:activo,inactivo',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $producto->update($request->all());

            // Limpiar cache
            Cache::forget("producto_{$producto->id}_stock");
            Cache::forget("producto_{$producto->id}_costo_promedio");

            return response()->json([
                'message' => 'Producto actualizado exitosamente',
                'data' => $producto->fresh(['categoria', 'empresa'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar el producto',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Producto $producto)
    {
        // Verificar que pertenece a la misma empresa
        if ($producto->empresa_id !== Auth::user()->empresa_id) {
            return response()->json([
                'message' => 'No autorizado'
            ], 403);
        }

        // Verificar si tiene stock
        if ($producto->stock_actual > 0) {
            return response()->json([
                'message' => 'No se puede eliminar un producto con stock'
            ], 400);
        }

        // Verificar si tiene movimientos
        if ($producto->movimientosStock()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar un producto con movimientos de stock'
            ], 400);
        }

        try {
            $producto->delete();

            return response()->json([
                'message' => 'Producto eliminado exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar el producto',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle estado del producto
     */
    public function toggleEstado(Producto $producto)
    {
        // Verificar que pertenece a la misma empresa
        if ($producto->empresa_id !== Auth::user()->empresa_id) {
            return response()->json([
                'message' => 'No autorizado'
            ], 403);
        }

        try {
            if ($producto->esActivo()) {
                $producto->inactivar();
            } else {
                $producto->activar();
            }

            return response()->json([
                'message' => "Producto {$producto->estado}",
                'data' => $producto
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al cambiar el estado',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar precios
     */
    public function actualizarPrecios(Request $request, Producto $producto)
    {
        // Verificar que pertenece a la misma empresa
        if ($producto->empresa_id !== Auth::user()->empresa_id) {
            return response()->json([
                'message' => 'No autorizado'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'precio_compra' => 'nullable|numeric|min:0',
            'precio_venta' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $producto->actualizarPrecios(
                $request->precio_compra,
                $request->precio_venta
            );

            return response()->json([
                'message' => 'Precios actualizados exitosamente',
                'data' => $producto->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar los precios',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener stock por almacén
     */
    public function stockPorAlmacen(Producto $producto)
    {
        // Verificar que pertenece a la misma empresa
        if ($producto->empresa_id !== Auth::user()->empresa_id) {
            return response()->json([
                'message' => 'No autorizado'
            ], 403);
        }

        $almacenes = $producto->almacenes()
            ->with('almacen')
            ->get()
            ->map(function ($ap) {
                return [
                    'almacen_id' => $ap->almacen_id,
                    'almacen' => $ap->almacen->nombre,
                    'stock_actual' => $ap->stock_actual,
                ];
            });

        return response()->json([
            'data' => $almacenes,
            'stock_total' => $producto->stock_actual
        ]);
    }

    /**
     * Productos bajo stock
     */
    public function bajoStock()
    {
        $productos = Producto::where('empresa_id', Auth::user()->empresa_id)
            ->bajoStock()
            ->with(['categoria'])
            ->get()
            ->map(function ($producto) {
                return [
                    'id' => $producto->id,
                    'codigo' => $producto->codigo,
                    'nombre' => $producto->nombre,
                    'categoria' => $producto->categoria->nombre ?? null,
                    'stock_actual' => $producto->stock_actual,
                    'stock_minimo' => $producto->stock_minimo,
                    'diferencia' => $producto->stock_actual - $producto->stock_minimo,
                ];
            });

        return response()->json([
            'data' => $productos,
            'count' => $productos->count()
        ]);
    }

    /**
     * Calcular costos
     */
    public function calcularCostos(Producto $producto, Request $request)
    {
        // Verificar que pertenece a la misma empresa
        if ($producto->empresa_id !== Auth::user()->empresa_id) {
            return response()->json([
                'message' => 'No autorizado'
            ], 403);
        }

        $metodo = $request->get('metodo', 'promedio');

        $costo = match ($metodo) {
            'peps' => $producto->calcularCostoPEPS(),
            'ueps' => $producto->calcularCostoUEPS(),
            default => $producto->precio_promedio,
        };

        return response()->json([
            'metodo' => $metodo,
            'costo' => $costo,
            'precio_compra' => $producto->precio_compra,
            'precio_venta' => $producto->precio_venta,
            'margen' => $producto->margen
        ]);
    }

    /**
     * Movimientos del producto
     */
    public function movimientos(Producto $producto, Request $request)
    {
        // Verificar que pertenece a la misma empresa
        if ($producto->empresa_id !== Auth::user()->empresa_id) {
            return response()->json([
                'message' => 'No autorizado'
            ], 403);
        }

        $query = $producto->movimientosStock()->with(['almacen']);

        if ($request->has('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->has('fecha_desde') && $request->has('fecha_hasta')) {
            $query->whereBetween('created_at', [$request->fecha_desde, $request->fecha_hasta]);
        }

        $movimientos = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 50));

        return response()->json($movimientos);
    }

    /**
     * Estadísticas del producto
     */
    public function estadisticas(Producto $producto)
    {
        // Verificar que pertenece a la misma empresa
        if ($producto->empresa_id !== Auth::user()->empresa_id) {
            return response()->json([
                'message' => 'No autorizado'
            ], 403);
        }

        $totalVentas = $producto->detallesComprobante()
            ->whereHas('comprobante', function ($q) {
                $q->where('estado', '!=', 'anulado');
            })
            ->sum(DB::raw('cantidad * precio_unitario'));

        $totalCompras = $producto->detallesCompra()
            ->whereHas('compra', function ($q) {
                $q->where('estado', '!=', 'anulada');
            })
            ->sum(DB::raw('cantidad * precio_unitario'));

        $cantidadVendida = $producto->detallesComprobante()
            ->whereHas('comprobante', function ($q) {
                $q->where('estado', '!=', 'anulado');
            })
            ->sum('cantidad');

        $cantidadComprada = $producto->detallesCompra()
            ->whereHas('compra', function ($q) {
                $q->where('estado', '!=', 'anulada');
            })
            ->sum('cantidad');

        return response()->json([
            'stock_actual' => $producto->stock_actual,
            'stock_minimo' => $producto->stock_minimo,
            'es_bajo_stock' => $producto->es_bajo_stock,
            'precio_compra' => $producto->precio_compra,
            'precio_venta' => $producto->precio_venta,
            'precio_promedio' => $producto->precio_promedio,
            'margen' => $producto->margen,
            'total_ventas' => $totalVentas,
            'total_compras' => $totalCompras,
            'cantidad_vendida' => $cantidadVendida,
            'cantidad_comprada' => $cantidadComprada
        ]);
    }

    /**
     * Importar productos
     */
    public function importar(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'productos' => 'required|array',
            'productos.*.codigo' => 'required|string|max:50',
            'productos.*.nombre' => 'required|string|max:255',
            'productos.*.categoria_id' => 'required|exists:categorias,id',
            'productos.*.unidad_medida' => 'required|string|max:10',
            'productos.*.precio_compra' => 'required|numeric|min:0',
            'productos.*.precio_venta' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $importados = 0;
            $errores = [];
            $empresaId = Auth::user()->empresa_id;

            foreach ($request->productos as $index => $productoData) {
                try {
                    // Verificar si ya existe
                    $existe = Producto::where('codigo', $productoData['codigo'])
                        ->where('empresa_id', $empresaId)
                        ->exists();

                    if ($existe) {
                        $errores[] = [
                            'index' => $index,
                            'codigo' => $productoData['codigo'],
                            'error' => 'El producto ya existe'
                        ];
                        continue;
                    }

                    Producto::create(array_merge($productoData, [
                        'empresa_id' => $empresaId,
                        'estado' => 'activo'
                    ]));

                    $importados++;
                } catch (\Exception $e) {
                    $errores[] = [
                        'index' => $index,
                        'codigo' => $productoData['codigo'] ?? 'N/A',
                        'error' => $e->getMessage()
                    ];
                }
            }

            DB::commit();

            return response()->json([
                'message' => "Se importaron {$importados} productos",
                'importados' => $importados,
                'errores' => $errores
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al importar productos',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}