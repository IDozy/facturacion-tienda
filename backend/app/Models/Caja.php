<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class Caja extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'usuario_id',
        'monto_inicial',
        'monto_final',
        'total_esperado',
        'diferencia_cuadratura',
        'moneda',
        'estado',
        'apertura',
        'cierre',
    ];

    protected $casts = [
        'monto_inicial' => 'decimal:2',
        'monto_final' => 'decimal:2',
        'total_esperado' => 'decimal:2',
        'diferencia_cuadratura' => 'decimal:2',
        'apertura' => 'datetime',
        'cierre' => 'datetime',
    ];

    // === RELACIONES ===
    public function usuario()
    {
        return $this->belongsTo(User::class);
    }

    public function pagos()
    {
        return $this->hasMany(Pago::class);
    }

    // === SCOPES ===
    public function scopeAbiertas($query)
    {
        return $query->where('estado', 'abierta');
    }

    public function scopeCerradas($query)
    {
        return $query->where('estado', 'cerrada');
    }

    public function scopeDelDia($query, $fecha = null)
    {
        $fecha = $fecha ?: Carbon::today();
        return $query->whereDate('apertura', $fecha);
    }

    public function scopeDelUsuario($query, $usuarioId)
    {
        return $query->where('usuario_id', $usuarioId);
    }

    // === MÉTODOS DE NEGOCIO ===
    public function estaAbierta()
    {
        return $this->estado === 'abierta';
    }

    public function estaCerrada()
    {
        return $this->estado === 'cerrada';
    }

    public function cerrar($montoFinal)
    {
        if (!$this->estaAbierta()) {
            throw new \Exception('La caja ya está cerrada');
        }

        $totalEsperado = $this->calcularTotalEsperado();

        $this->update([
            'monto_final' => $montoFinal,
            'total_esperado' => $totalEsperado,
            'diferencia_cuadratura' => $montoFinal - $totalEsperado,
            'estado' => 'cerrada',
            'cierre' => now(),
        ]);

        return $this;
    }

    public function calcularTotalEsperado()
    {
        $totalPagos = $this->pagos()
            ->where('estado', 'confirmado')
            ->sum('monto');

        return $this->monto_inicial + $totalPagos;
    }

    public function validarCuadratura($tolerancia = null)
    {
        if ($tolerancia === null) {
            $tolerancia = Auth::user()->empresa->configuracion->tolerancia_cuadratura ?? 0;
        }

        return abs($this->diferencia_cuadratura) <= $tolerancia;
    }

    // === ACCESSORS ===
    public function getEsCuadradaAttribute()
    {
        return $this->validarCuadratura();
    }

    public function getDuracionAttribute()
    {
        if ($this->apertura && $this->cierre) {
            return $this->apertura->diffForHumans($this->cierre, true);
        }
        return null;
    }
}
