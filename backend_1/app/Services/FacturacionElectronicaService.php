<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use App\Models\Comprobante;
use App\Models\Empresa;
use Greenter\Model\Client\Client;
use Greenter\Model\Company\Company;
use Greenter\Model\Company\Address;
use Greenter\Model\Sale\Invoice;
use Greenter\Model\Sale\Legend;
use Greenter\Model\Sale\SaleDetail;
use Greenter\Ws\Services\SunatEndpoints;
use Greenter\See;
use DateTime;

class FacturacionElectronicaService
{
    private $see;
    private $empresa;

    public function __construct()
    {
        $this->empresa = Empresa::where('activo', true)->first();
        
        if (!$this->empresa) {
            throw new \Exception('No hay empresa configurada');
        }

        $this->configurarGreenter();
    }

    /**
     * Configurar Greenter con credenciales de SUNAT
     */
    private function configurarGreenter()
    {
        $this->see = new See();
        
        // Certificado digital (debe estar en storage)
        // Por ahora usamos uno de prueba
        $certificadoPath = storage_path('app/certificados/certificate.pem');
        
        // Si no existe el certificado, creamos uno temporal para pruebas
        if (!file_exists($certificadoPath)) {
            // En producción debes tener tu certificado real
            $this->see->setCertificate(file_get_contents(__DIR__.'/../../storage/app/certificados/certificate.pem'));
        }
        
        // Credenciales SUNAT
        $this->see->setService($this->empresa->modo_prueba ? SunatEndpoints::FE_BETA : SunatEndpoints::FE_PRODUCCION);
        $this->see->setClaveSOL($this->empresa->ruc, $this->empresa->usuario_sol, $this->empresa->clave_sol);
    }

    /**
     * Generar XML de factura/boleta
     */
    public function generarXML(Comprobante $comprobante): string
    {
        // Crear el invoice según el tipo de comprobante
        $invoice = $this->crearInvoice($comprobante);

        // Generar XML
        $result = $this->see->getXmlSigned($invoice);

        if (!$result->isSuccess()) {
            throw new \Exception('Error al generar XML: ' . $result->getError()->getMessage());
        }

        return $result->getXmlSigned();
    }

    /**
     * Enviar comprobante a SUNAT
     */
    public function enviarSunat(Comprobante $comprobante): array
    {
        try {
            // Generar XML
            $xml = $this->generarXML($comprobante);

             // Guardar el XML en el storage
            $nombreXml = "{$comprobante->nombre_xml}.xml";
            $pathXml = "comprobantes/xml/{$nombreXml}";
            \Illuminate\Support\Facades\Storage::put($pathXml, $xml);

            // Crear invoice
            $invoice = $this->crearInvoice($comprobante);

            // Enviar a SUNAT
            $result = $this->see->send($invoice);

            // Procesar respuesta
            if ($result->isSuccess()) {
                // Obtener CDR (Constancia de Recepción)
                $cdr = $result->getCdrResponse();
                
                $comprobante->update([
                    'xml' => base64_encode($xml),
                    'cdr' => base64_encode($result->getCdrZip()),
                    'hash' => (new \Greenter\Report\XmlUtils())->getHashSign($xml),
                    'estado_sunat' => 'aceptado',
                    'codigo_sunat' => $cdr->getCode(),
                    'mensaje_sunat' => $cdr->getDescription(),
                    'fecha_envio_sunat' => now(),
                ]);

                return [
                    'success' => true,
                    'codigo' => $cdr->getCode(),
                    'mensaje' => $cdr->getDescription(),
                ];
            } else {
                $error = $result->getError();
                
                $comprobante->update([
                    'estado_sunat' => 'rechazado',
                    'codigo_sunat' => $error->getCode(),
                    'mensaje_sunat' => $error->getMessage(),
                    'fecha_envio_sunat' => now(),
                ]);

                return [
                    'success' => false,
                    'codigo' => $error->getCode(),
                    'mensaje' => $error->getMessage(),
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'mensaje' => 'Error al enviar a SUNAT: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Crear objeto Invoice de Greenter
     */
    private function crearInvoice(Comprobante $comprobante): Invoice
    {
        $invoice = new Invoice();
        
        // Datos del comprobante
        $invoice
            ->setUblVersion('2.1')
            ->setTipoOperacion('0101') // Venta interna
            ->setTipoDoc($comprobante->tipo_comprobante)
            ->setSerie($comprobante->serie)
            ->setCorrelativo(str_pad($comprobante->correlativo, 8, '0', STR_PAD_LEFT))
            ->setFechaEmision(new DateTime($comprobante->fecha_emision))
            ->setTipoMoneda($comprobante->moneda);

        // Datos de la empresa
        $company = new Company();
        $company
            ->setRuc($this->empresa->ruc)
            ->setRazonSocial($this->empresa->razon_social)
            ->setNombreComercial($this->empresa->nombre_comercial ?? $this->empresa->razon_social);

        $address = new Address();
        $address
            ->setUbigueo($this->empresa->ubigeo)
            ->setDistrito($this->empresa->distrito)
            ->setProvincia($this->empresa->provincia)
            ->setDepartamento($this->empresa->departamento)
            ->setUrbanizacion($this->empresa->urbanizacion ?? '-')
            ->setDireccion($this->empresa->direccion)
            ->setCodLocal('0000'); // Código de establecimiento

        $company->setAddress($address);
        $invoice->setCompany($company);

        // Datos del cliente
        $client = new Client();
        $client
            ->setTipoDoc($comprobante->cliente->tipo_documento)
            ->setNumDoc($comprobante->cliente->numero_documento)
            ->setRznSocial($comprobante->cliente->nombre_razon_social);

        if ($comprobante->cliente->direccion) {
            $clientAddress = new Address();
            $clientAddress->setDireccion($comprobante->cliente->direccion);
            $client->setAddress($clientAddress);
        }

        $invoice->setClient($client);

        // Detalles (productos)
        $items = [];
        foreach ($comprobante->detalles as $detalle) {
            $item = new SaleDetail();
            $item
                ->setCodProducto($detalle->codigo_producto)
                ->setUnidad($detalle->unidad_medida)
                ->setDescripcion($detalle->descripcion)
                ->setCantidad($detalle->cantidad)
                ->setMtoValorUnitario($detalle->precio_unitario)
                ->setMtoValorVenta($detalle->subtotal)
                ->setMtoBaseIgv($detalle->subtotal)
                ->setPorcentajeIgv(18.00)
                ->setIgv($detalle->igv)
                ->setTipAfeIgv($detalle->tipo_igv)
                ->setTotalImpuestos($detalle->igv)
                ->setMtoPrecioUnitario($detalle->precio_venta);

            $items[] = $item;
        }

        $invoice->setDetails($items);

        // Totales
        $invoice
            ->setMtoOperGravadas($comprobante->total_gravada)
            ->setMtoOperExoneradas($comprobante->total_exonerada)
            ->setMtoOperInafectas($comprobante->total_inafecta)
            ->setMtoIGV($comprobante->total_igv)
            ->setTotalImpuestos($comprobante->total_igv)
            ->setValorVenta($comprobante->total_gravada)
            ->setSubTotal($comprobante->total_gravada + $comprobante->total_igv)
            ->setMtoImpVenta($comprobante->total);

        // Leyenda
        $legend = new Legend();
        $legend
            ->setCode('1000')
            ->setValue($this->numeroALetras($comprobante->total) . ' ' . $comprobante->moneda);

        $invoice->setLegends([$legend]);

        return $invoice;
    }

    /**
     * Convertir número a letras (para la leyenda)
     */
    private function numeroALetras(float $numero): string
    {
        // Implementación básica
        // En producción usa una librería como https://github.com/luecano/numero-a-letras
        $entero = floor($numero);
        $decimal = round(($numero - $entero) * 100);
        
        return strtoupper("SON $entero Y $decimal/100 SOLES");
    }

    /**
     * Generar código QR
     */
    public function generarQR(Comprobante $comprobante): string
    {
        $texto = implode('|', [
            $this->empresa->ruc,
            $comprobante->tipo_comprobante,
            $comprobante->serie,
            $comprobante->correlativo,
            $comprobante->total_igv,
            $comprobante->total,
            $comprobante->fecha_emision->format('Y-m-d'),
            $comprobante->cliente->tipo_documento,
            $comprobante->cliente->numero_documento,
        ]);

        // Retornar el texto para generar QR (después lo convertimos a imagen)
        return $texto;
    }
}
