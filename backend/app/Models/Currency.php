<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    use HasFactory;

    protected $fillable = [
        'empresa_id',
        'code',
        'name',
        'is_base',
        'precios_incluyen_igv',
        'igv_rate',
        'redondeo',
        'tipo_cambio_automatico',
        'updated_by',
    ];

    protected $casts = [
        'is_base' => 'boolean',
        'precios_incluyen_igv' => 'boolean',
        'redondeo' => 'boolean',
        'tipo_cambio_automatico' => 'boolean',
        'igv_rate' => 'float',
    ];

    public function exchangeRates()
    {
        return $this->hasMany(ExchangeRate::class);
    }
}
