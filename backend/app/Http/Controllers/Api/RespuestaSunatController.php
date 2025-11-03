<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RespuestaSunat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RespuestaSunatController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = RespuestaSunat::with(['comprobante']);

        // Filtros
        if ($request->has('estado_envio')) {
            $query->where('estado_envio', $request->estado_envio);
        }

        if ($request->has('comprobante_id')) {
            $query->where('comprobante_id', $request->comprobante_id);
        }

        if ($request->has('codigo_respuesta')) {
            $query->where('codigo_respuesta', $request->codigo_respuesta);
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
        $respuestas = $query->paginate($perPage);

        return response()->json($respuestas);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'comprobante_id' => 'required|exists:comprobantes,id',
            'codigo_respuesta' => 'nullable|string|max:10',
            'descripcion_respuesta' => 'nullable|string',
            'intento' => 'nullable|integer|min:0',
            'cdr' => 'nullable|string',
            'xml' => 'nullable|string',
            'estado_envio' => 'required|in:pendiente,aceptado,rechazado',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $respuesta = RespuestaSunat::create($request->all());

            return response()->json([
                'message' => 'Respuesta SUNAT registrada exitosamente',
                'data' => $respuesta->load('comprobante')
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al registrar la respuesta SUNAT',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(RespuestaSunat $respuestaSunat)
    {
        return response()->json([
            'data' => $respuestaSunat->load(['comprobante'])
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, RespuestaSunat $respuestaSunat)
    {
        $validator = Validator::make($request->all(), [
            'comprobante_id' => 'sometimes|required|exists:comprobantes,id',
            'codigo_respuesta' => 'nullable|string|max:10',
            'descripcion_respuesta' => 'nullable|string',
            'intento' => 'nullable|integer|min:0',
            'cdr' => 'nullable|string',
            'xml' => 'nullable|string',
            'estado_envio' => 'sometimes|required|in:pendiente,aceptado,rechazado',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $respuestaSunat->update($request->all());

            return response()->json([
                'message' => 'Respuesta SUNAT actualizada exitosamente',
                'data' => $respuestaSunat->fresh(['comprobante'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar la respuesta SUNAT',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RespuestaSunat $respuestaSunat)
    {
        try {
            $respuestaSunat->delete();

            return response()->json([
                'message' => 'Respuesta SUNAT eliminada exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar la respuesta SUNAT',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Listar respuestas pendientes de reintento
     */
    public function paraReintento()
    {
        $respuestas = RespuestaSunat::paraReintento()
            ->with(['comprobante'])
            ->get();

        return response()->json([
            'data' => $respuestas,
            'count' => $respuestas->count()
        ]);
    }

    /**
     * Programar reintento de envío
     */
    public function programarReintento(RespuestaSunat $respuestaSunat)
    {
        if (!$respuestaSunat->puedeReintentar()) {
            return response()->json([
                'message' => 'No se puede reintentar. Se alcanzó el límite de intentos o el estado no es pendiente',
                'intento_actual' => $respuestaSunat->intento,
                'max_intentos' => RespuestaSunat::MAX_INTENTOS
            ], 400);
        }

        try {
            $respuestaSunat->update([
                'intento' => $respuestaSunat->intento + 1
            ]);

            $respuestaSunat->programarReintento();

            return response()->json([
                'message' => 'Reintento programado exitosamente',
                'data' => $respuestaSunat->fresh(),
                'proximo_reintento' => $respuestaSunat->fecha_proximo_reintento
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al programar el reintento',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Marcar como aceptado por SUNAT
     */
    public function marcarAceptado(Request $request, RespuestaSunat $respuestaSunat)
    {
        $validator = Validator::make($request->all(), [
            'codigo_respuesta' => 'required|string|max:10',
            'descripcion_respuesta' => 'required|string',
            'cdr' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $respuestaSunat->marcarComoAceptado(
                $request->codigo_respuesta,
                $request->descripcion_respuesta,
                $request->cdr
            );

            return response()->json([
                'message' => 'Respuesta marcada como aceptada',
                'data' => $respuestaSunat->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al marcar como aceptado',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Marcar como rechazado por SUNAT
     */
    public function marcarRechazado(Request $request, RespuestaSunat $respuestaSunat)
    {
        $validator = Validator::make($request->all(), [
            'codigo_respuesta' => 'required|string|max:10',
            'descripcion_respuesta' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $respuestaSunat->marcarComoRechazado(
                $request->codigo_respuesta,
                $request->descripcion_respuesta
            );

            return response()->json([
                'message' => 'Respuesta marcada como rechazada',
                'data' => $respuestaSunat->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al marcar como rechazado',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Descargar CDR
     */
    public function descargarCdr(RespuestaSunat $respuestaSunat)
    {
        if (!$respuestaSunat->cdr) {
            return response()->json([
                'message' => 'CDR no disponible'
            ], 404);
        }

        try {
            $filename = "CDR-{$respuestaSunat->comprobante->serie}-{$respuestaSunat->comprobante->numero}.xml";

            return response($respuestaSunat->cdr, 200)
                ->header('Content-Type', 'application/xml')
                ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al descargar el CDR',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Descargar XML
     */
    public function descargarXml(RespuestaSunat $respuestaSunat)
    {
        if (!$respuestaSunat->xml) {
            return response()->json([
                'message' => 'XML no disponible'
            ], 404);
        }

        try {
            $filename = "{$respuestaSunat->comprobante->serie}-{$respuestaSunat->comprobante->numero}.xml";

            return response($respuestaSunat->xml, 200)
                ->header('Content-Type', 'application/xml')
                ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al descargar el XML',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Estadísticas de respuestas SUNAT
     */
    public function estadisticas(Request $request)
    {
        $query = RespuestaSunat::query();

        if ($request->has('fecha_desde')) {
            $query->whereDate('created_at', '>=', $request->fecha_desde);
        }

        if ($request->has('fecha_hasta')) {
            $query->whereDate('created_at', '<=', $request->fecha_hasta);
        }

        $aceptados = (clone $query)->aceptados()->count();
        $rechazados = (clone $query)->rechazados()->count();
        $pendientes = (clone $query)->pendientes()->count();
        $paraReintento = RespuestaSunat::paraReintento()->count();

        $total = $aceptados + $rechazados + $pendientes;
        $tasaExito = $total > 0 ? round(($aceptados / $total) * 100, 2) : 0;

        return response()->json([
            'aceptados' => $aceptados,
            'rechazados' => $rechazados,
            'pendientes' => $pendientes,
            'para_reintento' => $paraReintento,
            'total' => $total,
            'tasa_exito' => $tasaExito
        ]);
    }

    /**
     * Obtener respuesta por comprobante
     */
    public function porComprobante($comprobanteId)
    {
        $respuestas = RespuestaSunat::where('comprobante_id', $comprobanteId)
            ->orderBy('intento', 'desc')
            ->get();

        if ($respuestas->isEmpty()) {
            return response()->json([
                'message' => 'No se encontraron respuestas para este comprobante'
            ], 404);
        }

        return response()->json([
            'data' => $respuestas,
            'ultima_respuesta' => $respuestas->first()
        ]);
    }
}