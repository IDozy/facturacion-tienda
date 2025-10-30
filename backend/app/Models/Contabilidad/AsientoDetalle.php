<?php

namespace App\Models\Contabilidad;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AsientoDetalle extends Model
{
    use HasFactory;

    protected $table = 'asiento_detalles';

    protected $fillable = [
        'asiento_id',
        'cuenta_id',
        'descripcion',
        'debe',
        'haber',
    ];

    protected function casts(): array
    {
        return [
            'debe' => 'decimal:2',
            'haber' => 'decimal:2',
        ];
    }

    // === VALIDACIÓN + ACTUALIZACIÓN AUTOMÁTICA ===
    protected static function booted()
    {
        static::creating(function ($detalle) {
            if ($detalle->debe > 0 && $detalle->haber > 0) {
                throw new \Exception('No se puede tener valores en debe y haber al mismo tiempo');
            }
            if ($detalle->debe == 0 && $detalle->haber == 0) {
                throw new \Exception('Debe especificar un monto en debe o haber');
            }
        });

        $updateTotals = function ($detalle) {
            if ($detalle->asiento) {
                Cache::forget("asiento_{$detalle->asiento->id}_totales");
                $detalle->asiento->calcularTotales();
            }
        };

        static::created($updateTotals);
        static::updated($updateTotals);
        static::deleted($updateTotals);
    }

    // === RELACIONES ===
    public function asiento()
    {
        return $this->belongsTo(Asiento::class);
    }

    public function cuenta()
    {
        return $this->belongsTo(PlanCuenta::class, 'cuenta_id');
    }

    // === ESTADO ===
    public function esCargo(): bool
    {
        return $this->debe > 0;
    }
    public function esAbono(): bool
    {
        return $this->haber > 0;
    }

    public function getMontoAttribute(): float
    {
        return $this->debe > 0 ? $this->debe : $this->haber;
    }

    public function getTipoMovimientoAttribute(): string
    {
        return $this->esCargo() ? 'Cargo' : 'Abono';
    }

    public function getColorAttribute(): string
    {
        return $this->esCargo() ? 'text-success' : 'text-danger';
    }

    // === ACCIONES ===
    public function cambiarTipo(): self
    {
        if ($this->esCargo()) {
            $this->haber = $this->debe;
            $this->debe = 0;
        } else {
            $this->debe = $this->haber;
            $this->haber = 0;
        }
        $this->save();
        return $this;
    }

    public function esDeCuenta(string $codigo): bool
    {
        return $this->cuenta?->codigo === $codigo;
    }

    public function getCuentaCompletaAttribute(): string
    {
        return $this->cuenta?->codigo_completo . ' ' . $this->cuenta?->nombre ?? 'Cuenta eliminada';
    }
}
