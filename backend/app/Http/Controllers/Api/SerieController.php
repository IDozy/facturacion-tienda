<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Serie;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SerieController extends Controller
{
    /**
     * Listar todas las series
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
     * GET /api/series/tipo/01
     */
    public function porTipo(string $tipo): JsonResponse
    {
        $series = Serie::where('activo', true)
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
}
