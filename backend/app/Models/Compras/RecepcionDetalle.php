<?php

namespace App\Models\Compras;
use Illuminate\Database\Eloquent\Model;

use App\Models\Compras\Recepcion;
use App\Models\Compras\RecepcionDetalle;
use App\Models\Inventario\Producto;

class RecepcionDetalle extends Model {
    protected $fillable = ['recepcion_id', 'compra_detalle_id', 'producto_id', 'cantidad_recibida', 'observaciones'];
    protected $casts = ['cantidad_recibida' => 'decimal:4'];
    
    public function recepcion() { return $this->belongsTo(Recepcion::class); }
    public function compraDetalle() { return $this->belongsTo(CompraDetalle::class); }
    public function producto() { return $this->belongsTo(Producto::class); }
}