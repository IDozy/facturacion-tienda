<?php

namespace App\Http\Controllers\Api\Inventario;

use App\Http\Controllers\Controller;
use App\Models\Inventario\Almacen;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AlmacenController extends Controller
{
    public function index(): JsonResponse
    {
        $almacenes = Almacen::where('activo', true)->get();

        return response()->json([
            'success' => true,
            'data' => $almacenes,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'codigo' => 'required|string|unique:almacenes,codigo',
            'descripcion' => 'nullable|string',
            'ubicacion' => 'nullable|string',
            'es_principal' => 'sometimes|boolean',
        ]);

        $almacen = Almacen::create($validated);

        return response()->json([
            'success' => true,
            'data' => $almacen,
            'message' => 'Almacén creado correctamente'
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $almacen = Almacen::find($id);

        if (!$almacen) {
            return response()->json([
                'success' => false,
                'message' => 'Almacén no encontrado'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $almacen,
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $almacen = Almacen::find($id);

        if (!$almacen) {
            return response()->json([
                'success' => false,
                'message' => 'Almacén no encontrado'
            ], 404);
        }

        $validated = $request->validate([
            'nombre' => 'sometimes|string|max:255',
            'descripcion' => 'nullable|string',
            'ubicacion' => 'nullable|string',
        ]);

        $almacen->update($validated);

        return response()->json([
            'success' => true,
            'data' => $almacen,
            'message' => 'Almacén actualizado correctamente'
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $almacen = Almacen::find($id);

        if (!$almacen) {
            return response()->json([
                'success' => false,
                'message' => 'Almacén no encontrado'
            ], 404);
        }

        $almacen->update(['activo' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Almacén eliminado correctamente'
        ]);
    }
}
