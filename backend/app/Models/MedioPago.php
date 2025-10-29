<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MedioPago extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'codigo_sunat',
        'nombre',
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function pagos()
    {
        return $this->hasMany(Pago::class);
    }
}
