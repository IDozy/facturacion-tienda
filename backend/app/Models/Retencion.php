<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Retencion extends Model
{
    use HasFactory;

    protected $table = 'retenciones';

    protected $fillable = [
        'comprobante_id',
        'tipo',
        'monto',
        'porcentaje',
        'estado',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'porcentaje' => 'decimal:2',
        'tipo' => 'string',
        'estado' => 'string',
    ];

    protected $attributes = [
        'estado' => 'pendiente',
    ];

    // === RELACIONES ===
    public function comprobante()
    {
        return $this->belongsTo(Facturacion\Comprobante::class);
    }

    // === SCOPES ===
    public function scopeRetenciones($query)
    {
        return $query->where('tipo', 'retencion');
    }

    public function scopePercepciones($query)
    {
        return $query->where('tipo', 'percepcion');
    }

    public function scopeActivas($query)
    {
        return $query->where('estado', 'aplicada');
    }

    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }

    // === MÉTODOS DE TIPO ===
    public function esRetencion(): bool
    {
        return $this->tipo === 'retencion';
    }

    public function esPercepcion(): bool
    {
        return $this->tipo === 'percepcion';
    }

    // === CÁLCULO INTELIGENTE ===
    public function calcularMonto(?float $montoBase = null): float
    {
        $config = $this->comprobante->empresa->configuracion;

        $porcentaje = $this->porcentaje ?? (
            $this->esRetencion()
                ? $config->retencion_porcentaje_default
                : $config->percepcion_porcentaje_default
        );

        $montoBase = $montoBase ?? $this->comprobante->total;
        return round($montoBase * ($porcentaje / 100), 2);
    }

    // === ESTADOS ===
    public function aplicar(): self
    {
        if ($this->estado === 'aplicada') {
            return $this;
        }

        $this->update([
            'estado' => 'aplicada',
            'monto' => $this->calcularMonto(),
        ]);

        return $this;
    }

    public function anular(): self
    {
        if ($this->estado === 'anulada') {
            return $this;
        }

        $this->update(['estado' => 'anulada']);
        return $this;
    }

    public function esAplicada(): bool
    {
        return $this->estado === 'aplicada';
    }

    public function esPendiente(): bool
    {
        return $this->estado === 'pendiente';
    }
}