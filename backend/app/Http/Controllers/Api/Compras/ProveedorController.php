<?php

namespace App\Http\Controllers\Api\Compras;

use App\Http\Controllers\Controller;
use App\Models\Compras\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProveedorController extends Controller
{
    public function index(): JsonResponse
    {
        $proveedores = Proveedor::where('activo', true)
            ->orderBy('nombre_razon_social')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $proveedores,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tipo_documento' => 'required|string|size:1|in:1,4,6,7',
            'numero_documento' => 'required|string|unique:proveedores,numero_documento',
            'nombre_razon_social' => 'required|string|max:255',
            'nombre_comercial' => 'nullable|string|max:255',
            'direccion' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email',
            'contacto' => 'nullable|string|max:100',
        ]);

        $proveedor = Proveedor::create($validated);

        return response()->json([
            'success' => true,
            'data' => $proveedor,
            'message' => 'Proveedor creado correctamente'
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $proveedor = Proveedor::find($id);

        if (!$proveedor) {
            return response()->json([
                'success' => false,
                'message' => 'Proveedor no encontrado'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $proveedor,
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $proveedor = Proveedor::find($id);

        if (!$proveedor) {
            return response()->json([
                'success' => false,
                'message' => 'Proveedor no encontrado'
            ], 404);
        }

        $validated = $request->validate([
            'nombre_razon_social' => 'sometimes|string|max:255',
            'nombre_comercial' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email',
            'contacto' => 'nullable|string|max:100',
            'activo' => 'sometimes|boolean',
        ]);

        $proveedor->update($validated);

        return response()->json([
            'success' => true,
            'data' => $proveedor,
            'message' => 'Proveedor actualizado correctamente'
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $proveedor = Proveedor::find($id);

        if (!$proveedor) {
            return response()->json([
                'success' => false,
                'message' => 'Proveedor no encontrado'
            ], 404);
        }

        $proveedor->update(['activo' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Proveedor eliminado correctamente'
        ]);
    }
}