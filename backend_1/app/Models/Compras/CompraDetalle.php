<?php 

namespace App\Models\Compras;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\Compras\Compra;
use App\Models\Inventario\Producto;
use App\Models\Inventario\Almacen;
use App\Models\Compras\RecepcionDetalle;

class CompraDetalle extends Model {
    use SoftDeletes;
    protected $fillable = ['compra_id', 'producto_id', 'almacen_id', 'item', 'cantidad', 'cantidad_recibida', 'precio_unitario', 'subtotal', 'descuento', 'tipo_igv', 'porcentaje_igv', 'igv', 'total', 'unidad_medida', 'descripcion'];
    protected $casts = ['cantidad' => 'decimal:4', 'cantidad_recibida' => 'decimal:4'];
    
    public function compra() { return $this->belongsTo(Compra::class); }
    public function producto() { return $this->belongsTo(Producto::class); }
    public function almacen() { return $this->belongsTo(Almacen::class); }
    public function recepcionDetalles() { return $this->hasMany(RecepcionDetalle::class); }
}