<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ConfiguracionEmpresa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ConfiguracionEmpresaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = ConfiguracionEmpresa::with(['empresa']);

        if ($request->has('empresa_id')) {
            $query->where('empresa_id', $request->empresa_id);
        }

        $configuraciones = $query->paginate($request->get('per_page', 15));

        return response()->json($configuraciones);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'empresa_id' => 'required|exists:empresas,id|unique:configuraciones_empresa,empresa_id',
            'igv_porcentaje' => 'required|numeric|min:0|max:100',
            'moneda_default' => 'required|string|in:PEN,USD,EUR',
            'tolerancia_cuadratura' => 'nullable|numeric|min:0',
            'retencion_porcentaje_default' => 'nullable|numeric|min:0|max:100',
            'percepcion_porcentaje_default' => 'nullable|numeric|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $configuracion = ConfiguracionEmpresa::create($request->all());

            return response()->json([
                'message' => 'Configuración creada exitosamente',
                'data' => $configuracion->load('empresa')
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear la configuración',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(ConfiguracionEmpresa $configuracionEmpresa)
    {
        return response()->json([
            'data' => $configuracionEmpresa->load(['empresa'])
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ConfiguracionEmpresa $configuracionEmpresa)
    {
        $validator = Validator::make($request->all(), [
            'igv_porcentaje' => 'sometimes|required|numeric|min:0|max:100',
            'moneda_default' => 'sometimes|required|string|in:PEN,USD,EUR',
            'tolerancia_cuadratura' => 'nullable|numeric|min:0',
            'retencion_porcentaje_default' => 'nullable|numeric|min:0|max:100',
            'percepcion_porcentaje_default' => 'nullable|numeric|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $configuracionEmpresa->update($request->all());

            return response()->json([
                'message' => 'Configuración actualizada exitosamente',
                'data' => $configuracionEmpresa->fresh(['empresa'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar la configuración',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ConfiguracionEmpresa $configuracionEmpresa)
    {
        try {
            $configuracionEmpresa->delete();

            return response()->json([
                'message' => 'Configuración eliminada exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar la configuración',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener configuración por empresa
     */
    public function porEmpresa($empresaId)
    {
        $configuracion = ConfiguracionEmpresa::deEmpresa($empresaId);

        if (!$configuracion) {
            return response()->json([
                'message' => 'Configuración no encontrada para esta empresa'
            ], 404);
        }

        return response()->json([
            'data' => $configuracion
        ]);
    }

    /**
     * Calcular IGV
     */
    public function calcularIgv(Request $request, ConfiguracionEmpresa $configuracionEmpresa)
    {
        $validator = Validator::make($request->all(), [
            'monto' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $monto = $request->monto;
        $igv = $configuracionEmpresa->calcularIgv($monto);
        $total = $configuracionEmpresa->calcularMontoConIgv($monto);

        return response()->json([
            'monto_base' => $monto,
            'igv' => $igv,
            'total' => $total,
            'porcentaje_igv' => $configuracionEmpresa->igv_porcentaje
        ]);
    }

    /**
     * Calcular monto sin IGV
     */
    public function calcularSinIgv(Request $request, ConfiguracionEmpresa $configuracionEmpresa)
    {
        $validator = Validator::make($request->all(), [
            'monto_con_igv' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $montoConIgv = $request->monto_con_igv;
        $montoSinIgv = $configuracionEmpresa->calcularMontoSinIgv($montoConIgv);
        $igv = $montoConIgv - $montoSinIgv;

        return response()->json([
            'monto_con_igv' => $montoConIgv,
            'monto_sin_igv' => $montoSinIgv,
            'igv' => $igv,
            'porcentaje_igv' => $configuracionEmpresa->igv_porcentaje
        ]);
    }

    /**
     * Calcular retención
     */
    public function calcularRetencion(Request $request, ConfiguracionEmpresa $configuracionEmpresa)
    {
        $validator = Validator::make($request->all(), [
            'monto' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $monto = $request->monto;
        $retencion = $configuracionEmpresa->calcularRetencion($monto);

        return response()->json([
            'monto_base' => $monto,
            'retencion' => $retencion,
            'porcentaje_retencion' => $configuracionEmpresa->retencion_porcentaje_default,
            'monto_final' => $monto - $retencion
        ]);
    }

    /**
     * Calcular percepción
     */
    public function calcularPercepcion(Request $request, ConfiguracionEmpresa $configuracionEmpresa)
    {
        $validator = Validator::make($request->all(), [
            'monto' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $monto = $request->monto;
        $percepcion = $configuracionEmpresa->calcularPercepcion($monto);

        return response()->json([
            'monto_base' => $monto,
            'percepcion' => $percepcion,
            'porcentaje_percepcion' => $configuracionEmpresa->percepcion_porcentaje_default,
            'monto_final' => $monto + $percepcion
        ]);
    }

    /**
     * Actualizar IGV
     */
    public function actualizarIgv(Request $request, ConfiguracionEmpresa $configuracionEmpresa)
    {
        $validator = Validator::make($request->all(), [
            'igv_porcentaje' => 'required|numeric|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $configuracionEmpresa->update([
                'igv_porcentaje' => $request->igv_porcentaje
            ]);

            return response()->json([
                'message' => 'Porcentaje de IGV actualizado exitosamente',
                'data' => $configuracionEmpresa->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar el IGV',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar moneda por defecto
     */
    public function actualizarMoneda(Request $request, ConfiguracionEmpresa $configuracionEmpresa)
    {
        $validator = Validator::make($request->all(), [
            'moneda_default' => 'required|string|in:PEN,USD,EUR',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $configuracionEmpresa->update([
                'moneda_default' => $request->moneda_default
            ]);

            return response()->json([
                'message' => 'Moneda por defecto actualizada exitosamente',
                'data' => $configuracionEmpresa->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar la moneda',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restablecer valores por defecto
     */
    public function restablecerDefecto(ConfiguracionEmpresa $configuracionEmpresa)
    {
        try {
            $configuracionEmpresa->update([
                'igv_porcentaje' => 18.00,
                'moneda_default' => 'PEN',
                'tolerancia_cuadratura' => 1.00,
                'retencion_porcentaje_default' => 3.00,
                'percepcion_porcentaje_default' => 2.00,
            ]);

            return response()->json([
                'message' => 'Configuración restablecida a valores por defecto',
                'data' => $configuracionEmpresa->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al restablecer la configuración',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener monedas disponibles
     */
    public function monedasDisponibles()
    {
        $monedas = [
            ['codigo' => 'PEN', 'nombre' => 'Soles', 'simbolo' => 'S/'],
            ['codigo' => 'USD', 'nombre' => 'Dólares', 'simbolo' => '$'],
            ['codigo' => 'EUR', 'nombre' => 'Euros', 'simbolo' => '€'],
        ];

        return response()->json([
            'data' => $monedas
        ]);
    }
}