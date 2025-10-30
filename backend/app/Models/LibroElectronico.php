<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class LibroElectronico extends Model
{
    use HasFactory;

    protected $table = 'libros_electronicos';

    protected $fillable = [
        'periodo_contable_id',
        'tipo_libro',
        'archivo_txt',
        'hash_archivo',
        'estado',
        'fecha_generacion',
        'fecha_envio_sunat',
        'motivo_rechazo',
    ];

    protected $casts = [
        'fecha_generacion' => 'datetime',
        'fecha_envio_sunat' => 'datetime',
        'estado' => 'string',
    ];

    // === TIPOS DE LIBROS PLE SUNAT CORRECTOS ===
    const LIBRO_VENTAS = '050100';           // Registro de Ventas
    const LIBRO_COMPRAS = '080100';          // Registro de Compras
    const LIBRO_DIARIO = '080200';              // Libro Diario
    const LIBRO_MAYOR = '140100';               // Libro Mayor
    const REGISTRO_CAJA_BANCOS = '140200';      // Registro de Caja y Bancos

    // === ESTADOS ===
    const ESTADO_GENERADO = 'generado';
    const ESTADO_ENVIADO = 'enviado';
    const ESTADO_RECHAZADO = 'rechazado';

    // === RELACIONES ===
    public function periodoContable()
    {
        return $this->belongsTo(Contabilidad\PeriodoContable::class);
    }

    // === SCOPES ===
    public function scopeGenerados($query)
    {
        return $query->where('estado', self::ESTADO_GENERADO);
    }

    public function scopeEnviados($query)
    {
        return $query->where('estado', self::ESTADO_ENVIADO);
    }

    public function scopeRechazados($query)
    {
        return $query->where('estado', self::ESTADO_RECHAZADO);
    }

    public function scopePorTipo($query, $tipo)
    {
        return $query->where('tipo_libro', $tipo);
    }

    public function scopePendientesEnvio($query)
    {
        return $query->whereIn('estado', [self::ESTADO_GENERADO, self::ESTADO_RECHAZADO]);
    }

    // === ACCESOR ===
    public function getNombreLibroAttribute(): string
    {
        $nombres = [
            self::LIBRO_VENTAS => 'Registro de Ventas',
            self::LIBRO_COMPRAS => 'Registro de Compras',
            self::LIBRO_DIARIO => 'Libro Diario',
            self::LIBRO_MAYOR => 'Libro Mayor',
            self::REGISTRO_CAJA_BANCOS => 'Registro de Caja y Bancos',
        ];

        return $nombres[$this->tipo_libro] ?? 'Libro Desconocido';
    }

    // === GENERACIÓN DE ARCHIVO ===
    public function generarNombreArchivo(): string
    {
        $empresa = $this->periodoContable->empresa;
        $periodo = $this->periodoContable;

        // Formato SUNAT: LE + RUC + AÑO + MES + DIA + LIBRO + SECUENCIAL + EXT
        return sprintf(
            'LE%s%04d%02d%s%03d.txt',
            $empresa->ruc,
            $periodo->año,
            $periodo->mes,
            $this->tipo_libro,
            1 // Secuencial (puedes incrementarlo si generas múltiples)
        );
    }

    public function getRutaCompletaAttribute(): ?string
    {
        if (!$this->archivo_txt) return null;

        return storage_path("app/ple/{$this->periodoContable->año}/{$this->periodoContable->mes}/{$this->archivo_txt}");
    }

    // === GESTIÓN DE ESTADO ===
    public function marcarComoEnviado(): self
    {
        $this->update([
            'estado' => self::ESTADO_ENVIADO,
            'fecha_envio_sunat' => now(),
        ]);
        return $this;
    }

    public function marcarComoRechazado(?string $motivo = null): self
    {
        $this->update([
            'estado' => self::ESTADO_RECHAZADO,
            'motivo_rechazo' => $motivo,
        ]);
        return $this;
    }

    // === GENERACIÓN DE CONTENIDO (CORREGIDA) ===
    public function generarContenido(): string
    {
        switch ($this->tipo_libro) {
            case self::LIBRO_VENTAS:
                return $this->generarRegistroVentas();
            case self::LIBRO_COMPRAS:
                return $this->generarRegistroCompras();
            case self::LIBRO_DIARIO:
                return $this->generarLibroDiario();
            case self::LIBRO_MAYOR:
                return $this->generarLibroMayor();
            default:
                throw new \Exception("Tipo de libro no implementado: {$this->tipo_libro}");
        }
    }

    // === IMPLEMENTACIONES CORREGIDAS ===
    protected function generarRegistroVentas(): string
    {
        $comprobantes = $this->periodoContable->empresa->comprobantes()
            ->whereBetween('fecha_emision', [
                $this->periodoContable->fecha_inicio,
                $this->periodoContable->fecha_fin
            ])
            ->where('estado', '!=', 'anulado')
            ->orderBy('fecha_emision')
            ->orderBy('numero')
            ->get();

        $lineas = [];

        foreach ($comprobantes as $comprobante) {
            $linea = $this->formatearRegistroVenta($comprobante);
            $lineas[] = $linea;
        }

        return implode("\n", $lineas);
    }

    protected function formatearRegistroVenta(Facturacion\Comprobante $comprobante): string
    {
        // Formato PLE Registro de Ventas (050100) - POSICIONES FIJAS
        $fechaEmision = $comprobante->fecha_emision->format('dmY');
        $tipoComprobante = $comprobante->tipo_comprobante;
        $serieNumero = str_pad($comprobante->serie . '-' . $comprobante->numero, 38, ' ', STR_PAD_RIGHT);
        $tipoDocCliente = str_pad($comprobante->tipo_documento_cliente ?? '', 2, '0', STR_PAD_LEFT);
        $docCliente = str_pad($comprobante->numero_documento_cliente ?? '', 15, '0', STR_PAD_LEFT);
        $nombreCliente = str_pad($comprobante->razon_social_cliente ?? '', 100, ' ', STR_PAD_RIGHT);
        $baseImponible = str_pad(number_format($comprobante->subtotal_gravado, 2, '', ''), 15, '0', STR_PAD_LEFT);
        $igv = str_pad(number_format($comprobante->igv_total, 2, '', ''), 15, '0', STR_PAD_LEFT);
        $total = str_pad(number_format($comprobante->total, 2, '', ''), 15, '0', STR_PAD_LEFT);

        // Construir línea con posiciones fijas
        $linea = '';
        $linea .= str_pad('01', 2, '0', STR_PAD_LEFT); // Tipo operación
        $linea .= $fechaEmision; // Fecha emisión (8 chars)
        $linea .= str_pad($tipoComprobante, 2, '0', STR_PAD_LEFT); // Tipo comprobante
        $linea .= $serieNumero; // Serie-Número (38 chars)
        $linea .= $tipoDocCliente; // Tipo documento cliente
        $linea .= $docCliente; // Número documento cliente
        $linea .= $nombreCliente; // Nombre cliente
        $linea .= $baseImponible; // Base imponible
        $linea .= $igv; // IGV
        $linea .= $total; // Total
        // ... más campos según formato oficial

        return $linea;
    }

    protected function generarRegistroCompras(): string
    {
        $compras = $this->periodoContable->empresa->compras()
            ->whereBetween('fecha_emision', [
                $this->periodoContable->fecha_inicio,
                $this->periodoContable->fecha_fin
            ])
            ->where('estado', '!=', 'anulada')
            ->get();

        $lineas = [];

        foreach ($compras as $compra) {
            $linea = $this->formatearRegistroCompra($compra);
            $lineas[] = $linea;
        }

        return implode("\n", $lineas);
    }

    protected function formatearRegistroCompra(Compras\Compra $compra): string
    {
        // Implementar formato PLE Registro de Compras (080100)
        // Similar a ventas pero con datos del proveedor
        return ''; // Placeholder
    }

    protected function generarLibroDiario(): string
    {
        $asientos = $this->periodoContable->asientos()
            ->where('estado', 'registrado')
            ->get();

        $lineas = [];

        foreach ($asientos as $asiento) {
            foreach ($asiento->detalles as $detalle) {
                $linea = $this->formatearAsientoDiario($asiento, $detalle);
                $lineas[] = $linea;
            }
        }

        return implode("\n", $lineas);
    }

    protected function formatearAsientoDiario(Contabilidad\Asiento $asiento, Contabilidad\AsientoDetalle $detalle): string
    {
        // Implementar formato PLE Libro Diario (080200)
        return ''; // Placeholder
    }

    protected function generarLibroMayor(): string
    {
        // Implementar formato PLE Libro Mayor (140100)
        return '';
    }

    // === MÉTODO COMPLETO PARA GENERAR Y GUARDAR ===
    public function generarYGuardar(): self
    {
        $contenido = $this->generarContenido();
        $nombreArchivo = $this->generarNombreArchivo();

        // Crear directorio si no existe
        $directorio = "ple/{$this->periodoContable->año}/{$this->periodoContable->mes}";
        Storage::makeDirectory($directorio);

        // Guardar archivo
        $ruta = "$directorio/$nombreArchivo";
        Storage::disk('local')->put($ruta, $contenido);

        // Actualizar registro
        $this->update([
            'archivo_txt' => $ruta,
            'hash_archivo' => sha1($contenido),
            'fecha_generacion' => now(),
            'estado' => self::ESTADO_GENERADO,
        ]);

        return $this;
    }
}
