<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Integration extends Model
{
    use HasFactory;

    protected $fillable = [
        'empresa_id',
        'tipo',
        'params',
        'activo',
        'updated_by',
    ];

    protected $casts = [
        'params' => 'array',
        'activo' => 'boolean',
    ];
}
