<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountingSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'empresa_id',
        'plan_contable',
        'cuenta_ventas',
        'cuenta_compras',
        'cuenta_igv',
        'cuenta_caja',
        'cuenta_bancos',
        'contabilizacion_automatica',
        'centros_costo_obligatorios',
        'periodos',
        'updated_by',
    ];

    protected $casts = [
        'contabilizacion_automatica' => 'boolean',
        'centros_costo_obligatorios' => 'boolean',
        'periodos' => 'array',
    ];
}
