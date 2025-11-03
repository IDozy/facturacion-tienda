<?php

namespace App\Http\Controllers\Api\Facturacion;

use App\Http\Controllers\Controller;
use App\Models\Facturacion\ComprobanteDetalle;
use App\Models\Facturacion\Comprobante;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ComprobanteDetalleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = ComprobanteDetalle::with(['comprobante', 'producto']);

        // Filtros
        if ($request->has('comprobante_id')) {
            $query->where('comprobante_id', $request->comprobante_id);
        }

        if ($request->has('producto_id')) {
            $query->where('producto_id', $request->producto_id);
        }

        if ($request->has('tipo_afectacion')) {
            $query->where('tipo_afectacion', $request->tipo_afectacion);
        }

        // Ordenamiento
        $sortBy = $request->get('sort_by', 'id');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginación
        $perPage = $request->get('per_page', 50);
        $detalles = $query->paginate($perPage);

        return response()->json($detalles);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'comprobante_id' => 'required|exists:comprobantes,id',
            'producto_id' => 'required|exists:productos,id',
            'cantidad' => 'required|numeric|min:0.001',
            'precio_unitario' => 'required|numeric|min:0',
            'tipo_afectacion' => 'required|in:gravado,exonerado,inafecto,exportacion',
            'descuento_monto' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        // Verificar que el comprobante no esté cerrado/enviado
        $comprobante = Comprobante::find($request->comprobante_id);
        if ($comprobante && in_array($comprobante->estado, ['enviado_sunat', 'aceptado_sunat'])) {
            return response()->json([
                'message' => 'No se pueden agregar detalles a un comprobante ya enviado a SUNAT'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $detalle = ComprobanteDetalle::create($request->all());

            // Recalcular totales del comprobante
            if ($comprobante) {
                $comprobante->recalcularTotales();
            }

            DB::commit();

            return response()->json([
                'message' => 'Detalle agregado exitosamente',
                'data' => $detalle->load(['producto', 'comprobante'])
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al agregar el detalle',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(ComprobanteDetalle $comprobanteDetalle)
    {
        return response()->json([
            'data' => $comprobanteDetalle->load(['comprobante', 'producto']),
            'descripcion' => $comprobanteDetalle->descripcion,
            'codigo' => $comprobanteDetalle->codigo,
            'unidad_medida' => $comprobanteDetalle->unidad_medida,
            'tipo_afectacion_codigo' => $comprobanteDetalle->tipo_afectacion_codigo,
            'valor_unitario' => $comprobanteDetalle->valor_unitario,
            'tiene_descuento' => $comprobanteDetalle->tieneDescuento()
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ComprobanteDetalle $comprobanteDetalle)
    {
        // Verificar que el comprobante no esté cerrado/enviado
        if (in_array($comprobanteDetalle->comprobante->estado, ['enviado_sunat', 'aceptado_sunat'])) {
            return response()->json([
                'message' => 'No se pueden modificar detalles de un comprobante ya enviado a SUNAT'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'cantidad' => 'sometimes|required|numeric|min:0.001',
            'precio_unitario' => 'sometimes|required|numeric|min:0',
            'tipo_afectacion' => 'sometimes|required|in:gravado,exonerado,inafecto,exportacion',
            'descuento_monto' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $comprobanteDetalle->update($request->all());

            // Recalcular totales del comprobante
            $comprobanteDetalle->comprobante->recalcularTotales();

            DB::commit();

            return response()->json([
                'message' => 'Detalle actualizado exitosamente',
                'data' => $comprobanteDetalle->fresh(['comprobante', 'producto'])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al actualizar el detalle',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ComprobanteDetalle $comprobanteDetalle)
    {
        // Verificar que el comprobante no esté cerrado/enviado
        if (in_array($comprobanteDetalle->comprobante->estado, ['enviado_sunat', 'aceptado_sunat'])) {
            return response()->json([
                'message' => 'No se pueden eliminar detalles de un comprobante ya enviado a SUNAT'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $comprobante = $comprobanteDetalle->comprobante;
            $comprobanteDetalle->delete();

            // Recalcular totales del comprobante
            $comprobante->recalcularTotales();

            DB::commit();

            return response()->json([
                'message' => 'Detalle eliminado exitosamente'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al eliminar el detalle',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validar totales del detalle
     */
    public function validarTotal(ComprobanteDetalle $comprobanteDetalle)
    {
        $esValido = $comprobanteDetalle->validarTotal();

        return response()->json([
            'valido' => $esValido,
            'subtotal' => $comprobanteDetalle->subtotal,
            'igv' => $comprobanteDetalle->igv,
            'total' => $comprobanteDetalle->total,
            'mensaje' => $esValido 
                ? 'Los montos del detalle son correctos' 
                : 'Existe una diferencia en los cálculos del detalle'
        ]);
    }

    /**
     * Recalcular montos del detalle
     */
    public function recalcular(ComprobanteDetalle $comprobanteDetalle)
    {
        // Verificar que el comprobante no esté cerrado/enviado
        if (in_array($comprobanteDetalle->comprobante->estado, ['enviado_sunat', 'aceptado_sunat'])) {
            return response()->json([
                'message' => 'No se pueden recalcular detalles de un comprobante ya enviado a SUNAT'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $comprobanteDetalle->calcularMontos();
            $comprobanteDetalle->save();

            // Recalcular totales del comprobante
            $comprobanteDetalle->comprobante->recalcularTotales();

            DB::commit();

            return response()->json([
                'message' => 'Montos recalculados exitosamente',
                'data' => $comprobanteDetalle->fresh()
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al recalcular los montos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener detalles por comprobante
     */
    public function porComprobante($comprobanteId)
    {
        $detalles = ComprobanteDetalle::where('comprobante_id', $comprobanteId)
            ->with(['producto'])
            ->orderBy('id')
            ->get();

        $subtotalTotal = $detalles->sum('subtotal');
        $igvTotal = $detalles->sum('igv');
        $totalGeneral = $detalles->sum('total');
        $descuentoTotal = $detalles->sum('descuento_monto');

        return response()->json([
            'data' => $detalles,
            'resumen' => [
                'cantidad_items' => $detalles->count(),
                'subtotal_total' => $subtotalTotal,
                'igv_total' => $igvTotal,
                'descuento_total' => $descuentoTotal,
                'total_general' => $totalGeneral
            ]
        ]);
    }

    /**
     * Obtener detalles por producto
     */
    public function porProducto($productoId, Request $request)
    {
        $query = ComprobanteDetalle::where('producto_id', $productoId)
            ->with(['comprobante']);

        if ($request->has('fecha_desde') && $request->has('fecha_hasta')) {
            $query->whereHas('comprobante', function ($q) use ($request) {
                $q->whereBetween('fecha_emision', [$request->fecha_desde, $request->fecha_hasta]);
            });
        }

        $detalles = $query->orderBy('created_at', 'desc')->get();

        $cantidadTotal = $detalles->sum('cantidad');
        $montoTotal = $detalles->sum('total');

        return response()->json([
            'data' => $detalles,
            'resumen' => [
                'cantidad_ventas' => $detalles->count(),
                'cantidad_total' => $cantidadTotal,
                'monto_total' => $montoTotal
            ]
        ]);
    }

    /**
     * Productos más vendidos
     */
    public function productosMasVendidos(Request $request)
    {
        $query = ComprobanteDetalle::with(['producto'])
            ->whereHas('comprobante', function ($q) {
                $q->where('estado', '!=', 'anulado');
            });

        if ($request->has('fecha_desde') && $request->has('fecha_hasta')) {
            $query->whereHas('comprobante', function ($q) use ($request) {
                $q->whereBetween('fecha_emision', [$request->fecha_desde, $request->fecha_hasta]);
            });
        }

        $productos = $query->select('producto_id')
            ->selectRaw('SUM(cantidad) as cantidad_total')
            ->selectRaw('SUM(total) as monto_total')
            ->selectRaw('COUNT(*) as veces_vendido')
            ->groupBy('producto_id')
            ->orderBy('cantidad_total', 'desc')
            ->limit($request->get('limit', 10))
            ->get();

        return response()->json([
            'data' => $productos
        ]);
    }

    /**
     * Estadísticas de detalles
     */
    public function estadisticas(Request $request)
    {
        $query = ComprobanteDetalle::whereHas('comprobante', function ($q) {
            $q->where('estado', '!=', 'anulado');
        });

        if ($request->has('fecha_desde') && $request->has('fecha_hasta')) {
            $query->whereHas('comprobante', function ($q) use ($request) {
                $q->whereBetween('fecha_emision', [$request->fecha_desde, $request->fecha_hasta]);
            });
        }

        $totalDetalles = (clone $query)->count();
        $cantidadTotal = (clone $query)->sum('cantidad');
        $subtotalTotal = (clone $query)->sum('subtotal');
        $igvTotal = (clone $query)->sum('igv');
        $totalGeneral = (clone $query)->sum('total');
        $descuentoTotal = (clone $query)->sum('descuento_monto');

        $porTipoAfectacion = (clone $query)->select('tipo_afectacion')
            ->selectRaw('COUNT(*) as cantidad')
            ->selectRaw('SUM(total) as monto')
            ->groupBy('tipo_afectacion')
            ->get();

        $conDescuento = (clone $query)->where('descuento_monto', '>', 0)->count();

        return response()->json([
            'total_detalles' => $totalDetalles,
            'cantidad_total' => $cantidadTotal,
            'subtotal_total' => $subtotalTotal,
            'igv_total' => $igvTotal,
            'descuento_total' => $descuentoTotal,
            'total_general' => $totalGeneral,
            'por_tipo_afectacion' => $porTipoAfectacion,
            'con_descuento' => $conDescuento,
            'sin_descuento' => $totalDetalles - $conDescuento
        ]);
    }

    /**
     * Aplicar descuento masivo
     */
    public function aplicarDescuentoMasivo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'comprobante_id' => 'required|exists:comprobantes,id',
            'descuento_porcentaje' => 'required|numeric|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $comprobante = Comprobante::find($request->comprobante_id);
        
        if (in_array($comprobante->estado, ['enviado_sunat', 'aceptado_sunat'])) {
            return response()->json([
                'message' => 'No se puede aplicar descuento a un comprobante ya enviado a SUNAT'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $detalles = ComprobanteDetalle::where('comprobante_id', $request->comprobante_id)->get();
            $actualizados = 0;

            foreach ($detalles as $detalle) {
                $subtotalSinDescuento = $detalle->cantidad * $detalle->precio_unitario;
                $descuento = round($subtotalSinDescuento * ($request->descuento_porcentaje / 100), 2);
                
                $detalle->descuento_monto = $descuento;
                $detalle->save();
                $actualizados++;
            }

            // Recalcular totales del comprobante
            $comprobante->recalcularTotales();

            DB::commit();

            return response()->json([
                'message' => "Descuento aplicado a {$actualizados} item(s)",
                'actualizados' => $actualizados,
                'porcentaje' => $request->descuento_porcentaje
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al aplicar el descuento',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}