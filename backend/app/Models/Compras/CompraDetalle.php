<?php

namespace App\Models\Compras;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Inventario\Producto;

class CompraDetalle extends Model
{
    use HasFactory;

    protected $table = 'compra_detalles';

    protected $fillable = [
        'compra_id',
        'producto_id',
        'cantidad',
        'precio_unitario',
        'subtotal',
    ];

    protected function casts(): array
    {
        return [
            'cantidad' => 'decimal:3',
            'precio_unitario' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    // === CÁLCULO AUTOMÁTICO ===
    protected static function booted()
    {
        static::creating(fn($d) => $d->subtotal = $d->cantidad * $d->precio_unitario);
        static::updating(function ($d) {
            if ($d->isDirty(['cantidad', 'precio_unitario'])) {
                $d->subtotal = $d->cantidad * $d->precio_unitario;
            }
        });
    }

    // === RELACIONES ===
    public function compra()
    {
        return $this->belongsTo(Compra::class);
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    // === MÉTODOS ===
    public function recalcularSubtotal(): self
    {
        $this->subtotal = $this->cantidad * $this->precio_unitario;
        $this->save();
        return $this;
    }

    public function getTotalAttribute(): float
    {
        return $this->subtotal;
    }

    public function getNombreProductoAttribute(): string
    {
        return $this->producto?->nombre ?? 'Producto eliminado';
    }
}