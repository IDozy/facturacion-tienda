<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Facturacion\ComprobanteDetalle;
use App\Enums\TipoComprobanteEnum;
use App\Enums\EstadoSunatEnum;

class Comprobante extends Model
{
    use SoftDeletes;

    protected $table = 'comprobantes';

    protected $fillable = [
        'empresa_id',
        'cliente_id',
        'usuario_id',
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
        'pdf_url',
        'nombre_xml',
        'nombre_cdr',
        'estado_sunat',
        'codigo_sunat',
        'mensaje_sunat',
        'fecha_envio_sunat',
        'ruta_pdf',
        'comprobante_relacionado_id',
        'motivo_nota',
        'tipo_nota',
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

    // ========================================
    // RELACIONES
    // ========================================
    
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

    // ========================================
    // SCOPES - TIPOS DE COMPROBANTES
    // ========================================
    
    public function scopeFacturas($query)
    {
        return $query->where('tipo_comprobante', '01');
    }

    public function scopeBoletas($query)
    {
        return $query->where('tipo_comprobante', '03');
    }

    public function scopeNotasCredito($query)
    {
        return $query->where('tipo_comprobante', '07');
    }

    public function scopeNotasDebito($query)
    {
        return $query->where('tipo_comprobante', '08');
    }

    public function scopeGuiasRemision($query)
    {
        return $query->where('tipo_comprobante', '09');
    }

    public function scopeComprobantesRetencion($query)
    {
        return $query->where('tipo_comprobante', '20');
    }

    public function scopeComprobantesPercepcion($query)
    {
        return $query->where('tipo_comprobante', '40');
    }

    // ========================================
    // SCOPES - ESTADOS
    // ========================================
    
    public function scopeAceptados($query)
    {
        return $query->where('estado_sunat', 'aceptado');
    }

    public function scopePendientes($query)
    {
        return $query->where('estado_sunat', 'pendiente');
    }

    public function scopeRechazados($query)
    {
        return $query->where('estado_sunat', 'rechazado');
    }

    public function scopeAnulados($query)
    {
        return $query->where('estado_sunat', 'anulado');
    }

    // ========================================
    // SCOPES - FILTROS
    // ========================================
    
    public function scopeEntreFechas($query, $desde, $hasta)
    {
        return $query->whereBetween('fecha_emision', [$desde, $hasta]);
    }

    public function scopeDelMes($query, $year, $month)
    {
        return $query->whereYear('fecha_emision', $year)
                    ->whereMonth('fecha_emision', $month);
    }

    public function scopeDelAnio($query, $year)
    {
        return $query->whereYear('fecha_emision', $year);
    }

    public function scopePorCliente($query, $clienteId)
    {
        return $query->where('cliente_id', $clienteId);
    }

    public function scopePorSerie($query, $serie)
    {
        return $query->where('serie', $serie);
    }

    public function scopeBuscar($query, $termino)
    {
        return $query->where(function($q) use ($termino) {
            $q->where('serie', 'like', "%{$termino}%")
              ->orWhere('correlativo', 'like', "%{$termino}%")
              ->orWhereHas('cliente', function($q) use ($termino) {
                  $q->where('nombre_comercial', 'like', "%{$termino}%")
                    ->orWhere('numero_documento', 'like', "%{$termino}%");
              });
        });
    }

    // ========================================
    // ACCESSORS - INFORMACIÓN
    // ========================================
    
    public function getNumeroCompletoAttribute()
    {
        return sprintf('%s-%08d', $this->serie, $this->correlativo);
    }

    public function getTipoComprobanteNombreAttribute()
    {
        $tipos = [
            '01' => 'Factura Electrónica',
            '03' => 'Boleta de Venta Electrónica',
            '07' => 'Nota de Crédito Electrónica',
            '08' => 'Nota de Débito Electrónica',
            '09' => 'Guía de Remisión Electrónica',
            '20' => 'Comprobante de Retención',
            '40' => 'Comprobante de Percepción',
        ];

        return $tipos[$this->tipo_comprobante] ?? 'Desconocido';
    }

    public function getTipoComprobanteShortAttribute()
    {
        $tipos = [
            '01' => 'Factura',
            '03' => 'Boleta',
            '07' => 'NC',
            '08' => 'ND',
            '09' => 'Guía',
        ];

        return $tipos[$this->tipo_comprobante] ?? 'N/A';
    }

    // ========================================
    // ACCESSORS - TIPOS DE COMPROBANTE
    // ========================================
    
    public function getEsFacturaAttribute()
    {
        return $this->tipo_comprobante === '01';
    }

    public function getEsBoletaAttribute()
    {
        return $this->tipo_comprobante === '03';
    }

    public function getEsNotaCreditoAttribute()
    {
        return $this->tipo_comprobante === '07';
    }

    public function getEsNotaDebitoAttribute()
    {
        return $this->tipo_comprobante === '08';
    }

    public function getEsGuiaRemisionAttribute()
    {
        return $this->tipo_comprobante === '09';
    }

    public function getEsDocumentoVentaAttribute()
    {
        return in_array($this->tipo_comprobante, ['01', '03']);
    }

    public function getEsNotaAttribute()
    {
        return in_array($this->tipo_comprobante, ['07', '08']);
    }

    // ========================================
    // ACCESSORS - ESTADOS SUNAT
    // ========================================
    
    public function getAceptadoSunatAttribute()
    {
        return $this->estado_sunat === 'aceptado';
    }

    public function getRechazadoSunatAttribute()
    {
        return $this->estado_sunat === 'rechazado';
    }

    public function getPendienteEnvioAttribute()
    {
        return $this->estado_sunat === 'pendiente';
    }

    public function getAnuladoAttribute()
    {
        return $this->estado_sunat === 'anulado';
    }

    // ========================================
    // MÉTODOS DE VALIDACIÓN DE ESTADO
    // ========================================
    
    public function puedeSerEnviado(): bool
    {
        return $this->estado_sunat === 'pendiente';
    }

    public function puedeSerAnulado(): bool
    {
        return in_array($this->estado_sunat, ['aceptado', 'pendiente']) 
               && !$this->anulado;
    }

    public function puedeGenerarNotaCredito(): bool
    {
        return $this->es_documento_venta 
               && $this->aceptado_sunat
               && !$this->anulado;
    }

    public function puedeGenerarNotaDebito(): bool
    {
        return $this->es_documento_venta 
               && $this->aceptado_sunat
               && !$this->anulado;
    }

    public function tieneNotasAsociadas(): bool
    {
        return $this->notasCredito()->exists() 
               || $this->notasDebito()->exists();
    }

    // ========================================
    // MÉTODOS DE NEGOCIO
    // ========================================
    
    public function calcularSaldo(): float
    {
        $saldo = $this->total;
        
        // Restar notas de crédito
        $notasCredito = $this->notasCredito()
            ->where('estado_sunat', 'aceptado')
            ->sum('total');
        $saldo -= $notasCredito;
        
        // Sumar notas de débito
        $notasDebito = $this->notasDebito()
            ->where('estado_sunat', 'aceptado')
            ->sum('total');
        $saldo += $notasDebito;
        
        return round($saldo, 2);
    }

    public function estaPagado(): bool
    {
        // Aquí irá la lógica de pagos cuando implementes ese módulo
        return false; // TODO: Implementar cuando tengas tabla de pagos
    }

    public function diasVencidos(): int
    {
        if (!$this->fecha_vencimiento) {
            return 0;
        }
        
        $hoy = now();
        $vencimiento = $this->fecha_vencimiento;
        
        if ($hoy <= $vencimiento) {
            return 0;
        }
        
        return $hoy->diffInDays($vencimiento);
    }

    public function estaVencido(): bool
    {
        return $this->diasVencidos() > 0;
    }

    // ========================================
    // MUTATORS
    // ========================================
    
    public function setXmlAttribute($value)
    {
        if (!$value) {
            $this->attributes['xml'] = null;
            return;
        }
        
        // Auto-encode si no está encoded
        $this->attributes['xml'] = base64_decode($value, true) === false 
            ? base64_encode($value) 
            : $value;
    }

    public function getXmlAttribute($value)
    {
        return $value ? base64_decode($value) : null;
    }

    public function setCdrAttribute($value)
    {
        if (!$value) {
            $this->attributes['cdr'] = null;
            return;
        }
        
        $this->attributes['cdr'] = base64_decode($value, true) === false 
            ? base64_encode($value) 
            : $value;
    }

    public function getCdrAttribute($value)
    {
        return $value ? base64_decode($value) : null;
    }

    // ========================================
    // MÉTODOS ESTÁTICOS
    // ========================================
    
    public static function tiposComprobante(): array
    {
        return [
            '01' => 'Factura Electrónica',
            '03' => 'Boleta de Venta Electrónica',
            '07' => 'Nota de Crédito Electrónica',
            '08' => 'Nota de Débito Electrónica',
            '09' => 'Guía de Remisión Electrónica',
            '20' => 'Comprobante de Retención',
            '40' => 'Comprobante de Percepción',
        ];
    }

    public static function estadosSunat(): array
    {
        return [
            'pendiente' => 'Pendiente de envío',
            'enviado' => 'Enviado a SUNAT',
            'aceptado' => 'Aceptado por SUNAT',
            'rechazado' => 'Rechazado por SUNAT',
            'anulado' => 'Anulado',
        ];
    }
}