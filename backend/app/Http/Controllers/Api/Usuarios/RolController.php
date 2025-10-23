<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Rol;
use App\Models\Permiso;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RolController extends Controller
{
    public function index(): JsonResponse
    {
        $roles = Rol::where('activo', true)
            ->with('permisos')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $roles,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nombre' => 'required|string|unique:roles,nombre',
            'descripcion' => 'nullable|string',
            'permisos' => 'nullable|array',
            'permisos.*' => 'exists:permisos,id',
        ]);

        $rol = Rol::create([
            'nombre' => $validated['nombre'],
            'descripcion' => $validated['descripcion'] ?? null,
            'activo' => true,
        ]);

        if (isset($validated['permisos'])) {
            $rol->permisos()->sync($validated['permisos']);
        }

        return response()->json([
            'success' => true,
            'data' => $rol->load('permisos'),
            'message' => 'Rol creado correctamente'
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $rol = Rol::with('permisos', 'usuarios')->find($id);

        if (!$rol) {
            return response()->json([
                'success' => false,
                'message' => 'Rol no encontrado'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $rol,
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $rol = Rol::find($id);

        if (!$rol) {
            return response()->json([
                'success' => false,
                'message' => 'Rol no encontrado'
            ], 404);
        }

        $validated = $request->validate([
            'nombre' => 'sometimes|string|unique:roles,nombre,' . $id,
            'descripcion' => 'nullable|string',
            'permisos' => 'nullable|array',
            'permisos.*' => 'exists:permisos,id',
            'activo' => 'sometimes|boolean',
        ]);

        $rol->update($validated);

        if (isset($validated['permisos'])) {
            $rol->permisos()->sync($validated['permisos']);
        }

        return response()->json([
            'success' => true,
            'data' => $rol->load('permisos'),
            'message' => 'Rol actualizado correctamente'
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $rol = Rol::find($id);

        if (!$rol) {
            return response()->json([
                'success' => false,
                'message' => 'Rol no encontrado'
            ], 404);
        }

        $rol->update(['activo' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Rol eliminado correctamente'
        ]);
    }

    public function asignarPermisos(Request $request, string $id): JsonResponse
    {
        $rol = Rol::find($id);

        if (!$rol) {
            return response()->json([
                'success' => false,
                'message' => 'Rol no encontrado'
            ], 404);
        }

        $validated = $request->validate([
            'permisos' => 'required|array',
            'permisos.*' => 'exists:permisos,id',
        ]);

        $rol->permisos()->sync($validated['permisos']);

        return response()->json([
            'success' => true,
            'data' => $rol->load('permisos'),
            'message' => 'Permisos asignados correctamente'
        ]);
    }
}