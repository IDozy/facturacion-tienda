<?php

namespace App\Models\Contabilidad;

use App\Models\Empresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Diario extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'empresa_id',
        'nombre',
        'tipo',
        'descripcion',
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function asientos()
    {
        return $this->hasMany(Asiento::class);
    }

    public function tipoFormateado(): string
    {
        return ucfirst($this->tipo);
    }
}
