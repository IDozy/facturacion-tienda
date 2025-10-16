<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComprobanteDetalle extends Model
{
    protected $table = 'comprobante_detalles';

    protected $fillable = [
        'comprobante_id',
        'producto_id',
        'item',
        'codigo_producto',
        'descripcion',
        'unidad_medida',
        'cantidad',
        'precio_unitario',
        'precio_venta',
        'descuento',
        'porcentaje_descuento',
        'tipo_igv',
        'porcentaje_igv',
        'igv',
        'subtotal',
        'total',
    ];

    protected $casts = [
        'cantidad' => 'decimal:2',
        'precio_unitario' => 'decimal:2',
        'precio_venta' => 'decimal:2',
        'descuento' => 'decimal:2',
        'porcentaje_descuento' => 'decimal:2',
        'porcentaje_igv' => 'decimal:2',
        'igv' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'total' => 'decimal:2',
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

    // Accesores
    public function getValorVentaAttribute()
    {
        return $this->cantidad * $this->precio_unitario;
    }

    public function getDescuentoAplicadoAttribute()
    {
        if ($this->porcentaje_descuento > 0) {
            return $this->valor_venta * ($this->porcentaje_descuento / 100);
        }
        return $this->descuento;
    }

    public function getBaseImponibleAttribute()
    {
        return $this->valor_venta - $this->descuento_aplicado;
    }
}
