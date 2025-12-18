<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanySetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'empresa_id',
        'razon_social',
        'nombre_comercial',
        'ruc',
        'direccion_fiscal',
        'direccion_comercial',
        'telefono',
        'email',
        'logo_url',
        'region',
        'ciudad',
        'pais',
        'updated_by',
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
