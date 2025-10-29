<?php

namespace App\Models\Compras;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\Compras\Compra;

class Proveedor extends Model {
    use SoftDeletes;
    protected $fillable = ['tipo_documento', 'numero_documento', 'nombre_razon_social', 'nombre_comercial', 'direccion', 'distrito', 'provincia', 'departamento', 'ubigeo', 'telefono', 'email', 'contacto', 'observaciones', 'saldo_deuda', 'activo'];
    protected $casts = ['saldo_deuda' => 'decimal:2', 'activo' => 'boolean'];
    
    public function compras() { return $this->hasMany(Compra::class); }
}