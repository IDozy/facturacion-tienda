<?php

namespace App\Models;

use App\Models\Compras\Compra;
use App\Models\Compras\Proveedor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Empresa extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'razon_social',
        'ruc',
        'direccion',
        'telefono',
        'email',
        'certificado_digital',
        'clave_certificado',
        'usuario_sol',
        'clave_sol',
        'modo',
    ];

    //Relaciones
    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function clientes()
    {
        return $this->hasMany(Cliente::class);
    }

    public function proveedores()
    {
        return $this->hasMany(Proveedor::class);
    }

    public function comprobantes()
    {
        return $this->hasMany(Comprobante::class);
    }

    public function compras()
    {
        return $this->hasMany(Compra::class);
    }

    public function configuracion()
    {
        return $this->hasOne(Configuracion::class);
    }
}
