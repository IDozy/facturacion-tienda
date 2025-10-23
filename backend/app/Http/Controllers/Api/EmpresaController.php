<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class EmpresaController extends Controller
{
    /**
     * Obtener la empresa actual
     * GET /api/empresa
     */
    public function show(): JsonResponse
    {
        $empresa = Empresa::first();

        if (!$empresa) {
            return response()->json([
                'success' => false,
                'message' => 'No hay empresa configurada'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $empresa,
        ]);
    }

    /**
     * Actualizar empresa
     * PUT /api/empresa
     */
    public function update(Request $request): JsonResponse
    {
        $empresa = Empresa::first() ?? new Empresa();

        $validated = $request->validate([
            'ruc' => 'sometimes|string|size:11|unique:empresas,ruc,' . ($empresa->id ?? 'NULL'),
            'razon_social' => 'sometimes|string|max:255',
            'nombre_comercial' => 'nullable|string|max:255',
            'direccion' => 'sometimes|string|max:255',
            'ubigeo' => 'sometimes|string|size:6',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email',
            'web' => 'nullable|url',
            'usuario_sol' => 'nullable|string|max:50',
            'clave_sol' => 'nullable|string|max:50',
            'modo_prueba' => 'sometimes|boolean',
        ]);

        $empresa->update($validated);

        return response()->json([
            'success' => true,
            'data' => $empresa,
            'message' => 'Empresa actualizada correctamente'
        ]);
    }
}