<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfiguracionEmpresa extends Model
{
    use HasFactory;

    protected $table = 'configuraciones_empresa';

    protected $fillable = [
        'empresa_id',
        'igv_porcentaje',
        'moneda_default',
        'tolerancia_cuadratura',
        'retencion_porcentaje_default',
        'percepcion_porcentaje_default',
    ];

    protected function casts(): array
    {
        return [
            'igv_porcentaje' => 'decimal:2',
            'tolerancia_cuadratura' => 'decimal:2',
            'retencion_porcentaje_default' => 'decimal:2',
            'percepcion_porcentaje_default' => 'decimal:2',
        ];
    }

    // === VALORES POR DEFECTO ===
    protected $attributes = [
        'igv_porcentaje' => 18.00,
        'moneda_default' => 'PEN',
        'tolerancia_cuadratura' => 1.00,
        'retencion_porcentaje_default' => 0.00,
        'percepcion_porcentaje_default' => 0.00,
    ];

    // === RELACIONES ===
    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    // === MÉTODOS DE CÁLCULO ===
    public function calcularIgv(float $monto): float
    {
        return round($monto * $this->getIgvRate(), 2);
    }

    public function calcularMontoConIgv(float $montoBase): float
    {
        return round($montoBase * (1 + $this->getIgvRate()), 2);
    }

    public function calcularMontoSinIgv(float $montoConIgv): float
    {
        return round($montoConIgv / (1 + $this->getIgvRate()), 2);
    }

    public function calcularRetencion(float $monto): float
    {
        return round($monto * ($this->retencion_porcentaje_default / 100), 2);
    }

    public function calcularPercepcion(float $monto): float
    {
        return round($monto * ($this->percepcion_porcentaje_default / 100), 2);
    }

    public function getIgvRate(): float
    {
        return $this->igv_porcentaje / 100;
    }

    // === ACCESO RÁPIDO ===
    public static function deEmpresa(int $empresaId): ?self
    {
        return static::where('empresa_id', $empresaId)->first();
    }

    public static function igvDeEmpresa(int $empresaId): float
    {
        return static::deEmpresa($empresaId)?->igv_porcentaje ?? 18.00;
    }
}
