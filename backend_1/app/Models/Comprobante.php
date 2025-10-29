<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\Facturacion\ComprobanteDetalle;

class Comprobante extends Model
{
    use SoftDeletes;

    protected $table = 'comprobantes';

    protected $fillable = [
        'empresa_id',            // agregado
        'cliente_id',
        'usuario_id',            // agregado
        'tipo_comprobante',
        'serie',
        'correlativo',
        'fecha_emision',
        'fecha_vencimiento',
        'hora_emision',
        'moneda',
        'tipo_cambio',
        'total_gravada',
        'total_exonerada',
        'total_inafecta',
        'total_gratuita',
        'total_igv',
        'total_descuentos',
        'total',
        'observaciones',
        'hash',
        'codigo_qr',
        'xml',
        'cdr',
        'estado_sunat',
        'codigo_sunat',
        'mensaje_sunat',
        'fecha_envio_sunat',
        'ruta_pdf',
        'comprobante_relacionado_id',
        'motivo_nota',
        'nombre_xml',            // agregado
    ];

    protected $casts = [
        'fecha_emision' => 'date',
        'fecha_vencimiento' => 'date',
        'fecha_envio_sunat' => 'datetime',
        'tipo_cambio' => 'decimal:3',
        'total_gravada' => 'decimal:2',
        'total_exonerada' => 'decimal:2',
        'total_inafecta' => 'decimal:2',
        'total_gratuita' => 'decimal:2',
        'total_igv' => 'decimal:2',
        'total_descuentos' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    // Relaciones
    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class);
    }

    public function detalles()
    {
        return $this->hasMany(ComprobanteDetalle::class);
    }

    public function comprobanteRelacionado()
    {
        return $this->belongsTo(Comprobante::class, 'comprobante_relacionado_id');
    }

    public function notasCredito()
    {
        return $this->hasMany(Comprobante::class, 'comprobante_relacionado_id')
                    ->where('tipo_comprobante', '07');
    }

    public function notasDebito()
    {
        return $this->hasMany(Comprobante::class, 'comprobante_relacionado_id')
                    ->where('tipo_comprobante', '08');
    }

    // Accesores
    public function getNumeroCompletoAttribute()
    {
        return $this->serie . '-' . str_pad($this->correlativo, 6, '0', STR_PAD_LEFT);
    }

    public function getTipoComprobanteNombreAttribute()
    {
        $tipos = [
            '01' => 'Factura Electrónica',
            '03' => 'Boleta de Venta Electrónica',
            '07' => 'Nota de Crédito Electrónica',
            '08' => 'Nota de Débito Electrónica',
        ];

        return $tipos[$this->tipo_comprobante] ?? 'Desconocido';
    }

    public function getEsFacturaAttribute() { return $this->tipo_comprobante === '01'; }
    public function getEsBoletaAttribute() { return $this->tipo_comprobante === '03'; }
    public function getEsNotaCreditoAttribute() { return $this->tipo_comprobante === '07'; }
    public function getEsNotaDebitoAttribute() { return $this->tipo_comprobante === '08'; }

    public function getAceptadoSunatAttribute() { return $this->estado_sunat === 'aceptado'; }
    public function getRechazadoSunatAttribute() { return $this->estado_sunat === 'rechazado'; }
    public function getPendienteEnvioAttribute() { return $this->estado_sunat === 'pendiente'; }
}
