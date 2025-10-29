<?php
// app/Http/Controllers/Api/Inventario/ProductoController.php

namespace App\Http\Controllers\Api\Inventario;

use App\Http\Controllers\Controller;
use App\Models\Inventario\Producto;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ProductoController extends Controller
{
    // 📋 Listado general con paginación
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 25);

        $productos = Producto::with('categoria')
            ->where('activo', true)
            ->orderBy('descripcion')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $productos->items(),
            'meta' => [
                'total' => $productos->total(),
                'current_page' => $productos->currentPage(),
                'last_page' => $productos->lastPage()
            ]
        ]);
    }

    // 🆕 Crear producto
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'categoria_id' => 'nullable|exists:categorias,id',
            'codigo' => 'required|string|max:50|unique:productos,codigo',
            'codigo_barras' => 'nullable|string|unique:productos,codigo_barras',
            'codigo_sunat' => 'nullable|string|max:20',
            'descripcion' => 'required|string|max:255',
            'descripcion_larga' => 'nullable|string',
            'unidad_medida' => 'required|in:NIU,KGM,ZZ,BX,PR,DOC,HR,MIN',
            'precio_costo' => 'nullable|numeric|min:0',
            'precio_unitario' => 'required|numeric|min:0',
            'precio_venta' => 'required|numeric|min:0',
            'tipo_igv' => 'required|in:10,20,30',
            'porcentaje_igv' => 'numeric|min:0|max:100',
            'stock' => 'nullable|numeric|min:0',
            'stock_minimo' => 'nullable|numeric|min:0',
            'ubicacion' => 'nullable|string|max:100',
            'imagen' => 'nullable|string',
        ]);

        try {
            $producto = Producto::create($validated);

            return response()->json([
                'success' => true,
                'data' => $producto,
                'message' => 'Producto creado correctamente'
            ], 201);

        } catch (\Throwable $e) {
            Log::error('Error al crear producto: '.$e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al crear el producto'
            ], 500);
        }
    }

    // 🔍 Mostrar detalle
    public function show(string $id): JsonResponse
    {
        try {
            $producto = Producto::with('categoria')->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $producto,
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado'
            ], 404);
        }
    }

    // ✏️ Actualizar producto
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $producto = Producto::findOrFail($id);

            $validated = $request->validate([
                'descripcion' => 'sometimes|string|max:255',
                'descripcion_larga' => 'nullable|string',
                'precio_unitario' => 'sometimes|numeric|min:0',
                'precio_venta' => 'sometimes|numeric|min:0',
                'porcentaje_igv' => 'sometimes|numeric|min:0|max:100',
                'stock' => 'sometimes|numeric|min:0',
                'stock_minimo' => 'sometimes|numeric|min:0',
                'activo' => 'sometimes|boolean',
            ]);

            $producto->update($validated);

            return response()->json([
                'success' => true,
                'data' => $producto,
                'message' => 'Producto actualizado correctamente'
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado'
            ], 404);
        }
    }

    // 🗑️ Eliminar (soft delete)
    public function destroy(string $id): JsonResponse
    {
        try {
            $producto = Producto::findOrFail($id);
            $producto->delete();

            return response()->json([
                'success' => true,
                'message' => 'Producto eliminado correctamente'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado'
            ], 404);
        }
    }

    // 🔎 Buscar por código, descripción o barras
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
            ->where(function ($q) use ($query) {
                $q->where('codigo', 'like', "%{$query}%")
                  ->orWhere('descripcion', 'like', "%{$query}%")
                  ->orWhere('codigo_barras', 'like', "%{$query}%");
            })
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $productos,
        ]);
    }
}
