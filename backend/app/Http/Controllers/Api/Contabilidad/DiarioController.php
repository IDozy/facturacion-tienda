<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asiento;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DiarioController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $desde = $request->input('desde', now()->startOfMonth());
        $hasta = $request->input('hasta', now()->endOfMonth());

        $asientos = Asiento::with('detalles.cuenta')
            ->whereBetween('fecha_asiento', [$desde, $hasta])
            ->orderBy('fecha_asiento', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $asientos,
        ]);
    }
}
