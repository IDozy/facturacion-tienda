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
        'caja_id',
        'medio_pago_id',
        'monto',
        'fecha_pago',
        'observacion',
    ];

    protected $casts = [
        'fecha_pago' => 'datetime',
        'monto' => 'decimal:2',
    ];

    // Relaciones
    public function comprobante()
    {
        return $this->belongsTo(Comprobante::class);
    }

    public function caja()
    {
        return $this->belongsTo(Caja::class);
    }

    public function medioPago()
    {
        return $this->belongsTo(MedioPago::class);
    }
}
