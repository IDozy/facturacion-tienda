<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    protected $table = 'productos';

    protected $fillable = [
        'codigo',
        'codigo_barras',
        'descripcion',
        'descripcion_larga',
        'unidad_medida',
        'precio_unitario',
        'precio_venta',
        'tipo_igv',
        'porcentaje_igv',
        'stock',
        'stock_minimo',
        'ubicacion',
        'categoria',
        'imagen',
        'activo',
    ];

    protected $casts = [
        'precio_unitario' => 'decimal:2',
        'precio_venta' => 'decimal:2',
        'porcentaje_igv' => 'decimal:2',
        'stock' => 'decimal:2',
        'stock_minimo' => 'decimal:2',
        'activo' => 'boolean',
    ];

    // Relaciones
    public function detalles()
    {
        return $this->hasMany(ComprobanteDetalle::class);
    }

    // Accesor: ¿Tiene stock?
    public function getTieneStockAttribute()
    {
        return $this->stock > 0;
    }

    // Accesor: ¿Stock bajo?
    public function getStockBajoAttribute()
    {
        return $this->stock <= $this->stock_minimo;
    }
}
