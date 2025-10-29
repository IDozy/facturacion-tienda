<?php

namespace App\Http\Controllers\Api\Compras;

use App\Http\Controllers\Controller;
use App\Models\Compras\Recepcion;
use App\Models\Compras\RecepcionDetalle;
use App\Models\Compras\Compra;
use App\Models\Inventario\Producto;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class RecepcionController extends Controller
{
    /**
     * Listar recepciones con filtros y paginación
     * GET /api/recepciones
     */
    public function index(Request $request): JsonResponse
    {
        $query = Recepcion::with(['compra', 'almacen', 'usuario', 'detalles.producto'])
            ->orderBy('fecha_recepcion', 'desc')
            ->orderBy('numero_recepcion', 'desc');

        if ($request->filled('compra_id')) {
            $query->where('compra_id', $request->compra_id);
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('fecha_desde')) {
            $query->where('fecha_recepcion', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->where('fecha_recepcion', '<=', $request->fecha_hasta);
        }

        $recepciones = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $recepciones,
            'message' => 'Recepciones obtenidas correctamente'
        ]);
    }

    /**
     * Ver una recepción específica
     * GET /api/recepciones/{id}
     */
    public function show(string $id): JsonResponse
    {
        $recepcion = Recepcion::with(['compra', 'almacen', 'usuario', 'detalles.producto'])->find($id);

        if (!$recepcion) {
            return response()->json([
                'success' => false,
                'message' => 'Recepción no encontrada'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $recepcion,
            'message' => 'Recepción obtenida correctamente'
        ]);
    }

    /**
     * Crear una recepción
     * POST /api/recepciones
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'compra_id' => 'required|exists:compras,id',
            'almacen_id' => 'required|exists:almacenes,id',
            'numero_recepcion' => 'required|string|unique:recepciones,numero_recepcion',
            'fecha_recepcion' => 'required|date',
            'observaciones' => 'nullable|string',
            'detalles' => 'required|array|min:1',
            'detalles.*.producto_id' => 'required|exists:productos,id',
            'detalles.*.cantidad' => 'required|numeric|min:0.01',
        ]);

        try {
            DB::beginTransaction();

            $recepcion = Recepcion::create([
                'compra_id' => $validated['compra_id'],
                'almacen_id' => $validated['almacen_id'],
                'usuario_id' => auth()->id(),
                'numero_recepcion' => $validated['numero_recepcion'],
                'fecha_recepcion' => $validated['fecha_recepcion'],
                'estado' => 'pendiente',
                'observaciones' => $validated['observaciones'] ?? null,
            ]);

            foreach ($validated['detalles'] as $index => $detalle) {
                $producto = Producto::findOrFail($detalle['producto_id']);
                $cantidad = $detalle['cantidad'];

                RecepcionDetalle::create([
                    'recepcion_id' => $recepcion->id,
                    'producto_id' => $producto->id,
                    'item' => $index + 1,
                    'cantidad' => $cantidad,
                ]);

                // Aumentar stock en el almacén
                $producto->increment('stock', $cantidad);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $recepcion->load(['compra', 'almacen', 'usuario', 'detalles.producto']),
                'message' => 'Recepción creada correctamente'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la recepción: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Anular una recepción
     * POST /api/recepciones/{id}/anular
     */
    public function anular(string $id): JsonResponse
    {
        $recepcion = Recepcion::find($id);

        if (!$recepcion) {
            return response()->json([
                'success' => false,
                'message' => 'Recepción no encontrada'
            ], 404);
        }

        if ($recepcion->estado === 'anulado') {
            return response()->json([
                'success' => false,
                'message' => 'La recepción ya está anulada'
            ], 422);
        }

        $recepcion->update(['estado' => 'anulado']);

        return response()->json([
            'success' => true,
            'data' => $recepcion,
            'message' => 'Recepción anulada correctamente'
        ]);
    }
}
