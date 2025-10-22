<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Proveedor extends Model {
    use SoftDeletes;
    protected $fillable = ['tipo_documento', 'numero_documento', 'nombre_razon_social', 'nombre_comercial', 'direccion', 'distrito', 'provincia', 'departamento', 'ubigeo', 'telefono', 'email', 'contacto', 'observaciones', 'saldo_deuda', 'activo'];
    protected $casts = ['saldo_deuda' => 'decimal:2', 'activo' => 'boolean'];
    
    public function compras() { return $this->hasMany(Compra::class); }
}