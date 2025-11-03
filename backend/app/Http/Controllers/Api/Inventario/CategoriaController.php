<?php

namespace App\Http\Controllers\Api\Inventario;

use App\Http\Controllers\Controller;
use App\Models\Inventario\Categoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CategoriaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Categoria::with(['empresa'])
            ->withCount('productos');

        // Filtros
        if ($request->has('con_productos')) {
            $query->conProductos();
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
            $categorias = $query->get();
            return response()->json(['data' => $categorias]);
        }

        $categorias = $query->paginate($perPage);

        return response()->json($categorias);
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
                Rule::unique('categorias')->where(function ($query) use ($empresaId) {
                    return $query->where('empresa_id', $empresaId);
                })
            ],
            'descripcion' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $categoria = Categoria::create($request->all());

            return response()->json([
                'message' => 'Categoría creada exitosamente',
                'data' => $categoria->load('empresa')
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear la categoría',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Categoria $categoria)
    {
        // Verificar que pertenece a la misma empresa
        if ($categoria->empresa_id !== Auth::user()->empresa_id) {
            return response()->json([
                'message' => 'No autorizado'
            ], 403);
        }

        return response()->json([
            'data' => $categoria->load(['empresa', 'productos' => function ($query) {
                $query->activos()->limit(10);
            }]),
            'cantidad_productos' => $categoria->cantidadProductos()
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Categoria $categoria)
    {
        // Verificar que pertenece a la misma empresa
        if ($categoria->empresa_id !== Auth::user()->empresa_id) {
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
                Rule::unique('categorias')->ignore($categoria->id)->where(function ($query) use ($empresaId) {
                    return $query->where('empresa_id', $empresaId);
                })
            ],
            'descripcion' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $categoria->update($request->all());

            return response()->json([
                'message' => 'Categoría actualizada exitosamente',
                'data' => $categoria->fresh(['empresa'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar la categoría',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Categoria $categoria)
    {
        // Verificar que pertenece a la misma empresa
        if ($categoria->empresa_id !== Auth::user()->empresa_id) {
            return response()->json([
                'message' => 'No autorizado'
            ], 403);
        }

        if (!$categoria->puedeEliminar()) {
            return response()->json([
                'message' => 'No se puede eliminar la categoría porque tiene productos asociados',
                'cantidad_productos' => $categoria->cantidadProductos()
            ], 400);
        }

        try {
            $categoria->delete();

            return response()->json([
                'message' => 'Categoría eliminada exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar la categoría',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener productos de una categoría
     */
    public function productos(Categoria $categoria, Request $request)
    {
        // Verificar que pertenece a la misma empresa
        if ($categoria->empresa_id !== Auth::user()->empresa_id) {
            return response()->json([
                'message' => 'No autorizado'
            ], 403);
        }

        $query = $categoria->productos();

        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->has('search')) {
            $query->buscar($request->search);
        }

        $productos = $query->orderBy('nombre')
            ->paginate($request->get('per_page', 15));

        return response()->json($productos);
    }

    /**
     * Estadísticas de la categoría
     */
    public function estadisticas(Categoria $categoria)
    {
        // Verificar que pertenece a la misma empresa
        if ($categoria->empresa_id !== Auth::user()->empresa_id) {
            return response()->json([
                'message' => 'No autorizado'
            ], 403);
        }

        $totalProductos = $categoria->cantidadProductos();
        $productosActivos = $categoria->productos()->where('estado', 'activo')->count();
        $productosInactivos = $categoria->productos()->where('estado', 'inactivo')->count();

        $stockTotal = $categoria->productos()->get()->sum('stock_actual');
        
        $valorInventario = $categoria->productos()->get()->sum(function ($producto) {
            return $producto->stock_actual * $producto->precio_promedio;
        });

        return response()->json([
            'total_productos' => $totalProductos,
            'productos_activos' => $productosActivos,
            'productos_inactivos' => $productosInactivos,
            'stock_total' => $stockTotal,
            'valor_inventario' => $valorInventario
        ]);
    }

    /**
     * Categorías con más productos
     */
    public function conMasProductos(Request $request)
    {
        $limit = $request->get('limit', 10);

        $categorias = Categoria::where('empresa_id', Auth::user()->empresa_id)
            ->withCount('productos')
            ->orderBy('productos_count', 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'data' => $categorias
        ]);
    }

    /**
     * Categorías sin productos
     */
    public function sinProductos()
    {
        $categorias = Categoria::where('empresa_id', Auth::user()->empresa_id)
            ->whereDoesntHave('productos')
            ->orderBy('nombre')
            ->get();

        return response()->json([
            'data' => $categorias,
            'count' => $categorias->count()
        ]);
    }

    /**
     * Verificar si puede eliminar
     */
    public function verificarEliminacion(Categoria $categoria)
    {
        // Verificar que pertenece a la misma empresa
        if ($categoria->empresa_id !== Auth::user()->empresa_id) {
            return response()->json([
                'message' => 'No autorizado'
            ], 403);
        }

        $puedeEliminar = $categoria->puedeEliminar();

        return response()->json([
            'puede_eliminar' => $puedeEliminar,
            'tiene_productos' => $categoria->tieneProductos(),
            'cantidad_productos' => $categoria->cantidadProductos(),
            'mensaje' => $puedeEliminar 
                ? 'La categoría puede ser eliminada' 
                : "La categoría tiene {$categoria->cantidadProductos()} producto(s) asociado(s)"
        ]);
    }

    /**
     * Mover productos a otra categoría
     */
    public function moverProductos(Request $request, Categoria $categoria)
    {
        // Verificar que pertenece a la misma empresa
        if ($categoria->empresa_id !== Auth::user()->empresa_id) {
            return response()->json([
                'message' => 'No autorizado'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'categoria_destino_id' => 'required|exists:categorias,id|different:' . $categoria->id,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        // Verificar que la categoría destino pertenece a la misma empresa
        $categoriaDestino = Categoria::find($request->categoria_destino_id);
        if ($categoriaDestino->empresa_id !== Auth::user()->empresa_id) {
            return response()->json([
                'message' => 'La categoría destino no pertenece a tu empresa'
            ], 403);
        }

        try {
            $cantidad = $categoria->productos()->update([
                'categoria_id' => $request->categoria_destino_id
            ]);

            return response()->json([
                'message' => "Se movieron {$cantidad} producto(s) a la categoría {$categoriaDestino->nombre}",
                'cantidad' => $cantidad
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al mover los productos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Importar categorías
     */
    public function importar(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'categorias' => 'required|array',
            'categorias.*.nombre' => 'required|string|max:100',
            'categorias.*.descripcion' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $importadas = 0;
            $errores = [];
            $empresaId = Auth::user()->empresa_id;

            foreach ($request->categorias as $index => $categoriaData) {
                try {
                    // Verificar si ya existe
                    $existe = Categoria::where('nombre', $categoriaData['nombre'])
                        ->where('empresa_id', $empresaId)
                        ->exists();

                    if ($existe) {
                        $errores[] = [
                            'index' => $index,
                            'nombre' => $categoriaData['nombre'],
                            'error' => 'La categoría ya existe'
                        ];
                        continue;
                    }

                    Categoria::create(array_merge($categoriaData, [
                        'empresa_id' => $empresaId
                    ]));

                    $importadas++;
                } catch (\Exception $e) {
                    $errores[] = [
                        'index' => $index,
                        'nombre' => $categoriaData['nombre'] ?? 'N/A',
                        'error' => $e->getMessage()
                    ];
                }
            }

            return response()->json([
                'message' => "Se importaron {$importadas} categorías",
                'importadas' => $importadas,
                'errores' => $errores
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al importar categorías',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}