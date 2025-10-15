<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Empresa extends Model
{
    protected $table = 'empresas';

    protected $fillable = [
        'ruc',
        'razon_social',
        'nombre_comercial',
        'direccion',
        'urbanizacion',
        'distrito',
        'provincia',
        'departamento',
        'ubigeo',
        'codigo_pais',
        'telefono',
        'email',
        'web',
        'usuario_sol',
        'clave_sol',
        'certificado_digital',
        'clave_certificado',
        'modo_prueba',
        'logo',
        'activo',
    ];

    protected $casts = [
        'modo_prueba' => 'boolean',
        'activo' => 'boolean',
    ];

    // Relaciones
    public function series()
    {
        return $this->hasMany(Serie::class);
    }

    public function comprobantes()
    {
        return $this->hasMany(Comprobante::class);
    }
}
