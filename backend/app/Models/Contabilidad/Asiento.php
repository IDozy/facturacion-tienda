<?php

namespace App\Models\Contabilidad;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Facturacion\Comprobante;
use App\Models\Empresa;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class Asiento extends Model
{
    use HasFactory;

    protected $table = 'asientos';

    protected $fillable = [
        'diario_id',
        'numero',
        'fecha',
        'glosa',
        'total_debe',
        'total_haber',
        'estado',
        'periodo_contable_id',
        'comprobante_id',
        'registrado_por',
        'registrado_en',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date:Y-m-d',
            'total_debe' => 'decimal:2',
            'total_haber' => 'decimal:2',
            'registrado_en' => 'datetime',
            'estado' => 'string',
        ];
    }

    // === MULTI-TENANCY + PERÍODO AUTOMÁTICO ===
    protected static function booted()
    {
        static::creating(function ($asiento) {
            $empresaId = Auth::user()?->empresa_id;

            // Período contable
            if (!$asiento->periodo_contable_id && $asiento->fecha) {
                $periodo = PeriodoContable::where('empresa_id', $empresaId)
                    ->where('fecha_inicio', '<=', $asiento->fecha)
                    ->where('fecha_fin', '>=', $asiento->fecha)
                    ->first();

                if ($periodo) {
                    $asiento->periodo_contable_id = $periodo->id;
                }
            }

            // Número correlativo
            if ($asiento->diario_id && !$asiento->numero) {
                $diario = \App\Models\Contabilidad\Diario::find($asiento->diario_id);
                $asiento->numero = $diario->numeroFormateado();
            }
        });
    }

    // === RELACIONES ===
    public function diario()
    {
        return $this->belongsTo(Diario::class);
    }

    public function periodoContable()
    {
        return $this->belongsTo(PeriodoContable::class);
    }

    public function comprobante()
    {
        return $this->belongsTo(Comprobante::class);
    }

    public function detalles()
    {
        return $this->hasMany(AsientoDetalle::class);
    }

    public function registradoPor()
    {
        return $this->belongsTo(\App\Models\User::class, 'registrado_por');
    }

    // === SCOPES ===
    public function scopeBorradores($q) { return $q->where('estado', 'borrador'); }
    public function scopeRegistrados($q) { return $q->where('estado', 'registrado'); }
    public function scopeAnulados($q) { return $q->where('estado', 'anulado'); }
    public function scopeDelPeriodo($q, $id) { return $q->where('periodo_contable_id', $id); }
    public function scopeDelDiario($q, $id) { return $q->where('diario_id', $id); }

    // === TOTALES CON CACHE ===
    public function calcularTotales(): self
    {
        $totales = Cache::remember("asiento_{$this->id}_totales", 3600, function () {
            return [
                'debe' => $this->detalles->sum('debe'),
                'haber' => $this->detalles->sum('haber'),
            ];
        });

        $this->update([
            'total_debe' => $totales['debe'],
            'total_haber' => $totales['haber'],
        ]);

        return $this;
    }

    // === VALIDACIÓN ===
    public function estaCuadrado(): bool
    {
        return abs($this->total_debe - $this->total_haber) < 0.01;
    }

    // === REGISTRAR ===
    public function registrar(): self
    {
        if ($this->estado === 'registrado') return $this;

        if (!$this->estaCuadrado()) {
            throw new \Exception("El asiento no está cuadrado. Debe: {$this->total_debe}, Haber: {$this->total_haber}");
        }

        if ($this->periodoContable?->estaCerrado()) {
            throw new \Exception('No se puede registrar en período cerrado');
        }

        $this->update([
            'estado' => 'registrado',
            'registrado_por' => Auth::id(),
            'registrado_en' => now(),
        ]);

        return $this;
    }

    // === ANULAR ===
    public function anular(): self
    {
        if ($this->estado === 'anulado') return $this;

        if ($this->periodoContable?->estaCerrado()) {
            throw new \Exception('No se puede anular en período cerrado');
        }

        $this->update(['estado' => 'anulado']);
        return $this;
    }

    // === DUPLICAR ===
    public function duplicar(): self
    {
        $nuevo = $this->replicate();
        $nuevo->estado = 'borrador';
        $nuevo->fecha = now();
        $nuevo->numero = null;
        $nuevo->save();

        foreach ($this->detalles as $detalle) {
            $nuevoDetalle = $detalle->replicate();
            $nuevoDetalle->asiento_id = $nuevo->id;
            $nuevoDetalle->save();
        }

        return $nuevo;
    }

    // === GENERAR DESDE COMPROBANTE ===
    public static function generarDesdeComprobante(Comprobante $comprobante): self
    {
        $diario = Diario::where('empresa_id', $comprobante->empresa_id)
            ->where('tipo', 'automatico')
            ->firstOrFail();

        $asiento = static::create([
            'diario_id' => $diario->id,
            'fecha' => $comprobante->fecha_emision,
            'glosa' => "Venta {$comprobante->getNumeroCompletoAttribute()}",
            'comprobante_id' => $comprobante->id,
            'estado' => 'borrador',
        ]);

        // === CUENTAS (AJUSTAR SEGÚN TU PLAN DE CUENTAS) ===
        $cuentas = [
            'cxc' => PlanCuenta::where('codigo', '12')->first(),     // Cuentas por Cobrar
            'ventas' => PlanCuenta::where('codigo', '70')->first(),  // Ventas
            'igv' => PlanCuenta::where('codigo', '40')->first(),     // IGV por Pagar
        ];

        // Cargo a CxC
        AsientoDetalle::create([
            'asiento_id' => $asiento->id,
            'cuenta_id' => $cuentas['cxc']->id,
            'descripcion' => 'CxC - ' . $comprobante->razon_social_cliente,
            'debe' => $comprobante->total,
            'haber' => 0,
        ]);

        // Abono a Ventas
        if ($comprobante->subtotal_gravado > 0) {
            AsientoDetalle::create([
                'asiento_id' => $asiento->id,
                'cuenta_id' => $cuentas['ventas']->id,
                'descripcion' => 'Ventas gravadas',
                'debe' => 0,
                'haber' => $comprobante->subtotal_gravado,
            ]);
        }

        // Abono a IGV
        if ($comprobante->igv_total > 0) {
            AsientoDetalle::create([
                'asiento_id' => $asiento->id,
                'cuenta_id' => $cuentas['igv']->id,
                'descripcion' => 'IGV por pagar',
                'debe' => 0,
                'haber' => $comprobante->igv_total,
            ]);
        }

        $asiento->calcularTotales();
        return $asiento;
    }

    // === ACCESSORS ===
    public function getEstadoBadgeAttribute(): string
    {
        return match ($this->estado) {
            'borrador' => 'warning',
            'registrado' => 'success',
            'anulado' => 'danger',
            default => 'secondary',
        };
    }

    public function esBorrador(): bool { return $this->estado === 'borrador'; }
    public function esRegistrado(): bool { return $this->estado === 'registrado'; }
    public function esAnulado(): bool { return $this->estado === 'anulado'; }
}