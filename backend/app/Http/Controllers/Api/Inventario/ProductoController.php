<?php
// app/Http/Controllers/Api/ProductoController.php
namespace App\Http\Controllers\Api\Inventario;

use App\Http\Controllers\Controller;
use App\Models\Inventario\Producto;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProductoController extends Controller
{
    public function index(): JsonResponse
    {
        $productos = Producto::where('activo', true)
            ->with('categoria')
            ->orderBy('descripcion')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $productos,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'categoria_id' => 'nullable|exists:categorias,id',
            'codigo' => 'required|string|unique:productos,codigo',
            'codigo_barras' => 'nullable|string|unique:productos,codigo_barras',
            'descripcion' => 'required|string|max:255',
            'descripcion_larga' => 'nullable|string',
            'unidad_medida' => 'required|in:NIU,KGM,ZZ,BX,PR,DOC,HR,MIN',
            'precio_costo' => 'nullable|numeric|min:0',
            'precio_unitario' => 'required|numeric|min:0',
            'precio_venta' => 'required|numeric|min:0',
            'tipo_igv' => 'required|in:10,20,30',
            'stock' => 'nullable|integer|min:0',
            'stock_minimo' => 'nullable|integer|min:0',
            'ubicacion' => 'nullable|string',
        ]);

        $producto = Producto::create($validated);

        return response()->json([
            'success' => true,
            'data' => $producto,
            'message' => 'Producto creado correctamente'
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $producto = Producto::with('categoria')->find($id);

        if (!$producto) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $producto,
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $producto = Producto::find($id);

        if (!$producto) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado'
            ], 404);
        }

        $validated = $request->validate([
            'descripcion' => 'sometimes|string|max:255',
            'descripcion_larga' => 'nullable|string',
            'precio_unitario' => 'sometimes|numeric|min:0',
            'precio_venta' => 'sometimes|numeric|min:0',
            'stock' => 'sometimes|integer|min:0',
            'stock_minimo' => 'sometimes|integer|min:0',
            'activo' => 'sometimes|boolean',
        ]);

        $producto->update($validated);

        return response()->json([
            'success' => true,
            'data' => $producto,
            'message' => 'Producto actualizado correctamente'
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $producto = Producto::find($id);

        if (!$producto) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado'
            ], 404);
        }

        $producto->update(['activo' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Producto eliminado correctamente'
        ]);
    }

    public function buscar(Request $request): JsonResponse
    {
        $query = $request->input('q');

        if (!$query) {
            return response()->json([
                'success' => false,
                'message' => 'Debe proporcionar un término de búsqueda'
            ], 400);
        }

        $productos = Producto::where('activo', true)
            ->where(function($q) use ($query) {
                $q->where('codigo', 'ILIKE', "%{$query}%")
                  ->orWhere('descripcion', 'ILIKE', "%{$query}%")
                  ->orWhere('codigo_barras', 'ILIKE', "%{$query}%");
            })
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $productos,
        ]);
    }
}