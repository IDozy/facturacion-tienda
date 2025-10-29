<?php

namespace App\Models\Facturacion;

use App\Models\Inventario\Producto;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class ComprobanteDetalle extends Model
{
    use HasFactory;

    protected $fillable = [
        'comprobante_id',
        'producto_id',
        'descripcion',
        'cantidad',
        'precio_unitario',
        'tipo_afectacion',
        'subtotal',
        'igv',
        'total',
    ];

    // Relaciones

    public function comprobante()
    {
        return $this->belongsTo(Comprobante::class);
    }
    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }
}
