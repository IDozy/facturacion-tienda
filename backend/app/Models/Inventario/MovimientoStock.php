<?php

namespace App\Models\Inventario;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MovimientoStock extends Model
{
    use HasFactory;

    protected $fillable = [
        'producto_id',
        'almacen_id',
        'tipo',
        'cantidad',
        'referencia_tipo', // 'entrada' o 'salida'
        'referencia_id',
        'observacion',
    ];

    protected $casts = [
        'cantidad' => 'decimal:2',
    ];

    // Relaciones
    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function almacen()
    {
        return $this->belongsTo(Almacen::class);
    }

    public function referencia()
    {
        return $this->morphTo();
    }


}
