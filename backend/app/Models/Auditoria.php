<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Auditoria extends Model
{
    use HasFactory;

    protected $fillable = [
        'usuario_id',
        'tabla',
        'accion',
        'registro_id',
        'accion',
        'valores_anteriores',
        'valores_nuevos',
        'ip',
    ];

    protected $casts = [
        'valores_anteriores' => 'array',
        'valores_nuevos' => 'array',
    ];

    // Relaciones

    public function usuario()
    {
        return $this->belongsTo(User::class);
    }
}
