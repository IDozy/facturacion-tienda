<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cliente extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tipo_documento',
        'numero_documento',
        'nombre',
        'direccion',
        'email',
        'telefono',
        'empresa_id',
    ];

    //Relaciones
    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }
}

