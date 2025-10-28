<?php
// app/Http/Controllers/Api/CategoriaController.php
namespace App\Http\Controllers\Api\Inventario;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CategoriaController extends Controller
{
    public function index(): JsonResponse
    {
        $categorias = Categoria::where('activo', true)
            ->orderBy('nombre')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categorias,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255|unique:categorias,nombre',
            'codigo' => 'nullable|string|unique:categorias,codigo',
            'descripcion' => 'nullable|string',
            'imagen' => 'nullable|string',
        ]);

        $categoria = Categoria::create($validated);

        return response()->json([
            'success' => true,
            'data' => $categoria,
            'message' => 'Categoría creada correctamente'
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $categoria = Categoria::find($id);

        if (!$categoria) {
            return response()->json([
                'success' => false,
                'message' => 'Categoría no encontrada'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $categoria,
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $categoria = Categoria::find($id);

        if (!$categoria) {
            return response()->json([
                'success' => false,
                'message' => 'Categoría no encontrada'
            ], 404);
        }

        $validated = $request->validate([
            'nombre' => 'sometimes|string|max:255|unique:categorias,nombre,' . $id,
            'codigo' => 'nullable|string|unique:categorias,codigo,' . $id,
            'descripcion' => 'nullable|string',
            'imagen' => 'nullable|string',
        ]);

        $categoria->update($validated);

        return response()->json([
            'success' => true,
            'data' => $categoria,
            'message' => 'Categoría actualizada correctamente'
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $categoria = Categoria::find($id);

        if (!$categoria) {
            return response()->json([
                'success' => false,
                'message' => 'Categoría no encontrada'
            ], 404);
        }

        $categoria->update(['activo' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Categoría eliminada correctamente'
        ]);
    }
}