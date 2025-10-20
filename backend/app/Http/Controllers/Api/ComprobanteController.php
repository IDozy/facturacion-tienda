<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comprobante;
use App\Models\ComprobanteDetalle;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Serie;
use App\Models\Empresa;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ComprobanteController extends Controller
{
    /**
     * Listar comprobantes
     * GET /api/comprobantes
     */
    public function index(Request $request): JsonResponse
    {
        $query = Comprobante::with(['cliente', 'empresa'])
            ->orderBy('fecha_emision', 'desc')
            ->orderBy('correlativo', 'desc');

        // Filtros opcionales
        if ($request->has('tipo_comprobante')) {
            $query->where('tipo_comprobante', $request->tipo_comprobante);
        }

        if ($request->has('estado_sunat')) {
            $query->where('estado_sunat', $request->estado_sunat);
        }

        if ($request->has('fecha_desde')) {
            $query->where('fecha_emision', '>=', $request->fecha_desde);
        }

        if ($request->has('fecha_hasta')) {
            $query->where('fecha_emision', '<=', $request->fecha_hasta);
        }

        $comprobantes = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $comprobantes,
            'message' => 'Comprobantes obtenidos correctamente'
        ]);
    }

    /**
     * Ver un comprobante específico
     * GET /api/comprobantes/{id}
     */
    public function show(string $id): JsonResponse
    {
        $comprobante = Comprobante::with(['cliente', 'empresa', 'detalles.producto'])
            ->find($id);

        if (!$comprobante) {
            return response()->json([
                'success' => false,
                'message' => 'Comprobante no encontrado'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $comprobante,
            'message' => 'Comprobante obtenido correctamente'
        ]);
    }

    /**
     * Crear un nuevo comprobante (Factura/Boleta)
     * POST /api/comprobantes
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'tipo_comprobante' => 'required|string|size:2|in:01,03,07,08',
            'fecha_emision' => 'required|date',
            'fecha_vencimiento' => 'nullable|date',
            'moneda' => 'required|string|size:3|in:PEN,USD',
            'tipo_cambio' => 'nullable|numeric|min:0',
            'observaciones' => 'nullable|string',
            'detalles' => 'required|array|min:1',
            'detalles.*.producto_id' => 'required|exists:productos,id',
            'detalles.*.cantidad' => 'required|numeric|min:0.01',
            'detalles.*.precio_unitario' => 'required|numeric|min:0',
            'detalles.*.descuento' => 'nullable|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            // Obtener empresa
            $empresa = Empresa::where('activo', true)->first();
            
            if (!$empresa) {
                throw new \Exception('No hay empresa configurada');
            }

            // Obtener cliente
            $cliente = Cliente::findOrFail($validated['cliente_id']);

            // Obtener serie por defecto para el tipo de comprobante
            $serie = Serie::where('empresa_id', $empresa->id)
                ->where('tipo_comprobante', $validated['tipo_comprobante'])
                ->where('activo', true)
                ->where('por_defecto', true)
                ->first();

            if (!$serie) {
                throw new \Exception('No hay serie configurada para este tipo de comprobante');
            }

            // Obtener siguiente correlativo
            $correlativo = $serie->obtenerSiguienteCorrelativo();

            // Calcular totales
            $totales = $this->calcularTotales($validated['detalles']);

            // Crear comprobante
            $comprobante = Comprobante::create([
                'empresa_id' => $empresa->id,
                'cliente_id' => $validated['cliente_id'],
                'tipo_comprobante' => $validated['tipo_comprobante'],
                'serie' => $serie->serie,
                'correlativo' => $correlativo,
                'fecha_emision' => $validated['fecha_emision'],
                'fecha_vencimiento' => $validated['fecha_vencimiento'] ?? null,
                'hora_emision' => now()->format('H:i:s'),
                'moneda' => $validated['moneda'],
                'tipo_cambio' => $validated['tipo_cambio'] ?? 1.000,
                'total_gravada' => $totales['total_gravada'],
                'total_exonerada' => $totales['total_exonerada'],
                'total_inafecta' => $totales['total_inafecta'],
                'total_igv' => $totales['total_igv'],
                'total_descuentos' => $totales['total_descuentos'],
                'total' => $totales['total'],
                'observaciones' => $validated['observaciones'] ?? null,
                'estado_sunat' => 'pendiente',
            ]);

            // Crear detalles
            foreach ($validated['detalles'] as $index => $detalle) {
                $producto = Producto::findOrFail($detalle['producto_id']);
                
                $cantidad = $detalle['cantidad'];
                $precio_unitario = $detalle['precio_unitario'];
                $descuento = $detalle['descuento'] ?? 0;
                
                $subtotal = ($cantidad * $precio_unitario) - $descuento;
                $igv = $producto->tipo_igv === '10' ? $subtotal * ($producto->porcentaje_igv / 100) : 0;
                $total = $subtotal + $igv;

                ComprobanteDetalle::create([
                    'comprobante_id' => $comprobante->id,
                    'producto_id' => $producto->id,
                    'item' => $index + 1,
                    'codigo_producto' => $producto->codigo,
                    'descripcion' => $producto->descripcion,
                    'unidad_medida' => $producto->unidad_medida,
                    'cantidad' => $cantidad,
                    'precio_unitario' => $precio_unitario,
                    'precio_venta' => $precio_unitario * (1 + ($producto->porcentaje_igv / 100)),
                    'descuento' => $descuento,
                    'tipo_igv' => $producto->tipo_igv,
                    'porcentaje_igv' => $producto->porcentaje_igv,
                    'igv' => $igv,
                    'subtotal' => $subtotal,
                    'total' => $total,
                ]);

                // Descontar stock
                $producto->decrement('stock', $cantidad);
            }

            DB::commit();

            // Cargar relaciones
            $comprobante->load(['cliente', 'empresa', 'detalles.producto']);

            return response()->json([
                'success' => true,
                'data' => $comprobante,
                'message' => 'Comprobante creado correctamente'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al crear comprobante: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Anular un comprobante
     * POST /api/comprobantes/{id}/anular
     */
    public function anular(string $id): JsonResponse
    {
        $comprobante = Comprobante::find($id);

        if (!$comprobante) {
            return response()->json([
                'success' => false,
                'message' => 'Comprobante no encontrado'
            ], 404);
        }

        if ($comprobante->estado_sunat === 'anulado') {
            return response()->json([
                'success' => false,
                'message' => 'El comprobante ya está anulado'
            ], 422);
        }

        $comprobante->update([
            'estado_sunat' => 'anulado'
        ]);

        return response()->json([
            'success' => true,
            'data' => $comprobante,
            'message' => 'Comprobante anulado correctamente'
        ]);
    }

    /**
     * Calcular totales de los detalles
     */
    private function calcularTotales(array $detalles): array
    {
        $total_gravada = 0;
        $total_exonerada = 0;
        $total_inafecta = 0;
        $total_igv = 0;
        $total_descuentos = 0;

        foreach ($detalles as $detalle) {
            $producto = Producto::find($detalle['producto_id']);
            
            $cantidad = $detalle['cantidad'];
            $precio_unitario = $detalle['precio_unitario'];
            $descuento = $detalle['descuento'] ?? 0;
            
            $subtotal = ($cantidad * $precio_unitario) - $descuento;
            $total_descuentos += $descuento;

            if ($producto->tipo_igv === '10') {
                // Gravado con IGV
                $total_gravada += $subtotal;
                $total_igv += $subtotal * ($producto->porcentaje_igv / 100);
            } elseif ($producto->tipo_igv === '20') {
                // Exonerado
                $total_exonerada += $subtotal;
            } elseif ($producto->tipo_igv === '30') {
                // Inafecto
                $total_inafecta += $subtotal;
            }
        }

        $total = $total_gravada + $total_exonerada + $total_inafecta + $total_igv;

        return [
            'total_gravada' => round($total_gravada, 2),
            'total_exonerada' => round($total_exonerada, 2),
            'total_inafecta' => round($total_inafecta, 2),
            'total_igv' => round($total_igv, 2),
            'total_descuentos' => round($total_descuentos, 2),
            'total' => round($total, 2),
        ];
    }


    /**
     * Enviar comprobante a SUNAT
     * POST /api/comprobantes/{id}/enviar-sunat
     */
    public function enviarSunat(string $id): JsonResponse
    {
        $comprobante = Comprobante::with(['cliente', 'empresa', 'detalles'])->find($id);

        if (!$comprobante) {
            return response()->json([
                'success' => false,
                'message' => 'Comprobante no encontrado'
            ], 404);
        }

        if ($comprobante->estado_sunat === 'aceptado') {
            return response()->json([
                'success' => false,
                'message' => 'El comprobante ya fue aceptado por SUNAT'
            ], 422);
        }

        try {
            $service = new \App\Services\FacturacionElectronicaService();
            $resultado = $service->enviarSunat($comprobante);

            // Recargar comprobante
            $comprobante->refresh();

            return response()->json([
                'success' => $resultado['success'],
                'data' => $comprobante,
                'message' => $resultado['mensaje']
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al enviar a SUNAT: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generar el XML del comprobante y guardarlo en storage/app/comprobantes/xml
     * POST /api/comprobantes/{id}/generar-xml
     */
    public function generarXML(string $id)
    {
        $comprobante = Comprobante::with(['cliente', 'empresa', 'detalles'])->find($id);

        if (!$comprobante) {
            return response()->json([
                'success' => false,
                'message' => 'Comprobante no encontrado'
            ], 404);
        }

        try {
            // Instanciar el servicio que genera el XML
            $service = new \App\Services\FacturacionElectronicaService();
            $xml = $service->generarXML($comprobante);

            if (!$xml) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al generar el XML'
                ], 500);
            }

            // ✅ Nombre del archivo: F001-1.xml, B001-2.xml, etc.
            $nombreArchivo = "{$comprobante->serie}-{$comprobante->correlativo}.xml";

            // ✅ Guardar archivo físico en storage/app/comprobantes/xml/
            $ruta = "comprobantes/xml/{$nombreArchivo}";
            \Illuminate\Support\Facades\Storage::disk('local')->put($ruta, $xml);

            // ✅ Guardar nombre y XML codificado en BD
            $comprobante->update([
                'nombre_xml' => "{$comprobante->serie}-{$comprobante->correlativo}",
                'xml' => base64_encode($xml),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'XML generado correctamente',
                'archivo' => $nombreArchivo
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar XML: ' . $e->getMessage()
            ], 500);
        }
    }










        /**
     * Ver o descargar el XML del comprobante
     * GET /api/comprobantes/{id}/xml
     */
    public function verXML(string $id)
    {
        $comprobante = Comprobante::find($id);

        if (!$comprobante) {
            return response()->json([
                'success' => false,
                'message' => 'Comprobante no encontrado'
            ], 404);
        }

        // Ruta donde se guarda el XML (ajústala según tu estructura)
        $rutaXml = storage_path("app/comprobantes/xml/{$comprobante->nombre_xml}.xml");

        if (!file_exists($rutaXml)) {
            return response()->json([
                'success' => false,
                'message' => 'Archivo XML no encontrado'
            ], 404);
        }

        // Opción 1: mostrar el XML en el navegador
        return response()->file($rutaXml, [
            'Content-Type' => 'application/xml'
        ]);

        // Opción 2: si prefieres forzar la descarga, usa esto en su lugar:
        // return response()->download($rutaXml, "{$comprobante->nombre_xml}.xml");
    }

}
