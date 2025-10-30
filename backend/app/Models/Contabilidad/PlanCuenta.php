<?php

namespace App\Models\Contabilidad;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Empresa;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class PlanCuenta extends Model
{
    use HasFactory;

    protected $table = 'plan_cuentas';

    protected $fillable = [
        'codigo',
        'nombre',
        'tipo',
        'padre_id',
        'nivel',
        'empresa_id',
        'es_auxiliar',
    ];

    protected function casts(): array
    {
        return [
            'nivel' => 'integer',
            'es_auxiliar' => 'boolean',
            'tipo' => 'string',
        ];
    }

    // === MULTI-TENANCY + NIVEL AUTOMÁTICO ===
    protected static function booted()
    {
        static::creating(function ($cuenta) {
            $cuenta->empresa_id ??= Auth::user()?->empresa_id;

            if ($cuenta->padre_id) {
                $padre = static::find($cuenta->padre_id);
                $cuenta->nivel = $padre->nivel + 1;
                $cuenta->es_auxiliar = $cuenta->nivel >= 4;
            } else {
                $cuenta->nivel = 1;
                $cuenta->es_auxiliar = false;
            }
        });
    }

    // === RELACIONES ===
    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function padre()
    {
        return $this->belongsTo(self::class, 'padre_id');
    }

    public function hijos()
    {
        return $this->hasMany(self::class, 'padre_id');
    }

    public function asientosDetalle()
    {
        return $this->hasMany(AsientoDetalle::class, 'cuenta_id');
    }

    // === SCOPES ===
    public function scopeRaices($q) { return $q->whereNull('padre_id'); }
    public function scopePorTipo($q, $tipo) { return $q->where('tipo', $tipo); }
    public function scopeActivos($q) { return $q->where('tipo', 'activo'); }
    public function scopePasivos($q) { return $q->where('tipo', 'pasivo'); }
    public function scopeIngresos($q) { return $q->where('tipo', 'ingreso'); }
    public function scopeGastos($q) { return $q->where('tipo', 'gasto'); }
    public function scopePatrimonio($q) { return $q->where('tipo', 'patrimonio'); }
    public function scopeAuxiliares($q) { return $q->where('es_auxiliar', true); }

    // === ESTADO ===
    public function esHoja(): bool { return !$this->hijos()->exists(); }
    public function tieneMovimientos(): bool { return $this->asientosDetalle()->exists(); }
    public function puedeEliminar(): bool { return $this->esHoja() && !$this->tieneMovimientos(); }
    public function esAuxiliar(): bool { return $this->es_auxiliar; }

    // === RUTA JERÁRQUICA ===
    public function obtenerRuta(): array
    {
        $ruta = [];
        $cuenta = $this;
        while ($cuenta) {
            array_unshift($ruta, $cuenta);
            $cuenta = $cuenta->padre;
        }
        return $ruta;
    }

    public function getNombreCompletoAttribute(): string
    {
        return collect($this->obtenerRuta())
            ->pluck('nombre')
            ->implode(' > ');
    }

    public function getCodigoCompletoAttribute(): string
    {
        return collect($this->obtenerRuta())
            ->pluck('codigo')
            ->implode('.');
    }

    // === SALDO CON CACHE ===
    public function saldo(int $periodoId = null): float
    {
        $cacheKey = "cuenta_{$this->id}_saldo" . ($periodoId ? "_p{$periodoId}" : '');
        return Cache::remember($cacheKey, 3600, function () use ($periodoId) {
            $query = $this->asientosDetalle()
                ->whereHas('asiento', fn($q) => $q->where('estado', 'registrado')
                    ->when($periodoId, fn($q) => $q->where('periodo_contable_id', $periodoId))
                );

            $debe = $query->sum('debe');
            $haber = $query->sum('haber');

            return match ($this->tipo) {
                'activo', 'gasto' => $debe - $haber,
                'pasivo', 'patrimonio', 'ingreso' => $haber - $debe,
                default => 0.0,
            };
        });
    }

    // === VALIDACIÓN DE CICLOS ===
    public function validarPadre(?int $padreId): bool
    {
        if (!$padreId || $padreId == $this->id) return false;

        $padre = static::find($padreId);
        while ($padre) {
            if ($padre->id == $this->id) return false;
            $padre = $padre->padre;
        }
        return true;
    }

    // === TIPOS ===
    public function esActivo(): bool { return $this->tipo === 'activo'; }
    public function esPasivo(): bool { return $this->tipo === 'pasivo'; }
    public function esPatrimonio(): bool { return $this->tipo === 'patrimonio'; }
    public function esIngreso(): bool { return $this->tipo === 'ingreso'; }
    public function esGasto(): bool { return $this->tipo === 'gasto'; }
}