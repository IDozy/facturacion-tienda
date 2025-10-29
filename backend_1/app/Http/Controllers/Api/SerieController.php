<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Serie;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SerieController extends Controller
{
    /**
     * Listar todas las series activas con su empresa
     * GET /api/series
     */
    public function index(): JsonResponse
    {
        $series = Serie::with('empresa')
            ->where('activo', true)
            ->orderBy('tipo_comprobante')
            ->orderBy('serie')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $series,
            'message' => 'Series obtenidas correctamente'
        ]);
    }

    /**
     * Obtener series por tipo de comprobante
     * GET /api/series/tipo/{tipo}
     */
    public function porTipo(string $tipo): JsonResponse
    {
        $series = Serie::with('empresa')
            ->where('activo', true)
            ->where('tipo_comprobante', $tipo)
            ->orderBy('serie')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $series,
            'message' => 'Series obtenidas correctamente'
        ]);
    }

    /**
     * Ver una serie específica
     * GET /api/series/{id}
     */
    public function show(string $id): JsonResponse
    {
        $serie = Serie::with('empresa')->find($id);

        if (!$serie) {
            return response()->json([
                'success' => false,
                'message' => 'Serie no encontrada'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $serie,
            'message' => 'Serie obtenida correctamente'
        ]);
    }

    /**
     * Crear una nueva serie
     * POST /api/series
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tipo_comprobante' => 'required|in:01,03,07,08,09,31',
            'serie' => 'required|string|unique:series,serie',
            'descripcion' => 'nullable|string',
            'por_defecto' => 'sometimes|boolean',
        ]);

        $validated['correlativo_actual'] = 0;
        $serie = Serie::create($validated);

        return response()->json([
            'success' => true,
            'data' => $serie,
            'message' => 'Serie creada correctamente'
        ], 201);
    }

    /**
     * Actualizar una serie existente
     * PUT/PATCH /api/series/{id}
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $serie = Serie::find($id);

        if (!$serie) {
            return response()->json([
                'success' => false,
                'message' => 'Serie no encontrada'
            ], 404);
        }

        $validated = $request->validate([
            'tipo_comprobante' => 'sometimes|in:01,03,07,08,09,31',
            'serie' => 'sometimes|string|unique:series,serie,' . $id,
            'descripcion' => 'nullable|string',
            'por_defecto' => 'sometimes|boolean',
            'activo' => 'sometimes|boolean',
        ]);

        $serie->update($validated);

        return response()->json([
            'success' => true,
            'data' => $serie,
            'message' => 'Serie actualizada correctamente'
        ]);
    }

    /**
     * Eliminar (soft delete) una serie
     * DELETE /api/series/{id}
     */
    public function destroy(string $id): JsonResponse
    {
        $serie = Serie::find($id);

        if (!$serie) {
            return response()->json([
                'success' => false,
                'message' => 'Serie no encontrada'
            ], 404);
        }

        $serie->update(['activo' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Serie eliminada correctamente'
        ]);
    }
}
