<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class EmpresaController extends Controller
{
    /**
     * Obtener la empresa actual del sistema
     * GET /api/empresa
     */
    public function index(): JsonResponse
    {
        $empresa = Empresa::where('activo', true)->first();

        if (!$empresa) {
            return response()->json([
                'success' => false,
                'message' => 'No hay empresa configurada'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $empresa,
            'message' => 'Empresa obtenida correctamente'
        ]);
    }

    /**
     * Actualizar datos de la empresa
     * PUT /api/empresa
     */
    public function update(Request $request): JsonResponse
    {
        $empresa = Empresa::where('activo', true)->first();

        if (!$empresa) {
            return response()->json([
                'success' => false,
                'message' => 'No hay empresa configurada'
            ], 404);
        }

        $validated = $request->validate([
            'ruc' => 'sometimes|string|size:11',
            'razon_social' => 'sometimes|string|max:255',
            'nombre_comercial' => 'nullable|string|max:255',
            'direccion' => 'sometimes|string|max:255',
            'urbanizacion' => 'nullable|string|max:255',
            'distrito' => 'sometimes|string|max:100',
            'provincia' => 'sometimes|string|max:100',
            'departamento' => 'sometimes|string|max:100',
            'ubigeo' => 'sometimes|string|size:6',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'web' => 'nullable|url|max:255',
            'usuario_sol' => 'nullable|string|max:50',
            'clave_sol' => 'nullable|string|max:50',
            'modo_prueba' => 'sometimes|boolean',
            'logo' => 'nullable|string',
        ]);

        $empresa->update($validated);

        return response()->json([
            'success' => true,
            'data' => $empresa,
            'message' => 'Empresa actualizada correctamente'
        ]);
    }
}
