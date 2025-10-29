<?php

namespace App\Models\Contabilidad;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PlanCuenta extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'codigo',
        'nombre',
        'tipo',
        'nivel',
        'padre_id',
    ];

    public function padre()
    {
        return $this->belongsTo(PlanCuenta::class, 'padre_id');
    }

    public function hijos()
    {
        return $this->hasMany(PlanCuenta::class, 'padre_id');
    }

    public function esCuentaRaiz()
    {
        return is_null($this->padre_id);
    }

    public function esCuentaHija()
    {
        return !is_null($this->padre_id);
    }

    public function obtenerRutaCompleta(): string
    {

        $ruta = $this->nombre;
        $actual = $this->padre;

        while ($actual) {
            $ruta = $actual->nombre . ' > ' . $ruta;
            $actual = $actual->padre;
        }
        return $ruta;
    }
}
