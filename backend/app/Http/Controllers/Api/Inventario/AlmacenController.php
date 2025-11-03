<?php

namespace App\Http\Controllers\Api\Inventario;

use App\Http\Controllers\Controller;
use App\Models\Inventario\Almacen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AlmacenController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Almacen::with(['empresa'])
            ->withCount('productos');

        // Filtros
        if ($request->has('activo')) {
            $query->where('activo', $request->activo);
        }

        if ($request->has('con_stock')) {
            $query->conStock();
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                    ->orWhere('ubicacion', 'like', "%{$search}%");
            });
        }

        // Ordenamiento
        $sortBy = $request->get('sort_by', 'nombre');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginación
        $perPage = $request->get('per_page', 15);
        
        if ($request->get('all') === 'true') {
            $almacenes = $query->activos()->get();
            return response()->json(['data' => $almacenes]);
        }

        $almacenes = $query->paginate($perPage);

        return response()->json($almacenes);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $empresaId = Auth::user()->empresa_id;

        $validator = Validator::make($request->all(), [
            'nombre' => [
                'required',
                'string',
                'max:100',
                Rule::unique('almacenes')->where(function ($query) use ($empresaId) {
                    return $query->where('empresa_id', $empresaId);
                })
            ],
            'ubicacion' => 'nullable|string|max:255',
            'activo' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $almacen = Almacen::create($request->all());

            return response()->json([
                'message' => 'Almacén creado exitosamente',
                'data' => $almacen->load('empresa')
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear el almacén',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Almacen $almacen)
    {
        // Verificar que pertenece a la misma empresa
        if ($almacen->empresa_id !== Auth::user()->empresa_id) {
            return response()->json([
                'message' => 'No autorizado'
            ], 403);
        }

        return response()->json([
            'data' => $almacen->load(['empresa']),
            'cantidad_productos' => $almacen->productos()->count(),
            'valor_inventario' => $almacen->valorInventario(),
            'productos_bajo_stock' => $almacen->productosConBajoStock()->count()
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Almacen $almacen)
    {
        // Verificar que pertenece a la misma empresa
        if ($almacen->empresa_id !== Auth::user()->empresa_id) {
            return response()->json([
                'message' => 'No autorizado'
            ], 403);
        }

        $empresaId = Auth::user()->empresa_id;

        $validator = Validator::make($request->all(), [
            'nombre' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique('almacenes')->ignore($almacen->id)->where(function ($query) use ($empresaId) {
                    return $query->where('empresa_id', $empresaId);
                })
            ],
            'ubicacion' => 'nullable|string|max:255',
            'activo' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $almacen->update($request->all());

            // Limpiar caché
            $this->limpiarCache($almacen->id);

            return response()->json([
                'message' => 'Almacén actualizado exitosamente',
                'data' => $almacen->fresh(['empresa'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar el almacén',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Almacen $almacen)
    {
        // Verificar que pertenece a la misma empresa
        if ($almacen->empresa_id !== Auth::user()->empresa_id) {
            return response()->json([
                'message' => 'No autorizado'
            ], 403);
        }

        // Verificar si tiene productos con stock
        if ($almacen->productos()->where('stock_actual', '>', 0)->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar un almacén con productos en stock'
            ], 400);
        }

        try {
            $almacen->delete();

            // Limpiar caché
            $this->limpiarCache($almacen->id);

            return response()->json([
                'message' => 'Almacén eliminado exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar el almacén',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle estado del almacén
     */
    public function toggleEstado(Almacen $almacen)
    {
        // Verificar que pertenece a la misma empresa
        if ($almacen->empresa_id !== Auth::user()->empresa_id) {
            return response()->json([
                'message' => 'No autorizado'
            ], 403);
        }

        try {
            if ($almacen->esActivo()) {
                $almacen->desactivar();
            } else {
                $almacen->activar();
            }

            return response()->json([
                'message' => "Almacén " . ($almacen->activo ? 'activado' : 'desactivado'),
                'data' => $almacen
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al cambiar el estado',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener productos del almacén
     */
    public function productos(Almacen $almacen, Request $request)
    {
        // Verificar que pertenece a la misma empresa
        if ($almacen->empresa_id !== Auth::user()->empresa_id) {
            return response()->json([
                'message' => 'No autorizado'
            ], 403);
        }

        $query = $almacen->productos()->with(['producto']);

        if ($request->has('con_stock')) {
            $query->where('stock_actual', '>', 0);
        }

        if ($request->has('bajo_stock')) {
            $query->whereHas('producto', function ($q) {
                $q->whereRaw('almacen_productos.stock_actual <= productos.stock_minimo');
            });
        }

        $productos = $query->orderBy('stock_actual', 'desc')
            ->paginate($request->get('per_page', 50));

        return response()->json($productos);
    }

    /**
     * Verificar stock de producto
     */
    public function verificarStock(Request $request, Almacen $almacen)
    {
        // Verificar que pertenece a la misma empresa
        if ($almacen->empresa_id !== Auth::user()->empresa_id) {
            return response()->json([
                'message' => 'No autorizado'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'producto_id' => 'required|exists:productos,id',
            'cantidad' => 'required|numeric|min:0.001',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $stock = $almacen->stockProducto($request->producto_id);
        $tieneStock = $almacen->tieneStock($request->producto_id, $request->cantidad);

        return response()->json([
            'tiene_stock' => $tieneStock,
            'stock_actual' => $stock,
            'cantidad_solicitada' => $request->cantidad,
            'faltante' => $tieneStock ? 0 : $request->cantidad - $stock
        ]);
    }

    /**
     * Productos con bajo stock
     */
    public function productosConBajoStock(Almacen $almacen)
    {
        // Verificar que pertenece a la misma empresa
        if ($almacen->empresa_id !== Auth::user()->empresa_id) {
            return response()->json([
                'message' => 'No autorizado'
            ], 403);
        }

        $productos = $almacen->productosConBajoStock()->map(function ($ap) {
            return [
                'producto_id' => $ap->producto_id,
                'codigo' => $ap->producto->codigo,
                'nombre' => $ap->producto->nombre,
                'stock_actual' => $ap->stock_actual,
                'stock_minimo' => $ap->producto->stock_minimo,
                'diferencia' => $ap->stock_actual - $ap->producto->stock_minimo,
            ];
        });

        return response()->json([
            'data' => $productos,
            'count' => $productos->count()
        ]);
    }

    /**
     * Movimientos del almacén
     */
    public function movimientos(Almacen $almacen, Request $request)
    {
        // Verificar que pertenece a la misma empresa
        if ($almacen->empresa_id !== Auth::user()->empresa_id) {
            return response()->json([
                'message' => 'No autorizado'
            ], 403);
        }

        $query = $almacen->movimientosStock()->with(['producto']);

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
     * Estadísticas del almacén
     */
    public function estadisticas(Almacen $almacen)
    {
        // Verificar que pertenece a la misma empresa
        if ($almacen->empresa_id !== Auth::user()->empresa_id) {
            return response()->json([
                'message' => 'No autorizado'
            ], 403);
        }

        $totalProductos = $almacen->productos()->count();
        $productosConStock = $almacen->productos()->where('stock_actual', '>', 0)->count();
        $productosSinStock = $almacen->productos()->where('stock_actual', '<=', 0)->count();
        $productosBajoStock = $almacen->productosConBajoStock()->count();
        
        $stockTotal = $almacen->productos()->sum('stock_actual');
        $valorInventario = $almacen->valorInventario();

        $ultimosMovimientos = $almacen->movimientosStock()
            ->with(['producto'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'total_productos' => $totalProductos,
            'productos_con_stock' => $productosConStock,
            'productos_sin_stock' => $productosSinStock,
            'productos_bajo_stock' => $productosBajoStock,
            'stock_total' => $stockTotal,
            'valor_inventario' => $valorInventario,
            'ultimos_movimientos' => $ultimosMovimientos
        ]);
    }

    /**
     * Valorización del inventario
     */
    public function valorizacion(Almacen $almacen)
    {
        // Verificar que pertenece a la misma empresa
        if ($almacen->empresa_id !== Auth::user()->empresa_id) {
            return response()->json([
                'message' => 'No autorizado'
            ], 403);
        }

        $productos = $almacen->productos()
            ->with(['producto'])
            ->where('stock_actual', '>', 0)
            ->get()
            ->map(function ($ap) {
                return [
                    'producto_id' => $ap->producto_id,
                    'codigo' => $ap->producto->codigo,
                    'nombre' => $ap->producto->nombre,
                    'stock_actual' => $ap->stock_actual,
                    'precio_promedio' => $ap->producto->precio_promedio,
                    'valor_total' => $ap->stock_actual * $ap->producto->precio_promedio,
                ];
            });

        $valorTotal = $productos->sum('valor_total');

        return response()->json([
            'almacen' => $almacen->nombre,
            'productos' => $productos,
            'valor_total' => $valorTotal
        ]);
    }

    /**
     * Comparar almacenes
     */
    public function comparar(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'almacen_ids' => 'required|array|min:2',
            'almacen_ids.*' => 'exists:almacenes,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $empresaId = Auth::user()->empresa_id;
        
        $almacenes = Almacen::whereIn('id', $request->almacen_ids)
            ->where('empresa_id', $empresaId)
            ->get();

        if ($almacenes->count() !== count($request->almacen_ids)) {
            return response()->json([
                'message' => 'Algunos almacenes no pertenecen a tu empresa'
            ], 403);
        }

        $comparacion = $almacenes->map(function ($almacen) {
            return [
                'id' => $almacen->id,
                'nombre' => $almacen->nombre,
                'ubicacion' => $almacen->ubicacion,
                'activo' => $almacen->activo,
                'total_productos' => $almacen->productos()->count(),
                'productos_con_stock' => $almacen->productos()->where('stock_actual', '>', 0)->count(),
                'stock_total' => $almacen->productos()->sum('stock_actual'),
                'valor_inventario' => $almacen->valorInventario(),
                'productos_bajo_stock' => $almacen->productosConBajoStock()->count(),
            ];
        });

        return response()->json([
            'data' => $comparacion
        ]);
    }

    /**
     * Limpiar caché del almacén
     */
    public function limpiarCache(int $almacenId = null)
    {
        if ($almacenId) {
            Cache::forget("almacen_{$almacenId}_valor_inventario");
            Cache::forget("almacen_{$almacenId}_bajo_stock");
            
            // Limpiar cache de stock de productos
            $almacen = Almacen::find($almacenId);
            if ($almacen) {
                $almacen->productos->each(function ($ap) use ($almacenId) {
                    Cache::forget("almacen_{$almacenId}_producto_{$ap->producto_id}_stock");
                });
            }
        }

        return response()->json([
            'message' => 'Caché limpiada exitosamente'
        ]);
    }

    /**
     * Importar almacenes
     */
    public function importar(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'almacenes' => 'required|array',
            'almacenes.*.nombre' => 'required|string|max:100',
            'almacenes.*.ubicacion' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $importados = 0;
            $errores = [];
            $empresaId = Auth::user()->empresa_id;

            foreach ($request->almacenes as $index => $almacenData) {
                try {
                    // Verificar si ya existe
                    $existe = Almacen::where('nombre', $almacenData['nombre'])
                        ->where('empresa_id', $empresaId)
                        ->exists();

                    if ($existe) {
                        $errores[] = [
                            'index' => $index,
                            'nombre' => $almacenData['nombre'],
                            'error' => 'El almacén ya existe'
                        ];
                        continue;
                    }

                    Almacen::create(array_merge($almacenData, [
                        'empresa_id' => $empresaId,
                        'activo' => true
                    ]));

                    $importados++;
                } catch (\Exception $e) {
                    $errores[] = [
                        'index' => $index,
                        'nombre' => $almacenData['nombre'] ?? 'N/A',
                        'error' => $e->getMessage()
                    ];
                }
            }

            return response()->json([
                'message' => "Se importaron {$importados} almacenes",
                'importados' => $importados,
                'errores' => $errores
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al importar almacenes',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}