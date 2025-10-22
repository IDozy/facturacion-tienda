<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cliente extends Model {
    use SoftDeletes;
    protected $fillable = ['tipo_documento', 'numero_documento', 'nombre_razon_social', 'nombre_comercial', 'direccion', 'distrito', 'provincia', 'departamento', 'ubigeo', 'telefono', 'email', 'observaciones', 'activo'];
    protected $casts = ['activo' => 'boolean'];
    
    public function comprobantes() { return $this->hasMany(Comprobante::class); }
    
    public function getEsEmpresaAttribute() { return $this->tipo_documento === '6'; }
    public function getEsPersonaNaturalAttribute() { return $this->tipo_documento === '1'; }
}