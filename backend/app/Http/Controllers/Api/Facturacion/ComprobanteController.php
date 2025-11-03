<?php

namespace App\Http\Controllers\Api\Facturacion;

use App\Http\Controllers\Controller;
use App\Models\Facturacion\Comprobante;
use App\Models\Facturacion\Serie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ComprobanteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Comprobante::with(['cliente', 'serie', 'usuario']);

        // Filtros
        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->has('tipo_comprobante')) {
            $query->where('tipo_comprobante', $request->tipo_comprobante);
        }

        if ($request->has('cliente_id')) {
            $query->where('cliente_id', $request->cliente_id);
        }

        if ($request->has('serie_id')) {
            $query->where('serie_id', $request->serie_id);
        }

        if ($request->has('con_saldo')) {
            $query->conSaldo();
        }

        if ($request->has('fecha_desde') && $request->has('fecha_hasta')) {
            $query->delPeriodo($request->fecha_desde, $request->fecha_hasta);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('numero', 'like', "%{$search}%")
                    ->orWhere('razon_social_cliente', 'like', "%{$search}%")
                    ->orWhere('numero_documento_cliente', 'like', "%{$search}%");
            });
        }

        // Ordenamiento
        $sortBy = $request->get('sort_by', 'fecha_emision');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginación
        $perPage = $request->get('per_page', 15);
        $comprobantes = $query->paginate($perPage);

        return response()->json($comprobantes);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cliente_id' => 'required|exists:clientes,id',
            'serie_id' => 'required|exists:series,id',
            'tipo_comprobante' => 'required|in:factura,boleta,nota_credito,nota_debito',
            'fecha_emision' => 'nullable|date',
            'forma_pago' => 'required|in:contado,credito',
            'plazo_pago_dias' => 'nullable|required_if:forma_pago,credito|integer|min:1',
            'es_exportacion' => 'boolean',
            'codigo_moneda' => 'nullable|string|in:PEN,USD,EUR',
            'tipo_cambio' => 'nullable|numeric|min:0',
            'observaciones' => 'nullable|string',
            'comprobante_referencia_id' => 'nullable|required_if:tipo_comprobante,nota_credito,nota_debito|exists:comprobantes,id',
            'motivo_anulacion' => 'nullable|required_if:tipo_comprobante,nota_credito|string',
            'detalles' => 'required|array|min:1',
            'detalles.*.producto_id' => 'required|exists:productos,id',
            'detalles.*.cantidad' => 'required|numeric|min:0.001',
            'detalles.*.precio_unitario' => 'required|numeric|min:0',
            'detalles.*.tipo_afectacion' => 'required|in:gravado,exonerado,inafecto,exportacion',
            'detalles.*.descuento_monto' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        // Validar que la serie corresponda al tipo de comprobante
        $serie = Serie::find($request->serie_id);
        if ($serie->tipo_comprobante !== $request->tipo_comprobante) {
            return response()->json([
                'message' => 'La serie no corresponde al tipo de comprobante'
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Crear comprobante
            $comprobante = Comprobante::create([
                'cliente_id' => $request->cliente_id,
                'empresa_id' => Auth::user()->empresa_id,
                'serie_id' => $request->serie_id,
                'tipo_comprobante' => $request->tipo_comprobante,
                'fecha_emision' => $request->fecha_emision ?? now(),
                'forma_pago' => $request->forma_pago,
                'plazo_pago_dias' => $request->plazo_pago_dias,
                'es_exportacion' => $request->get('es_exportacion', false),
                'codigo_moneda' => $request->codigo_moneda ?? 'PEN',
                'tipo_cambio' => $request->tipo_cambio ?? 1,
                'observaciones' => $request->observaciones,
                'comprobante_referencia_id' => $request->comprobante_referencia_id,
                'estado' => 'emitido',
                'usuario_id' => Auth::id(),
            ]);

            // Crear detalles
            foreach ($request->detalles as $detalleData) {
                $comprobante->detalles()->create($detalleData);
            }

            // Calcular totales
            $comprobante->calcularTotales();

            // Generar número de comprobante
            $numeroCompleto = $serie->generarNumero();
            $numero = explode('-', $numeroCompleto)[1];
            $comprobante->update(['numero' => (int)$numero]);

            DB::commit();

            return response()->json([
                'message' => 'Comprobante creado exitosamente',
                'data' => $comprobante->load(['cliente', 'serie', 'detalles.producto']),
                'numero_completo' => $comprobante->numero_completo
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al crear el comprobante',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Comprobante $comprobante)
    {
        return response()->json([
            'data' => $comprobante->load([
                'cliente',
                'serie',
                'usuario',
                'detalles.producto',
                'pagos',
                'respuestaSunat',
                'guiasRemision'
            ]),
            'numero_completo' => $comprobante->numero_completo,
            'esta_pagado' => $comprobante->estaPagado()
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Comprobante $comprobante)
    {
        if (in_array($comprobante->estado, ['enviado_sunat', 'aceptado_sunat', 'anulado'])) {
            return response()->json([
                'message' => 'No se puede modificar un comprobante enviado a SUNAT o anulado'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'forma_pago' => 'sometimes|required|in:contado,credito',
            'plazo_pago_dias' => 'nullable|integer|min:1',
            'observaciones' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $comprobante->update($request->only(['forma_pago', 'plazo_pago_dias', 'observaciones']));

            return response()->json([
                'message' => 'Comprobante actualizado exitosamente',
                'data' => $comprobante->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar el comprobante',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Comprobante $comprobante)
    {
        if ($comprobante->estado !== 'emitido') {
            return response()->json([
                'message' => 'Solo se pueden eliminar comprobantes en estado emitido'
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Eliminar detalles y movimientos
            $comprobante->detalles()->delete();
            $comprobante->movimientosStock()->delete();
            $comprobante->delete();

            DB::commit();

            return response()->json([
                'message' => 'Comprobante eliminado exitosamente'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al eliminar el comprobante',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Anular comprobante
     */
    public function anular(Request $request, Comprobante $comprobante)
    {
        if ($comprobante->estado === 'anulado') {
            return response()->json([
                'message' => 'El comprobante ya está anulado'
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

        DB::beginTransaction();
        try {
            $comprobante->anular($request->motivo_anulacion);

            DB::commit();

            return response()->json([
                'message' => 'Comprobante anulado exitosamente',
                'data' => $comprobante->fresh()
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al anular el comprobante',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Recalcular totales
     */
    public function recalcularTotales(Comprobante $comprobante)
    {
        if (in_array($comprobante->estado, ['enviado_sunat', 'aceptado_sunat', 'anulado'])) {
            return response()->json([
                'message' => 'No se pueden recalcular totales de un comprobante enviado o anulado'
            ], 400);
        }

        try {
            $comprobante->calcularTotales();

            return response()->json([
                'message' => 'Totales recalculados exitosamente',
                'data' => $comprobante->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al recalcular totales',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar saldo pendiente
     */
    public function actualizarSaldo(Comprobante $comprobante)
    {
        try {
            $comprobante->actualizarSaldoPendiente();

            return response()->json([
                'message' => 'Saldo actualizado exitosamente',
                'saldo_pendiente' => $comprobante->saldo_pendiente,
                'esta_pagado' => $comprobante->estaPagado()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar el saldo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Comprobantes con saldo pendiente
     */
    public function conSaldo(Request $request)
    {
        $query = Comprobante::conSaldo()
            ->with(['cliente', 'serie'])
            ->where('estado', '!=', 'anulado');

        if ($request->has('cliente_id')) {
            $query->where('cliente_id', $request->cliente_id);
        }

        $comprobantes = $query->orderBy('fecha_emision', 'desc')->get();

        $saldoTotal = $comprobantes->sum('saldo_pendiente');

        return response()->json([
            'data' => $comprobantes,
            'resumen' => [
                'cantidad' => $comprobantes->count(),
                'saldo_total' => $saldoTotal
            ]
        ]);
    }

    /**
     * Estadísticas de comprobantes
     */
    public function estadisticas(Request $request)
    {
        $query = Comprobante::query();

        if ($request->has('fecha_desde') && $request->has('fecha_hasta')) {
            $query->delPeriodo($request->fecha_desde, $request->fecha_hasta);
        }

        $totalComprobantes = (clone $query)->count();
        $totalFacturado = (clone $query)->where('estado', '!=', 'anulado')->sum('total');
        $totalIgv = (clone $query)->where('estado', '!=', 'anulado')->sum('igv_total');

        $porTipo = (clone $query)->select('tipo_comprobante')
            ->selectRaw('COUNT(*) as cantidad')
            ->selectRaw('SUM(total) as monto')
            ->where('estado', '!=', 'anulado')
            ->groupBy('tipo_comprobante')
            ->get();

        $porEstado = (clone $query)->select('estado')
            ->selectRaw('COUNT(*) as cantidad')
            ->groupBy('estado')
            ->get();

        $topClientes = (clone $query)->select('cliente_id')
            ->with('cliente')
            ->selectRaw('COUNT(*) as cantidad_comprobantes')
            ->selectRaw('SUM(total) as monto_total')
            ->where('estado', '!=', 'anulado')
            ->groupBy('cliente_id')
            ->orderBy('monto_total', 'desc')
            ->limit(10)
            ->get();

        $porFormaPago = (clone $query)->select('forma_pago')
            ->selectRaw('COUNT(*) as cantidad')
            ->selectRaw('SUM(total) as monto')
            ->where('estado', '!=', 'anulado')
            ->groupBy('forma_pago')
            ->get();

        return response()->json([
            'total_comprobantes' => $totalComprobantes,
            'total_facturado' => $totalFacturado,
            'total_igv' => $totalIgv,
            'por_tipo' => $porTipo,
            'por_estado' => $porEstado,
            'top_clientes' => $topClientes,
            'por_forma_pago' => $porFormaPago
        ]);
    }

    /**
     * Exportar comprobantes
     */
    public function exportar(Request $request)
    {
        $query = Comprobante::with(['cliente', 'serie']);

        if ($request->has('fecha_desde') && $request->has('fecha_hasta')) {
            $query->delPeriodo($request->fecha_desde, $request->fecha_hasta);
        }

        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->has('tipo_comprobante')) {
            $query->where('tipo_comprobante', $request->tipo_comprobante);
        }

        $comprobantes = $query->orderBy('fecha_emision', 'desc')->get()->map(function ($comp) {
            return [
                'numero' => $comp->numero_completo,
                'tipo' => $comp->tipo_comprobante,
                'fecha' => $comp->fecha_emision->format('Y-m-d'),
                'cliente_documento' => $comp->numero_documento_cliente,
                'cliente_nombre' => $comp->razon_social_cliente,
                'subtotal' => $comp->total_neto,
                'igv' => $comp->igv_total,
                'total' => $comp->total,
                'estado' => $comp->estado,
            ];
        });

        return response()->json([
            'data' => $comprobantes,
            'count' => $comprobantes->count()
        ]);
    }

    /**
     * Ventas del día
     */
    public function ventasDelDia(Request $request)
    {
        $fecha = $request->get('fecha', now()->toDateString());

        $comprobantes = Comprobante::whereDate('fecha_emision', $fecha)
            ->where('estado', '!=', 'anulado')
            ->with(['cliente'])
            ->get();

        $totalVentas = $comprobantes->sum('total');
        $totalIgv = $comprobantes->sum('igv_total');
        $cantidadFacturas = $comprobantes->where('tipo_comprobante', 'factura')->count();
        $cantidadBoletas = $comprobantes->where('tipo_comprobante', 'boleta')->count();

        return response()->json([
            'fecha' => $fecha,
            'data' => $comprobantes,
            'resumen' => [
                'total_ventas' => $totalVentas,
                'total_igv' => $totalIgv,
                'cantidad_facturas' => $cantidadFacturas,
                'cantidad_boletas' => $cantidadBoletas,
                'total_comprobantes' => $comprobantes->count()
            ]
        ]);
    }

    /**
     * Crear nota de crédito
     */
    public function crearNotaCredito(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'comprobante_id' => 'required|exists:comprobantes,id',
            'serie_id' => 'required|exists:series,id',
            'motivo_anulacion' => 'required|string|max:255',
            'detalles' => 'required|array|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $comprobanteOriginal = Comprobante::find($request->comprobante_id);

        DB::beginTransaction();
        try {
            $notaCredito = Comprobante::create([
                'cliente_id' => $comprobanteOriginal->cliente_id,
                'empresa_id' => $comprobanteOriginal->empresa_id,
                'serie_id' => $request->serie_id,
                'tipo_comprobante' => 'nota_credito',
                'fecha_emision' => now(),
                'comprobante_referencia_id' => $request->comprobante_id,
                'motivo_anulacion' => $request->motivo_anulacion,
                'estado' => 'emitido',
                'usuario_id' => Auth::id(),
            ]);

            // Crear detalles
            foreach ($request->detalles as $detalleData) {
                $notaCredito->detalles()->create($detalleData);
            }

            // Calcular totales
            $notaCredito->calcularTotales();

            DB::commit();

            return response()->json([
                'message' => 'Nota de crédito creada exitosamente',
                'data' => $notaCredito->load(['detalles', 'comprobanteReferencia'])
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al crear la nota de crédito',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}