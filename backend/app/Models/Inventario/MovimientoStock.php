<?php

namespace App\Models\Inventario;

use Illuminate\Database\Eloquent\Model;

use App\Models\Inventario\Almacen;
use App\Models\Inventario\Producto;
use App\Models\User;

class MovimientoStock extends Model {
    protected $fillable = ['almacen_id', 'producto_id', 'usuario_id', 'tipo', 'cantidad', 'descripcion', 'referencia'];
    
    public function almacen() { return $this->belongsTo(Almacen::class); }
    public function producto() { return $this->belongsTo(Producto::class); }
    public function usuario() { return $this->belongsTo(User::class); }
}
