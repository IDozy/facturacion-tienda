<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cashbox extends Model
{
    use HasFactory;

    protected $fillable = [
        'empresa_id',
        'nombre',
        'moneda',
        'por_defecto',
        'maneja_cheques',
        'liquidacion_diaria',
        'flujo_automatico',
        'updated_by',
    ];

    protected $casts = [
        'por_defecto' => 'boolean',
        'maneja_cheques' => 'boolean',
        'liquidacion_diaria' => 'boolean',
        'flujo_automatico' => 'boolean',
    ];
}
