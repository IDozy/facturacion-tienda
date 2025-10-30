<?php

namespace App\Models\Facturacion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Empresa;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GuiaRemision extends Model
{
    use HasFactory;

    protected $table = 'guias_remision';

    protected $fillable = [
        'empresa_id',
        'comprobante_id',
        'serie',           // T001, T002...
        'numero',          // 1, 2, 3...
        'fecha_emision',
        'motivo_traslado',
        'peso_total',
        'punto_partida',
        'punto_llegada',
        'transportista_ruc',
        'transportista_razon_social',
        'placa_vehiculo',
        'conductor_dni',
        'conductor_nombre',
        'estado',
        'motivo_anulacion',
    ];

    protected function casts(): array
    {
        return [
            'fecha_emision' => 'date:Y-m-d',
            'peso_total' => 'decimal:2',
            'estado' => 'string',
        ];
    }

    // === MULTI-TENANCY + AUTOCOMPLETADO ===
    protected static function booted()
    {
        static::creating(function ($guia) {
            $guia->empresa_id ??= Auth::user()?->empresa_id;
            $guia->estado ??= 'emitida';
            $guia->fecha_emision ??= now()->format('Y-m-d');

            // Generar número correlativo seguro
            if (!$guia->numero) {
                $guia->numero = $guia->generarSiguienteNumero();
            }
        });
    }

    // === RELACIONES ===
    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function comprobante()
    {
        return $this->belongsTo(Comprobante::class);
    }

    // === SCOPES ===
    public function scopeEmitidas($q) { return $q->where('estado', 'emitida'); }
    public function scopeAnuladas($q) { return $q->where('estado', 'anulada'); }
    public function scopePorMotivo($q, $motivo) { return $q->where('motivo_traslado', $motivo); }
    public function scopeDelMes($q, $año, $mes) {
        return $q->whereYear('fecha_emision', $año)->whereMonth('fecha_emision', $mes);
    }

    // === NÚMERO CORRELATIVO SEGURO (SIN SerieGuia) ===
    public function generarSiguienteNumero(): int
    {
        return DB::transaction(function () {
            $ultima = DB::table('guias_remision')
                ->where('empresa_id', $this->empresa_id)
                ->where('serie', $this->serie)
                ->lockForUpdate()
                ->max('numero');

            return ($ultima ?? 0) + 1;
        });
    }

    // === ACCESSORS ===
    public function getNumeroCompletoAttribute(): string
    {
        return $this->serie . '-' . str_pad($this->numero, 8, '0', STR_PAD_LEFT);
    }

    public function getMotivoDescripcionAttribute(): string
    {
        return match ($this->motivo_traslado) {
            'venta' => 'Venta',
            'traslado_interno' => 'Traslado entre establecimientos',
            'devolucion' => 'Devolución',
            'importacion' => 'Importación',
            'exportacion' => 'Exportación',
            'otros' => 'Otros',
            default => $this->motivo_traslado,
        };
    }

    public function getCodigoMotivoAttribute(): string
    {
        return match ($this->motivo_traslado) {
            'venta' => '01',
            'traslado_interno' => '04',
            'devolucion' => '02',
            'importacion' => '08',
            'exportacion' => '09',
            'otros' => '13',
            default => '99',
        };
    }

    // === VALIDACIONES ===
    public function validarTransportista(): bool
    {
        if (!$this->transportista_ruc) return true;
        return preg_match('/^\d{11}$/', $this->transportista_ruc) === 1;
    }

    public function validarSerie(): bool
    {
        return preg_match('/^T\d{3}$/', $this->serie) === 1;
    }

    // === ACCIONES ===
    public function anular(?string $motivo = null): self
    {
        $this->update([
            'estado' => 'anulada',
            'motivo_anulacion' => $motivo,
        ]);
        return $this;
    }

    public function esEmitida(): bool { return $this->estado === 'emitida'; }
    public function esAnulada(): bool { return $this->estado === 'anulada'; }
}