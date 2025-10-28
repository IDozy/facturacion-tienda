<?php

namespace App\Http\Controllers\Api\Contabilidad;

use App\Http\Controllers\Controller;
use App\Models\Asiento;
use App\Models\AsientoDetalle;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use DB;

class AsientoController extends Controller
{
    public function index(): JsonResponse
    {
        $asientos = Asiento::with('usuario', 'detalles')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $asientos,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'diario' => 'required|string',
            'fecha_asiento' => 'required|date',
            'descripcion' => 'nullable|string',
            'detalles' => 'required|array|min:2',
            'detalles.*.cuenta_id' => 'required|exists:plan_cuentas,id',
            'detalles.*.descripcion' => 'required|string',
            'detalles.*.debe' => 'nullable|numeric|min:0',
            'detalles.*.haber' => 'nullable|numeric|min:0',
        ]);

        try {
            return DB::transaction(function () use ($validated) {
                $totalDebe = 0;
                $totalHaber = 0;

                foreach ($validated['detalles'] as $detalle) {
                    $totalDebe += $detalle['debe'] ?? 0;
                    $totalHaber += $detalle['haber'] ?? 0;
                }

                // Validar que debe = haber
                if (abs($totalDebe - $totalHaber) > 0.01) {
                    return response()->json([
                        'success' => false,
                        'message' => 'El total de débitos debe ser igual al total de créditos'
                    ], 422);
                }

                $asiento = Asiento::create([
                    'usuario_id' => auth()->id(),
                    'numero_asiento' => 'ASI-' . date('YmdHis'),
                    'diario' => $validated['diario'],
                    'fecha_asiento' => $validated['fecha_asiento'],
                    'descripcion' => $validated['descripcion'],
                    'estado' => 'borrador',
                    'total_debe' => $totalDebe,
                    'total_haber' => $totalHaber,
                ]);

                foreach ($validated['detalles'] as $index => $detalle) {
                    AsientoDetalle::create([
                        'asiento_id' => $asiento->id,
                        'cuenta_id' => $detalle['cuenta_id'],
                        'item' => $index + 1,
                        'descripcion' => $detalle['descripcion'],
                        'debe' => $detalle['debe'] ?? 0,
                        'haber' => $detalle['haber'] ?? 0,
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'data' => $asiento->load('detalles'),
                    'message' => 'Asiento creado correctamente'
                ], 201);
            });
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function show(string $id): JsonResponse
    {
        $asiento = Asiento::with('usuario', 'detalles')->find($id);

        if (!$asiento) {
            return response()->json([
                'success' => false,
                'message' => 'Asiento no encontrado'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $asiento,
        ]);
    }

    public function registrar(string $id): JsonResponse
    {
        $asiento = Asiento::find($id);

        if (!$asiento) {
            return response()->json([
                'success' => false,
                'message' => 'Asiento no encontrado'
            ], 404);
        }

        $asiento->update(['estado' => 'registrado']);

        return response()->json([
            'success' => true,
            'message' => 'Asiento registrado correctamente'
        ]);
    }

    public function anular(string $id): JsonResponse
    {
        $asiento = Asiento::find($id);

        if (!$asiento) {
            return response()->json([
                'success' => false,
                'message' => 'Asiento no encontrado'
            ], 404);
        }

        $asiento->update(['estado' => 'anulado']);

        return response()->json([
            'success' => true,
            'message' => 'Asiento anulado correctamente'
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
{
    $asiento = Asiento::find($id);

    if (!$asiento) {
        return response()->json([
            'success' => false,
            'message' => 'Asiento no encontrado'
        ], 404);
    }

    // Solo permitir editar si está en borrador
    if ($asiento->estado !== 'borrador') {
        return response()->json([
            'success' => false,
            'message' => 'Solo se pueden editar asientos en estado "borrador"'
        ], 422);
    }

    $validated = $request->validate([
        'descripcion' => 'nullable|string',
        'detalles' => 'array|min:2',
        'detalles.*.cuenta_id' => 'required|exists:plan_cuentas,id',
        'detalles.*.descripcion' => 'required|string',
        'detalles.*.debe' => 'nullable|numeric|min:0',
        'detalles.*.haber' => 'nullable|numeric|min:0',
    ]);

    return DB::transaction(function () use ($asiento, $validated) {
        $asiento->update([
            'descripcion' => $validated['descripcion'] ?? $asiento->descripcion,
        ]);

        if (!empty($validated['detalles'])) {
            $asiento->detalles()->delete();

            foreach ($validated['detalles'] as $index => $detalle) {
                AsientoDetalle::create([
                    'asiento_id' => $asiento->id,
                    'cuenta_id' => $detalle['cuenta_id'],
                    'item' => $index + 1,
                    'descripcion' => $detalle['descripcion'],
                    'debe' => $detalle['debe'] ?? 0,
                    'haber' => $detalle['haber'] ?? 0,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Asiento actualizado correctamente',
            'data' => $asiento->load('detalles')
        ]);
    });
}

public function destroy(string $id): JsonResponse
{
    $asiento = Asiento::find($id);

    if (!$asiento) {
        return response()->json([
            'success' => false,
            'message' => 'Asiento no encontrado'
        ], 404);
    }

    if ($asiento->estado === 'registrado') {
        return response()->json([
            'success' => false,
            'message' => 'No se puede eliminar un asiento registrado'
        ], 422);
    }

    $asiento->delete();

    return response()->json([
        'success' => true,
        'message' => 'Asiento eliminado correctamente'
    ]);
}

}
