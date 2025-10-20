<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProductoController extends Controller
{
    /**
     * Listar todos los productos
     * GET /api/productos
     */
    public function index(): JsonResponse
    {
        $productos = Producto::where('activo', true)
            ->orderBy('descripcion')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $productos,
            'message' => 'Productos obtenidos correctamente'
        ]);
    }

    /**
     * Crear un nuevo producto
     * POST /api/productos
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'codigo' => 'required|string|max:50|unique:productos,codigo',
            'codigo_barras' => 'nullable|string',
            'descripcion' => 'required|string|max:255',
            'descripcion_larga' => 'nullable|string',
            'unidad_medida' => 'required|string|max:3',
            'precio_unitario' => 'required|numeric|min:0',
            'precio_venta' => 'required|numeric|min:0',
            'tipo_igv' => 'required|string|max:2',
            'porcentaje_igv' => 'required|numeric|min:0|max:100',
            'stock' => 'nullable|numeric|min:0',
            'stock_minimo' => 'nullable|numeric|min:0',
            'ubicacion' => 'nullable|string',
            'categoria' => 'nullable|string',
            'imagen' => 'nullable|string',
        ]);

        $producto = Producto::create($validated);

        return response()->json([
            'success' => true,
            'data' => $producto,
            'message' => 'Producto creado correctamente'
        ], 201);
    }

    /**
     * Ver un producto específico
     * GET /api/productos/{id}
     */
    public function show(string $id): JsonResponse
    {
        $producto = Producto::find($id);

        if (!$producto) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $producto,
            'message' => 'Producto obtenido correctamente'
        ]);
    }

    /**
     * Actualizar un producto
     * PUT/PATCH /api/productos/{id}
     */
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
            'codigo' => 'sometimes|string|max:50|unique:productos,codigo,' . $id,
            'codigo_barras' => 'nullable|string',
            'descripcion' => 'sometimes|string|max:255',
            'descripcion_larga' => 'nullable|string',
            'unidad_medida' => 'sometimes|string|max:3',
            'precio_unitario' => 'sometimes|numeric|min:0',
            'precio_venta' => 'sometimes|numeric|min:0',
            'tipo_igv' => 'sometimes|string|max:2',
            'porcentaje_igv' => 'sometimes|numeric|min:0|max:100',
            'stock' => 'nullable|numeric|min:0',
            'stock_minimo' => 'nullable|numeric|min:0',
            'ubicacion' => 'nullable|string',
            'categoria' => 'nullable|string',
            'imagen' => 'nullable|string',
            'activo' => 'sometimes|boolean',
        ]);

        $producto->update($validated);

        return response()->json([
            'success' => true,
            'data' => $producto,
            'message' => 'Producto actualizado correctamente'
        ]);
    }

    /**
     * Eliminar un producto (soft delete - marcar como inactivo)
     * DELETE /api/productos/{id}
     */
    public function destroy(string $id): JsonResponse
    {
        $producto = Producto::find($id);

        if (!$producto) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado'
            ], 404);
        }

        // Soft delete: solo marcamos como inactivo
        $producto->update(['activo' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Producto eliminado correctamente'
        ]);
    }

    /**
     * Buscar productos por código o descripción
     * GET /api/productos/buscar?q=laptop
     */
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
                  ->orWhere('codigo_barras', 'ILIKE', "%{$query}%")
                  ->orWhere('descripcion', 'ILIKE', "%{$query}%");
            })
            ->orderBy('descripcion')
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $productos,
            'message' => 'Búsqueda completada'
        ]);
    }
}
