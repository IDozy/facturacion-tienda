<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Categoria extends Model {
    use SoftDeletes;
    protected $fillable = ['nombre', 'codigo', 'descripcion', 'imagen', 'activo'];
    protected $casts = ['activo' => 'boolean'];
    
    public function productos() { return $this->hasMany(Producto::class); }
}

