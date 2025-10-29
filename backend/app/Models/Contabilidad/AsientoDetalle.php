<?php

namespace App\Models\Contabilidad;

use Illuminate\Database\Eloquent\Model;

class AsientoDetalle extends Model
{
    protected $fillable = [
        'asiento_id',
        'cuenta_id',
        'descripcion',
        'debe',
        'haber',
    ];

    protected $casts = [
        'debe' => 'decimal:2',
        'haber' => 'decimal:2',
    ];

    // Relaciones

    public function asiento()
    {
        return $this->belongsTo(Asiento::class);
    }

    public function planCuenta()
    {
        return $this->belongsTo(PlanCuenta::class);
    }

    public function esDebe(): bool
    {
        return $this->debe > 0;
    }

    public function esHaber(): bool
    {
        return $this->haber > 0;
    }
}
