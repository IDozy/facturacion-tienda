<?php

namespace App\Models\Inventario;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Almacen extends Model {
    use SoftDeletes;
    protected $fillable = ['nombre', 'codigo', 'descripcion', 'ubicacion', 'es_principal', 'activo'];
    protected $casts = ['es_principal' => 'boolean', 'activo' => 'boolean'];
    
    public function productos() { return $this->hasMany(Producto::class, 'almacen_principal_id'); }
    public function movimientos() { return $this->hasMany(MovimientoStock::class); }
    public function recepciones() { return $this->hasMany(Recepcion::class); }
}