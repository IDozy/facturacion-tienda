<?php

namespace App\Http\Controllers\Api\Facturacion;

use App\Http\Controllers\Controller;
use App\Models\Facturacion\Serie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SerieController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Serie::with(['empresa'])
            ->withCount('comprobantes');

        // Filtros
        if ($request->has('activo')) {
            $query->where('activo', $request->activo);
        }

        if ($request->has('tipo_comprobante')) {
            $query->porTipo($request->tipo_comprobante);
        }

        if ($request->has('empresa_id')) {
            $query->deEmpresa($request->empresa_id);
        }

        // Ordenamiento
        $sortBy = $request->get('sort_by', 'tipo_comprobante');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginación
        $perPage = $request->get('per_page', 15);
        
        if ($request->get('all') === 'true') {
            $series = $query->activas()->get();
            return response()->json(['data' => $series]);
        }

        $series = $query->paginate($perPage);

        return response()->json($series);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'empresa_id' => 'required|exists:empresas,id',
            'tipo_comprobante' => 'required|in:factura,boleta,nota_credito,nota_debito,guia_remision',
            'serie' => [
                'required',
                'string',
                'max:4',
                Rule::unique('series')->where(function ($query) use ($request) {
                    return $query->where('empresa_id', $request->empresa_id)
                                 ->where('tipo_comprobante', $request->tipo_comprobante);
                })
            ],
            'correlativo_actual' => 'nullable|integer|min:0',
            'activo' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $serie = Serie::create(array_merge(
                $request->all(),
                ['correlativo_actual' => $request->correlativo_actual ?? 0]
            ));

            // Validar formato SUNAT
            if (!$serie->validarFormato()) {
                $serie->delete();
                return response()->json([
                    'message' => 'El formato de la serie no es válido según SUNAT',
                    'formato_esperado' => $this->obtenerFormatoEsperado($request->tipo_comprobante)
                ], 422);
            }

            return response()->json([
                'message' => 'Serie creada exitosamente',
                'data' => $serie->load('empresa')
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear la serie',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Serie $serie)
    {
        return response()->json([
            'data' => $serie->load(['empresa']),
            'comprobantes_emitidos' => $serie->comprobantes()->count(),
            'formato_valido' => $serie->validarFormato()
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Serie $serie)
    {
        $validator = Validator::make($request->all(), [
            'tipo_comprobante' => 'sometimes|required|in:factura,boleta,nota_credito,nota_debito,guia_remision',
            'serie' => [
                'sometimes',
                'required',
                'string',
                'max:4',
                Rule::unique('series')->ignore($serie->id)->where(function ($query) use ($serie, $request) {
                    return $query->where('empresa_id', $serie->empresa_id)
                                 ->where('tipo_comprobante', $request->tipo_comprobante ?? $serie->tipo_comprobante);
                })
            ],
            'correlativo_actual' => 'nullable|integer|min:0',
            'activo' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $serie->update($request->all());

            // Validar formato SUNAT si cambió la serie
            if ($request->has('serie') && !$serie->validarFormato()) {
                return response()->json([
                    'message' => 'Advertencia: El formato de la serie no es válido según SUNAT',
                    'formato_esperado' => $this->obtenerFormatoEsperado($serie->tipo_comprobante),
                    'data' => $serie
                ], 200);
            }

            return response()->json([
                'message' => 'Serie actualizada exitosamente',
                'data' => $serie->fresh(['empresa'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar la serie',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Serie $serie)
    {
        // Verificar si tiene comprobantes
        if ($serie->comprobantes()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar una serie con comprobantes emitidos',
                'comprobantes_count' => $serie->comprobantes()->count()
            ], 400);
        }

        try {
            $serie->delete();

            return response()->json([
                'message' => 'Serie eliminada exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar la serie',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle estado de la serie
     */
    public function toggleEstado(Serie $serie)
    {
        try {
            if ($serie->esActiva()) {
                $serie->desactivar();
            } else {
                $serie->activar();
            }

            return response()->json([
                'message' => "Serie " . ($serie->activo ? 'activada' : 'desactivada'),
                'data' => $serie
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al cambiar el estado',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generar siguiente número
     */
    public function generarNumero(Serie $serie)
    {
        if (!$serie->esActiva()) {
            return response()->json([
                'message' => 'La serie no está activa'
            ], 400);
        }

        try {
            $numero = $serie->generarNumero();

            return response()->json([
                'numero' => $numero,
                'serie' => $serie->serie,
                'correlativo' => $serie->fresh()->correlativo_actual
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al generar el número',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener siguiente número sin incrementar
     */
    public function siguienteNumero(Serie $serie)
    {
        $siguienteCorrelativo = $serie->correlativo_actual + 1;
        $numeroFormateado = $serie->serie . '-' . str_pad($siguienteCorrelativo, 8, '0', STR_PAD_LEFT);

        return response()->json([
            'siguiente_numero' => $numeroFormateado,
            'serie' => $serie->serie,
            'siguiente_correlativo' => $siguienteCorrelativo,
            'correlativo_actual' => $serie->correlativo_actual
        ]);
    }

    /**
     * Validar formato de serie
     */
    public function validarFormato(Serie $serie)
    {
        $esValido = $serie->validarFormato();

        return response()->json([
            'valido' => $esValido,
            'serie' => $serie->serie,
            'tipo_comprobante' => $serie->tipo_comprobante,
            'formato_esperado' => $this->obtenerFormatoEsperado($serie->tipo_comprobante),
            'mensaje' => $esValido 
                ? 'El formato es válido según SUNAT' 
                : 'El formato no cumple con los estándares de SUNAT'
        ]);
    }

    /**
     * Series por tipo de comprobante
     */
    public function porTipo($tipoComprobante)
    {
        if (!in_array($tipoComprobante, ['factura', 'boleta', 'nota_credito', 'nota_debito', 'guia_remision'])) {
            return response()->json([
                'message' => 'Tipo de comprobante no válido'
            ], 400);
        }

        $series = Serie::porTipo($tipoComprobante)
            ->activas()
            ->orderBy('serie')
            ->get();

        return response()->json([
            'data' => $series,
            'tipo_comprobante' => $tipoComprobante
        ]);
    }

    /**
     * Restablecer correlativo
     */
    public function restablecerCorrelativo(Request $request, Serie $serie)
    {
        $validator = Validator::make($request->all(), [
            'correlativo' => 'required|integer|min:0',
            'confirmacion' => 'required|boolean|accepted',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        // Verificar que no tenga comprobantes con correlativo mayor
        $maxCorrelativo = $serie->comprobantes()
            ->selectRaw('MAX(CAST(SUBSTRING_INDEX(numero, "-", -1) AS UNSIGNED)) as max_correlativo')
            ->value('max_correlativo') ?? 0;

        if ($request->correlativo < $maxCorrelativo) {
            return response()->json([
                'message' => 'No se puede establecer un correlativo menor al máximo usado',
                'correlativo_actual' => $serie->correlativo_actual,
                'correlativo_maximo_usado' => $maxCorrelativo,
                'correlativo_solicitado' => $request->correlativo
            ], 400);
        }

        try {
            $serie->update(['correlativo_actual' => $request->correlativo]);

            return response()->json([
                'message' => 'Correlativo restablecido exitosamente',
                'data' => $serie->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al restablecer el correlativo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Estadísticas de series
     */
    public function estadisticas(Request $request)
    {
        $query = Serie::with(['empresa']);

        if ($request->has('empresa_id')) {
            $query->deEmpresa($request->empresa_id);
        }

        $totalSeries = (clone $query)->count();
        $activas = (clone $query)->activas()->count();
        $inactivas = (clone $query)->where('activo', false)->count();

        $porTipo = (clone $query)->select('tipo_comprobante')
            ->selectRaw('COUNT(*) as cantidad')
            ->selectRaw('SUM(correlativo_actual) as total_emitidos')
            ->groupBy('tipo_comprobante')
            ->get();

        $masUsadas = (clone $query)
            ->withCount('comprobantes')
            ->orderBy('comprobantes_count', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'total_series' => $totalSeries,
            'activas' => $activas,
            'inactivas' => $inactivas,
            'por_tipo' => $porTipo,
            'mas_usadas' => $masUsadas
        ]);
    }

    /**
     * Obtener formato esperado para tipo de comprobante
     */
    private function obtenerFormatoEsperado($tipoComprobante)
    {
        return match($tipoComprobante) {
            'factura' => 'F### (Ej: F001)',
            'boleta' => 'B### (Ej: B001)',
            'nota_credito' => 'F### o B### (Ej: F001, B001)',
            'nota_debito' => 'F### o B### (Ej: F001, B001)',
            'guia_remision' => 'T### (Ej: T001)',
            default => 'No definido'
        };
    }

    /**
     * Tipos de comprobante disponibles
     */
    public function tipos()
    {
        $tipos = [
            ['value' => 'factura', 'label' => 'Factura', 'formato' => 'F###'],
            ['value' => 'boleta', 'label' => 'Boleta', 'formato' => 'B###'],
            ['value' => 'nota_credito', 'label' => 'Nota de Crédito', 'formato' => 'F### o B###'],
            ['value' => 'nota_debito', 'label' => 'Nota de Débito', 'formato' => 'F### o B###'],
            ['value' => 'guia_remision', 'label' => 'Guía de Remisión', 'formato' => 'T###'],
        ];

        return response()->json([
            'data' => $tipos
        ]);
    }
}