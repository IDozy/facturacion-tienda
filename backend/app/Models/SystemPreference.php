<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'empresa_id',
        'idioma',
        'zona_horaria',
        'formato_fecha',
        'decimales',
        'alertas',
        'preferencias',
        'updated_by',
    ];

    protected $casts = [
        'alertas' => 'array',
        'preferencias' => 'array',
    ];
}
