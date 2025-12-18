<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentSeries extends Model
{
    use HasFactory;

    protected $fillable = [
        'empresa_id',
        'tipo',
        'serie',
        'correlativo_inicial',
        'correlativo_actual',
        'automatico',
        'activo',
        'updated_by',
    ];

    protected $casts = [
        'automatico' => 'boolean',
        'activo' => 'boolean',
    ];
}
