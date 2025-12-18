<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    use HasFactory;

    protected $fillable = [
        'empresa_id',
        'nombre',
        'principal',
        'stock_negativo',
        'maneja_series',
        'maneja_lotes',
        'codigo_barras',
        'activo',
        'updated_by',
    ];

    protected $casts = [
        'principal' => 'boolean',
        'stock_negativo' => 'boolean',
        'maneja_series' => 'boolean',
        'maneja_lotes' => 'boolean',
        'activo' => 'boolean',
    ];
}
