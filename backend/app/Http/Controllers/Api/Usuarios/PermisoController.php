<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permiso;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PermisoController extends Controller
{
    public function index(): JsonResponse
    {
        $permisos = Permiso::orderBy('modulo')->get();

        return response()->json([
            'success' => true,
            'data' => $permisos,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nombre' => 'required|string|unique:permisos,nombre',
            'descripcion' => 'nullable|string',
            'modulo' => 'required|string',
        ]);

        $permiso = Permiso::create($validated);

        return response()->json([
            'success' => true,
            'data' => $permiso,
            'message' => 'Permiso creado correctamente'
        ], 201);
    }

    public function porModulo(string $modulo): JsonResponse
    {
        $permisos = Permiso::where('modulo', $modulo)->get();

        return response()->json([
            'success' => true,
            'data' => $permisos,
        ]);
    }
}