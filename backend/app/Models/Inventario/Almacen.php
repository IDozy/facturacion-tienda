<?php

namespace App\Models\Inventario;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Empresa;
use App\Models\Compras\Compra;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class Almacen extends Model
{
    use HasFactory;

    protected $table = 'almacenes';

    protected $fillable = [
        'nombre',
        'ubicacion',
        'empresa_id',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    // === MULTI-TENANCY ===
    protected static function booted()
    {
        static::creating(function ($almacen) {
            if (Auth::check() && !$almacen->empresa_id) {
                $almacen->empresa_id = Auth::user()->empresa_id;
            }
        });
    }

    // === RELACIONES ===
    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function productos()
    {
        return $this->hasMany(AlmacenProducto::class);
    }

    public function movimientosStock()
    {
        return $this->hasMany(MovimientoStock::class);
    }

    public function compras()
    {
        return $this->hasMany(Compra::class);
    }

    // === SCOPES ===
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopeInactivos($query)
    {
        return $query->where('activo', false);
    }

    public function scopeConStock($query)
    {
        return $query->whereHas('productos', fn($q) => $q->where('stock_actual', '>', 0));
    }

    // === MÉTODOS CON CACHE ===
    public function stockProducto(int $productoId): float
    {
        return Cache::remember("almacen_{$this->id}_producto_{$productoId}_stock", 300, fn() =>
            $this->productos()->where('producto_id', $productoId)->value('stock_actual') ?? 0
        );
    }

    public function tieneStock(int $productoId, float $cantidad): bool
    {
        return $this->stockProducto($productoId) >= $cantidad;
    }

    public function valorInventario(): float
    {
        return Cache::remember("almacen_{$this->id}_valor_inventario", 3600, function () {
            return $this->productos()->with('producto')->get()
                ->sum(fn($ap) => $ap->stock_actual * $ap->producto->precio_promedio);
        });
    }

    public function productosConBajoStock()
    {
        return Cache::remember("almacen_{$this->id}_bajo_stock", 1800, function () {
            return $this->productos()->with('producto')->get()
                ->filter(fn($ap) => $ap->stock_actual <= $ap->producto->stock_minimo);
        });
    }

    // === ACCIONES ===
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

    public function esActivo(): bool
    {
        return $this->activo;
    }
}