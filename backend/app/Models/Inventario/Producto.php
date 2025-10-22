<?php

namespace App\Models\Inventario;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Producto extends Model {
    use SoftDeletes;
    protected $fillable = ['categoria_id', 'almacen_principal_id', 'codigo', 'codigo_barras', 'descripcion', 'descripcion_larga', 'unidad_medida', 'precio_costo', 'precio_unitario', 'precio_venta', 'tipo_igv', 'porcentaje_igv', 'stock', 'stock_minimo', 'ubicacion', 'imagen', 'stock_por_almacen', 'activo'];
    protected $casts = ['stock_por_almacen' => 'json', 'activo' => 'boolean'];
    
    public function categoria() { return $this->belongsTo(Categoria::class); }
    public function almacen() { return $this->belongsTo(Almacen::class, 'almacen_principal_id'); }
    public function comprobanteDetalles() { return $this->hasMany(ComprobanteDetalle::class); }
    public function compraDetalles() { return $this->hasMany(CompraDetalle::class); }
    public function movimientos() { return $this->hasMany(MovimientoStock::class); }
}