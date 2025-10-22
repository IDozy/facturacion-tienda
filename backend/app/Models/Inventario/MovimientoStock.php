<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MovimientoStock extends Model {
    protected $fillable = ['almacen_id', 'producto_id', 'usuario_id', 'tipo', 'cantidad', 'descripcion', 'referencia'];
    
    public function almacen() { return $this->belongsTo(Almacen::class); }
    public function producto() { return $this->belongsTo(Producto::class); }
    public function usuario() { return $this->belongsTo(User::class); }
}
