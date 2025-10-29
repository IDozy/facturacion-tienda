<?php

namespace App\Models\Contabilidad;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asiento extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'diario_id',
        'fecha',
        'glosa',
        'total_debe',
        'total_haber',
        'estado',
    ];

    protected $casts = [
        'fecha' => 'date',
        'total_debe' => 'decimal:2',
        'total_haber' => 'decimal:2',
    ];

    public function diario()
    {
        return $this->belongsTo(Diario::class);
    }

    public function detalles()
    {
        return $this->hasMany(AsientoDetalle::class);
    }

    public function estaCuadrado(): bool
    {
        return bccomp($this->total_debe, $this->total_haber, 2) === 0;
    }

    public function estadoFormateado(): string
    {
        return ucfirst($this->estado);
    }
}
