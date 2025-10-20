<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ClienteController extends Controller
{
    /**
     * Listar todos los clientes
     * GET /api/clientes
     */
    public function index(): JsonResponse
    {
        $clientes = Cliente::where('activo', true)
            ->orderBy('nombre_razon_social')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $clientes,
            'message' => 'Clientes obtenidos correctamente'
        ]);
    }

    /**
     * Crear un nuevo cliente
     * POST /api/clientes
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tipo_documento' => 'required|string|size:1|in:1,4,6,7',
            'numero_documento' => 'required|string|max:15',
            'nombre_razon_social' => 'required|string|max:255',
            'nombre_comercial' => 'nullable|string|max:255',
            'direccion' => 'nullable|string|max:255',
            'distrito' => 'nullable|string|max:100',
            'provincia' => 'nullable|string|max:100',
            'departamento' => 'nullable|string|max:100',
            'ubigeo' => 'nullable|string|size:6',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
        ]);

        // Verificar si ya existe
        $existe = Cliente::where('tipo_documento', $validated['tipo_documento'])
            ->where('numero_documento', $validated['numero_documento'])
            ->first();

        if ($existe) {
            return response()->json([
                'success' => false,
                'message' => 'El cliente ya existe'
            ], 422);
        }

        $cliente = Cliente::create($validated);

        return response()->json([
            'success' => true,
            'data' => $cliente,
            'message' => 'Cliente creado correctamente'
        ], 201);
    }

    /**
     * Ver un cliente específico
     * GET /api/clientes/{id}
     */
    public function show(string $id): JsonResponse
    {
        $cliente = Cliente::find($id);

        if (!$cliente) {
            return response()->json([
                'success' => false,
                'message' => 'Cliente no encontrado'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $cliente,
            'message' => 'Cliente obtenido correctamente'
        ]);
    }

    /**
     * Actualizar un cliente
     * PUT/PATCH /api/clientes/{id}
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $cliente = Cliente::find($id);

        if (!$cliente) {
            return response()->json([
                'success' => false,
                'message' => 'Cliente no encontrado'
            ], 404);
        }

        $validated = $request->validate([
            'tipo_documento' => 'sometimes|string|size:1|in:1,4,6,7',
            'numero_documento' => 'sometimes|string|max:15',
            'nombre_razon_social' => 'sometimes|string|max:255',
            'nombre_comercial' => 'nullable|string|max:255',
            'direccion' => 'nullable|string|max:255',
            'distrito' => 'nullable|string|max:100',
            'provincia' => 'nullable|string|max:100',
            'departamento' => 'nullable|string|max:100',
            'ubigeo' => 'nullable|string|size:6',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'activo' => 'sometimes|boolean',
        ]);

        $cliente->update($validated);

        return response()->json([
            'success' => true,
            'data' => $cliente,
            'message' => 'Cliente actualizado correctamente'
        ]);
    }

    /**
     * Eliminar un cliente
     * DELETE /api/clientes/{id}
     */
    public function destroy(string $id): JsonResponse
    {
        $cliente = Cliente::find($id);

        if (!$cliente) {
            return response()->json([
                'success' => false,
                'message' => 'Cliente no encontrado'
            ], 404);
        }

        $cliente->update(['activo' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Cliente eliminado correctamente'
        ]);
    }

    /**
     * Buscar clientes por documento o nombre
     * GET /api/clientes/buscar?q=20123456789
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

        $clientes = Cliente::where('activo', true)
            ->where(function($q) use ($query) {
                $q->where('numero_documento', 'ILIKE', "%{$query}%")
                  ->orWhere('nombre_razon_social', 'ILIKE', "%{$query}%")
                  ->orWhere('nombre_comercial', 'ILIKE', "%{$query}%");
            })
            ->orderBy('nombre_razon_social')
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $clientes,
            'message' => 'Búsqueda completada'
        ]);
    }
}
