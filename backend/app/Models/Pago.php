<?php

namespace App\Models;

use App\Models\Facturacion\Comprobante;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{
    use HasFactory;

    protected $fillable = [
        'comprobante_id',
        'medio_pago_id',
        'caja_id',
        'monto',
        'fecha_pago',
        'numero_referencia',
        'estado',
        'fecha_confirmacion',
        'cuota_numero',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'fecha_pago' => 'date',
        'fecha_confirmacion' => 'datetime',
        'cuota_numero' => 'integer',
    ];

    // Boot method
    protected static function booted()
    {
        static::creating(function ($pago) {
            // Validar que el monto no exceda el saldo pendiente
            if ($pago->monto > $pago->comprobante->saldo_pendiente) {
                throw new \Exception('El monto del pago excede el saldo pendiente');
            }
        });

        static::created(function ($pago) {
            // Actualizar saldo pendiente del comprobante
            $pago->comprobante->actualizarSaldoPendiente();
        });

        static::updated(function ($pago) {
            // Actualizar saldo pendiente si cambió el estado
            if ($pago->isDirty('estado')) {
                $pago->comprobante->actualizarSaldoPendiente();
            }
        });
    }

    // Relaciones
    public function comprobante()
    {
        return $this->belongsTo(Facturacion\Comprobante::class);
    }

    public function medioPago()
    {
        return $this->belongsTo(MedioPago::class, 'medio_pago_id');
    }

    public function caja()
    {
        return $this->belongsTo(Caja::class);
    }

    // Scopes
    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }

    public function scopeConfirmados($query)
    {
        return $query->where('estado', 'confirmado');
    }

    public function scopeDelPeriodo($query, $fechaInicio, $fechaFin)
    {
        return $query->whereBetween('fecha_pago', [$fechaInicio, $fechaFin]);
    }

    // Métodos
    public function confirmar()
    {
        if ($this->estado === 'confirmado') {
            return $this;
        }

        $this->update([
            'estado' => 'confirmado',
            'fecha_confirmacion' => now(),
        ]);

        return $this;
    }

    public function anular()
    {
        if ($this->estado === 'anulado') {
            return $this;
        }

        $this->update([
            'estado' => 'anulado',
        ]);

        return $this;
    }

    public function esPendiente()
    {
        return $this->estado === 'pendiente';
    }

    public function esConfirmado()
    {
        return $this->estado === 'confirmado';
    }
}