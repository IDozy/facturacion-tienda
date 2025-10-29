<?php

namespace App\Models\Inventario;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\Inventario\Producto;

class Categoria extends Model {
    use SoftDeletes;
    protected $fillable = ['nombre', 'codigo', 'descripcion', 'imagen', 'activo'];
    protected $casts = ['activo' => 'boolean'];
    
    public function productos() { return $this->hasMany(Producto::class); }
}

