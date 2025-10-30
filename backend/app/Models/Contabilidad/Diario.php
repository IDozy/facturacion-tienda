<?php

namespace App\Models\Contabilidad;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Empresa;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Diario extends Model
{
    use HasFactory;

    protected $table = 'diarios';

    protected $fillable = [
        'empresa_id',
        'codigo',               // DV, DC, DB
        'nombre',               // Diario de Ventas
        'tipo',                 // manual, automatico
        'prefijo',              // DV-
        'correlativo_actual',   // 100
        'descripcion',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'correlativo_actual' => 'integer',
            'activo' => 'boolean',
            'tipo' => 'string',
        ];
    }

    // === MULTI-TENANCY + DEFAULTS ===
    protected static function booted()
    {
        static::creating(function ($diario) {
            $diario->empresa_id ??=Auth::user()?->empresa_id;
            $diario->correlativo_actual ??= 0;
            $diario->activo ??= true;
            $diario->prefijo ??= strtoupper(substr($diario->codigo, 0, 3)) . '-';
        });
    }

    // === RELACIONES ===
    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function asientos()
    {
        return $this->hasMany(Asiento::class);
    }

    // === SCOPES ===
    public function scopeManuales($q) { return $q->where('tipo', 'manual'); }
    public function scopeAutomaticos($q) { return $q->where('tipo', 'automatico'); }
    public function scopeActivos($q) { return $q->where('activo', true); }
    public function scopePorCodigo($q, $codigo) { return $q->where('codigo', $codigo); }

    // === NÚMERO CORRELATIVO SEGURO ===
    public function generarSiguienteNumero(): int
    {
        return DB::transaction(function () {
            $diario = DB::table('diarios')
                ->where('id', $this->id)
                ->lockForUpdate()
                ->first();

            $nuevo = $diario->correlativo_actual + 1;

            DB::table('diarios')
                ->where('id', $this->id)
                ->update(['correlativo_actual' => $nuevo]);

            return $nuevo;
        });
    }

    public function numeroFormateado(?int $numero = null): string
    {
        $numero = $numero ?? ($this->correlativo_actual + 1);
        return $this->prefijo . str_pad($numero, 6, '0', STR_PAD_LEFT);
    }

    // === ESTADO ===
    public function esManual(): bool { return $this->tipo === 'manual'; }
    public function esAutomatico(): bool { return $this->tipo === 'automatico'; }
    public function estaActivo(): bool { return $this->activo; }

    public function activar(): self
    {
        $this->update(['activo' => true]);
        return $this;
    }

    public function desactivar(): self
    {
        $this->update(['activo' => false]);
        return $this;
    }

    // === ESTADÍSTICAS ===
    public function cantidadAsientos(): int
    {
        return $this->asientos()->count();
    }

    public function ultimoAsiento()
    {
        return $this->asientos()->latest('fecha')->first();
    }

    public function tieneAsientos(): bool
    {
        return $this->asientos()->exists();
    }

    // === VALIDACIÓN ===
    public function puedeEliminar(): bool
    {
        return !$this->tieneAsientos();
    }
}