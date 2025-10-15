<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    protected $table = 'clientes';

    protected $fillable = [
        'tipo_documento',
        'numero_documento',
        'nombre_razon_social',
        'nombre_comercial',
        'direccion',
        'distrito',
        'provincia',
        'departamento',
        'ubigeo',
        'telefono',
        'email',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    // Relaciones
    public function comprobantes()
    {
        return $this->hasMany(Comprobante::class);
    }

    // Accesor: ¿Es empresa (RUC)?
    public function getEsEmpresaAttribute()
    {
        return $this->tipo_documento === '6';
    }

    // Accesor: ¿Es persona natural (DNI)?
    public function getEsPersonaNaturalAttribute()
    {
        return $this->tipo_documento === '1';
    }
}
