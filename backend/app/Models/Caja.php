<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Caja extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'usuario_id',
        'monto_inicial',
        'monto_final',
        'total_esperado',
        'apertura',
        'cierre',
        'estado',
    ];

    protected $casts = [
        'apertura' => 'datetime',
        'cierre' => 'datetime',
        'monto_inicial' => 'decimal:2',
        'monto_final' => 'decimal:2',
        'total_esperado' => 'decimal:2',
        'estado' => 'boolean',
    ];

    // Relaciones

    public function usuario()
    {
        return $this->belongsTo(User::class);
    }

    public function pagos()
    {
        return $this->hasMany(Pago::class);
    }

    public function estaAbierta(): bool
    {
        return $this->estado === true;
    }

    public function estaCerrada(): bool
    {
        return $this->estado === false;
    }

    public function calcularCuadre(): float
    {
        return (float) $this->monto_final - (float) $this->total_esperado;
    }

}
