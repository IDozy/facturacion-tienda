<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class RecepcionDetalle extends Model {
    protected $fillable = ['recepcion_id', 'compra_detalle_id', 'producto_id', 'cantidad_recibida', 'observaciones'];
    protected $casts = ['cantidad_recibida' => 'decimal:4'];
    
    public function recepcion() { return $this->belongsTo(Recepcion::class); }
    public function compraDetalle() { return $this->belongsTo(CompraDetalle::class); }
    public function producto() { return $this->belongsTo(Producto::class); }
}