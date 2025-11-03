<?php

namespace App\Http\Controllers\Api\Facturacion;

use App\Http\Controllers\Controller;
use App\Models\Facturacion\GuiaRemision;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class GuiaRemisionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = GuiaRemision::with(['empresa', 'comprobante']);

        // Filtros
        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->has('motivo_traslado')) {
            $query->porMotivo($request->motivo_traslado);
        }

        if ($request->has('comprobante_id')) {
            $query->where('comprobante_id', $request->comprobante_id);
        }

        if ($request->has('serie')) {
            $query->where('serie', $request->serie);
        }

        if ($request->has('año') && $request->has('mes')) {
            $query->delMes($request->año, $request->mes);
        }

        if ($request->has('fecha_desde') && $request->has('fecha_hasta')) {
            $query->whereBetween('fecha_emision', [$request->fecha_desde, $request->fecha_hasta]);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('serie', 'like', "%{$search}%")
                    ->orWhere('numero', 'like', "%{$search}%")
                    ->orWhere('transportista_razon_social', 'like', "%{$search}%")
                    ->orWhere('placa_vehiculo', 'like', "%{$search}%");
            });
        }

        // Ordenamiento
        $sortBy = $request->get('sort_by', 'fecha_emision');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginación
        $perPage = $request->get('per_page', 15);
        $guias = $query->paginate($perPage);

        return response()->json($guias);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'comprobante_id' => 'nullable|exists:comprobantes,id',
            'serie' => 'required|string|regex:/^T\d{3}$/',
            'fecha_emision' => 'nullable|date',
            'motivo_traslado' => 'required|in:venta,traslado_interno,devolucion,importacion,exportacion,otros',
            'peso_total' => 'required|numeric|min:0',
            'punto_partida' => 'required|string|max:500',
            'punto_llegada' => 'required|string|max:500',
            'transportista_ruc' => 'required|string|size:11|regex:/^\d{11}$/',
            'transportista_razon_social' => 'required|string|max:255',
            'placa_vehiculo' => 'required|string|max:10',
            'conductor_dni' => 'required|string|size:8|regex:/^\d{8}$/',
            'conductor_nombre' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $guia = GuiaRemision::create([
                'empresa_id' => Auth::user()->empresa_id,
                'comprobante_id' => $request->comprobante_id,
                'serie' => $request->serie,
                'fecha_emision' => $request->fecha_emision ?? now(),
                'motivo_traslado' => $request->motivo_traslado,
                'peso_total' => $request->peso_total,
                'punto_partida' => $request->punto_partida,
                'punto_llegada' => $request->punto_llegada,
                'transportista_ruc' => $request->transportista_ruc,
                'transportista_razon_social' => $request->transportista_razon_social,
                'placa_vehiculo' => strtoupper($request->placa_vehiculo),
                'conductor_dni' => $request->conductor_dni,
                'conductor_nombre' => $request->conductor_nombre,
                'estado' => 'emitida',
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Guía de remisión creada exitosamente',
                'data' => $guia->load(['empresa', 'comprobante']),
                'numero_completo' => $guia->numero_completo
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al crear la guía de remisión',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(GuiaRemision $guiaRemision)
    {
        return response()->json([
            'data' => $guiaRemision->load(['empresa', 'comprobante']),
            'numero_completo' => $guiaRemision->numero_completo,
            'motivo_descripcion' => $guiaRemision->motivo_descripcion,
            'codigo_motivo' => $guiaRemision->codigo_motivo
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, GuiaRemision $guiaRemision)
    {
        if ($guiaRemision->esAnulada()) {
            return response()->json([
                'message' => 'No se puede modificar una guía anulada'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'fecha_emision' => 'sometimes|required|date',
            'motivo_traslado' => 'sometimes|required|in:venta,traslado_interno,devolucion,importacion,exportacion,otros',
            'peso_total' => 'sometimes|required|numeric|min:0',
            'punto_partida' => 'sometimes|required|string|max:500',
            'punto_llegada' => 'sometimes|required|string|max:500',
            'transportista_ruc' => 'sometimes|required|string|size:11|regex:/^\d{11}$/',
            'transportista_razon_social' => 'sometimes|required|string|max:255',
            'placa_vehiculo' => 'sometimes|required|string|max:10',
            'conductor_dni' => 'sometimes|required|string|size:8|regex:/^\d{8}$/',
            'conductor_nombre' => 'sometimes|required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $request->all();
            
            // Convertir placa a mayúsculas si se proporciona
            if (isset($data['placa_vehiculo'])) {
                $data['placa_vehiculo'] = strtoupper($data['placa_vehiculo']);
            }

            $guiaRemision->update($data);

            return response()->json([
                'message' => 'Guía de remisión actualizada exitosamente',
                'data' => $guiaRemision->fresh(['empresa', 'comprobante'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar la guía de remisión',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(GuiaRemision $guiaRemision)
    {
        if ($guiaRemision->esAnulada()) {
            return response()->json([
                'message' => 'La guía ya está anulada'
            ], 400);
        }

        try {
            $guiaRemision->delete();

            return response()->json([
                'message' => 'Guía de remisión eliminada exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar la guía de remisión',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Anular guía de remisión
     */
    public function anular(Request $request, GuiaRemision $guiaRemision)
    {
        if ($guiaRemision->esAnulada()) {
            return response()->json([
                'message' => 'La guía ya está anulada'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'motivo_anulacion' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $guiaRemision->anular($request->motivo_anulacion);

            return response()->json([
                'message' => 'Guía de remisión anulada exitosamente',
                'data' => $guiaRemision->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al anular la guía de remisión',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validar datos de la guía
     */
    public function validar(GuiaRemision $guiaRemision)
    {
        $errores = [];

        if (!$guiaRemision->validarSerie()) {
            $errores[] = 'La serie no cumple con el formato SUNAT (T###)';
        }

        if (!$guiaRemision->validarTransportista()) {
            $errores[] = 'El RUC del transportista no es válido';
        }

        if (!preg_match('/^\d{8}$/', $guiaRemision->conductor_dni)) {
            $errores[] = 'El DNI del conductor no es válido';
        }

        $esValido = empty($errores);

        return response()->json([
            'valido' => $esValido,
            'errores' => $errores,
            'mensaje' => $esValido 
                ? 'La guía cumple con todos los requisitos' 
                : 'La guía tiene errores de validación'
        ]);
    }

    /**
     * Obtener siguiente número para una serie
     */
    public function siguienteNumero(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'serie' => 'required|string|regex:/^T\d{3}$/',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $ultimoNumero = GuiaRemision::where('empresa_id', Auth::user()->empresa_id)
            ->where('serie', $request->serie)
            ->max('numero') ?? 0;

        $siguienteNumero = $ultimoNumero + 1;
        $numeroFormateado = $request->serie . '-' . str_pad($siguienteNumero, 8, '0', STR_PAD_LEFT);

        return response()->json([
            'serie' => $request->serie,
            'siguiente_numero' => $siguienteNumero,
            'numero_formateado' => $numeroFormateado
        ]);
    }

    /**
     * Guías por comprobante
     */
    public function porComprobante($comprobanteId)
    {
        $guias = GuiaRemision::where('comprobante_id', $comprobanteId)
            ->orderBy('fecha_emision', 'desc')
            ->get();

        return response()->json([
            'data' => $guias,
            'count' => $guias->count()
        ]);
    }

    /**
     * Estadísticas de guías de remisión
     */
    public function estadisticas(Request $request)
    {
        $query = GuiaRemision::query();

        if ($request->has('fecha_desde') && $request->has('fecha_hasta')) {
            $query->whereBetween('fecha_emision', [$request->fecha_desde, $request->fecha_hasta]);
        }

        $total = (clone $query)->count();
        $emitidas = (clone $query)->emitidas()->count();
        $anuladas = (clone $query)->anuladas()->count();

        $porMotivo = (clone $query)->select('motivo_traslado')
            ->selectRaw('COUNT(*) as cantidad')
            ->groupBy('motivo_traslado')
            ->get()
            ->map(function ($item) {
                return [
                    'motivo' => $item->motivo_traslado,
                    'descripcion' => GuiaRemision::make(['motivo_traslado' => $item->motivo_traslado])->motivo_descripcion,
                    'cantidad' => $item->cantidad
                ];
            });

        $porSerie = (clone $query)->select('serie')
            ->selectRaw('COUNT(*) as cantidad')
            ->groupBy('serie')
            ->orderBy('cantidad', 'desc')
            ->get();

        $transportistas = (clone $query)->select('transportista_ruc', 'transportista_razon_social')
            ->selectRaw('COUNT(*) as cantidad_guias')
            ->groupBy('transportista_ruc', 'transportista_razon_social')
            ->orderBy('cantidad_guias', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'total' => $total,
            'emitidas' => $emitidas,
            'anuladas' => $anuladas,
            'por_motivo' => $porMotivo,
            'por_serie' => $porSerie,
            'transportistas_mas_usados' => $transportistas
        ]);
    }

    /**
     * Motivos de traslado disponibles
     */
    public function motivos()
    {
        $motivos = [
            ['value' => 'venta', 'label' => 'Venta', 'codigo' => '01'],
            ['value' => 'traslado_interno', 'label' => 'Traslado entre establecimientos', 'codigo' => '04'],
            ['value' => 'devolucion', 'label' => 'Devolución', 'codigo' => '02'],
            ['value' => 'importacion', 'label' => 'Importación', 'codigo' => '08'],
            ['value' => 'exportacion', 'label' => 'Exportación', 'codigo' => '09'],
            ['value' => 'otros', 'label' => 'Otros', 'codigo' => '13'],
        ];

        return response()->json([
            'data' => $motivos
        ]);
    }

    /**
     * Guías del mes
     */
    public function delMes(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'año' => 'required|integer|min:2000|max:2100',
            'mes' => 'required|integer|min:1|max:12',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $guias = GuiaRemision::delMes($request->año, $request->mes)
            ->with(['comprobante'])
            ->orderBy('fecha_emision', 'desc')
            ->get();

        return response()->json([
            'data' => $guias,
            'año' => $request->año,
            'mes' => $request->mes,
            'count' => $guias->count()
        ]);
    }

    /**
     * Series disponibles
     */
    public function series()
    {
        $series = GuiaRemision::where('empresa_id', Auth::user()->empresa_id)
            ->select('serie')
            ->distinct()
            ->orderBy('serie')
            ->pluck('serie');

        return response()->json([
            'data' => $series
        ]);
    }

    /**
     * Exportar guías
     */
    public function exportar(Request $request)
    {
        $query = GuiaRemision::with(['comprobante']);

        if ($request->has('fecha_desde') && $request->has('fecha_hasta')) {
            $query->whereBetween('fecha_emision', [$request->fecha_desde, $request->fecha_hasta]);
        }

        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        $guias = $query->orderBy('fecha_emision', 'desc')->get()->map(function ($guia) {
            return [
                'numero_completo' => $guia->numero_completo,
                'fecha_emision' => $guia->fecha_emision->format('Y-m-d'),
                'motivo' => $guia->motivo_descripcion,
                'punto_partida' => $guia->punto_partida,
                'punto_llegada' => $guia->punto_llegada,
                'transportista' => $guia->transportista_razon_social,
                'placa' => $guia->placa_vehiculo,
                'peso' => $guia->peso_total,
                'estado' => $guia->estado,
            ];
        });

        return response()->json([
            'data' => $guias,
            'count' => $guias->count()
        ]);
    }
}