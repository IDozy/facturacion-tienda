<?php

namespace App\Models\Facturacion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Inventario\Producto;
use Illuminate\Support\Facades\Cache;

class ComprobanteDetalle extends Model
{
    use HasFactory;

    protected $table = 'comprobante_detalles';

    protected $fillable = [
        'comprobante_id',
        'producto_id',
        'cantidad',
        'precio_unitario',
        'tipo_afectacion',
        'subtotal',
        'igv',
        'total',
        'descuento_monto',
    ];

    protected function casts(): array
    {
        return [
            'cantidad' => 'decimal:3',
            'precio_unitario' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'igv' => 'decimal:2',
            'total' => 'decimal:2',
            'descuento_monto' => 'decimal:2',
            'tipo_afectacion' => 'string',
        ];
    }

    // === CÁLCULO AUTOMÁTICO ===
    protected static function booted()
    {
        static::creating(fn($d) => $d->calcularMontos());
        static::updating(function ($d) {
            if ($d->isDirty(['cantidad', 'precio_unitario', 'descuento_monto', 'tipo_afectacion'])) {
                $d->calcularMontos();
            }
        });
    }

    // === RELACIONES ===
    public function comprobante()
    {
        return $this->belongsTo(Comprobante::class);
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    // === CÁLCULO DE MONTOS CON CACHE ===
    public function calcularMontos(): void
    {
        $subtotalBruto = $this->cantidad * $this->precio_unitario;
        $this->subtotal = round($subtotalBruto - $this->descuento_monto, 2);

        $igvPorcentaje = Cache::remember(
            "empresa_{$this->comprobante->empresa_id}_igv",
            86400,
            fn() => $this->comprobante->empresa->configuracion->igv_porcentaje ?? 18
        );

        $this->igv = match (true) {
            $this->tipo_afectacion === 'gravado' && !$this->comprobante->es_exportacion =>
                round($this->subtotal * ($igvPorcentaje / 100), 2),
            default => 0.00,
        };

        $this->total = round($this->subtotal + $this->igv, 2);
    }

    // === VALIDACIÓN ===
    public function validarTotal(): bool
    {
        $calculado = round(
            ($this->precio_unitario * $this->cantidad) - $this->descuento_monto + $this->igv,
            2
        );
        return abs($this->total - $calculado) < 0.01;
    }

    // === ACCESSORS ===
    public function getDescripcionAttribute(): string
    {
        return $this->producto?->nombre ?? 'Producto eliminado';
    }

    public function getCodigoAttribute(): string
    {
        return $this->producto?->codigo ?? 'N/A';
    }

    public function getUnidadMedidaAttribute(): string
    {
        return $this->producto?->unidad_medida ?? 'NIU';
    }

    public function getTipoAfectacionCodigoAttribute(): string
    {
        return match ($this->tipo_afectacion) {
            'gravado' => '10',
            'exonerado' => '20',
            'inafecto' => '30',
            default => '40',
        };
    }

    public function getValorUnitarioAttribute(): float
    {
        return round($this->precio_unitario / (1 + ($this->igv / $this->subtotal)), 6);
    }

    // === MÉTODOS DE NEGOCIO ===
    public function esGravado(): bool { return $this->tipo_afectacion === 'gravado'; }
    public function esExonerado(): bool { return $this->tipo_afectacion === 'exonerado'; }
    public function esInafecto(): bool { return $this->tipo_afectacion === 'inafecto'; }

    public function tieneDescuento(): bool { return $this->descuento_monto > 0; }
}