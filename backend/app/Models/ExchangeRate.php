<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'currency_id',
        'fecha',
        'compra',
        'venta',
        'fuente',
        'automatico',
    ];

    protected $casts = [
        'fecha' => 'date',
        'automatico' => 'boolean',
        'compra' => 'float',
        'venta' => 'float',
    ];
}
