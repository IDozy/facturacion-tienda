<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Retencion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class RetencionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Retencion::with(['comprobante']);

        // Filtros
        if ($request->has('tipo')) {
            if ($request->tipo === 'retencion') {
                $query->retenciones();
            } elseif ($request->tipo === 'percepcion') {
                $query->percepciones();
            }
        }

        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->has('comprobante_id')) {
            $query->where('comprobante_id', $request->comprobante_id);
        }

        if ($request->has('fecha_desde')) {
            $query->whereDate('created_at', '>=', $request->fecha_desde);
        }

        if ($request->has('fecha_hasta')) {
            $query->whereDate('created_at', '<=', $request->fecha_hasta);
        }

        // Ordenamiento
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginación
        $perPage = $request->get('per_page', 15);
        $retenciones = $query->paginate($perPage);

        return response()->json($retenciones);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'comprobante_id' => 'required|exists:comprobantes,id',
            'tipo' => 'required|in:retencion,percepcion',
            'monto' => 'nullable|numeric|min:0',
            'porcentaje' => 'required|numeric|min:0|max:100',
            'estado' => 'sometimes|in:pendiente,aplicada,anulada',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $retencion = Retencion::create($request->all());

            // Calcular monto automáticamente si no se proporcionó
            if (!$request->has('monto')) {
                $retencion->update([
                    'monto' => $retencion->calcularMonto()
                ]);
            }

            return response()->json([
                'message' => ucfirst($retencion->tipo) . ' creada exitosamente',
                'data' => $retencion->load('comprobante')
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear la retención',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Retencion $retencion)
    {
        return response()->json([
            'data' => $retencion->load(['comprobante'])
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Retencion $retencion)
    {
        $validator = Validator::make($request->all(), [
            'comprobante_id' => 'sometimes|required|exists:comprobantes,id',
            'tipo' => 'sometimes|required|in:retencion,percepcion',
            'monto' => 'nullable|numeric|min:0',
            'porcentaje' => 'sometimes|required|numeric|min:0|max:100',
            'estado' => 'sometimes|in:pendiente,aplicada,anulada',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $retencion->update($request->all());

            // Recalcular monto si cambió el porcentaje
            if ($request->has('porcentaje') && !$request->has('monto')) {
                $retencion->update([
                    'monto' => $retencion->calcularMonto()
                ]);
            }

            return response()->json([
                'message' => ucfirst($retencion->tipo) . ' actualizada exitosamente',
                'data' => $retencion->fresh(['comprobante'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar la retención',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Retencion $retencion)
    {
        try {
            $retencion->delete();

            return response()->json([
                'message' => ucfirst($retencion->tipo) . ' eliminada exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar la retención',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Aplicar retención/percepción
     */
    public function aplicar(Retencion $retencion)
    {
        try {
            $retencion->aplicar();

            return response()->json([
                'message' => ucfirst($retencion->tipo) . ' aplicada exitosamente',
                'data' => $retencion->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al aplicar la retención',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Anular retención/percepción
     */
    public function anular(Retencion $retencion)
    {
        try {
            $retencion->anular();

            return response()->json([
                'message' => ucfirst($retencion->tipo) . ' anulada exitosamente',
                'data' => $retencion->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al anular la retención',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calcular monto de retención/percepción
     */
    public function calcular(Request $request, Retencion $retencion)
    {
        $validator = Validator::make($request->all(), [
            'monto_base' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $montoBase = $request->get('monto_base');
            $montoCalculado = $retencion->calcularMonto($montoBase);

            return response()->json([
                'monto_base' => $montoBase ?? $retencion->comprobante->total,
                'porcentaje' => $retencion->porcentaje,
                'monto_calculado' => $montoCalculado,
                'tipo' => $retencion->tipo
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al calcular el monto',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Estadísticas de retenciones y percepciones
     */
    public function estadisticas(Request $request)
    {
        $query = Retencion::query();

        if ($request->has('fecha_desde')) {
            $query->whereDate('created_at', '>=', $request->fecha_desde);
        }

        if ($request->has('fecha_hasta')) {
            $query->whereDate('created_at', '<=', $request->fecha_hasta);
        }

        $totalRetenciones = (clone $query)->retenciones()->aplicadas()->sum('monto');
        $totalPercepciones = (clone $query)->percepciones()->aplicadas()->sum('monto');
        $retencionesCount = (clone $query)->retenciones()->count();
        $percepcionesCount = (clone $query)->percepciones()->count();
        $pendientes = (clone $query)->pendientes()->count();

        return response()->json([
            'retenciones' => [
                'total' => $totalRetenciones,
                'cantidad' => $retencionesCount
            ],
            'percepciones' => [
                'total' => $totalPercepciones,
                'cantidad' => $percepcionesCount
            ],
            'pendientes' => $pendientes
        ]);
    }
}