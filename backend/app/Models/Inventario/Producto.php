<?php

namespace App\Models\Inventario;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Empresa;
use App\Models\Facturacion\ComprobanteDetalle;
use App\Models\Compras\CompraDetalle;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class Producto extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'productos';

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'categoria_id',
        'unidad_medida',
        'precio_compra',
        'precio_venta',
        'stock_minimo',
        'cod_producto_sunat',
        'empresa_id',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'precio_compra' => 'decimal:2',
            'precio_venta' => 'decimal:2',
            'stock_minimo' => 'decimal:3',
            'estado' => 'string',
            'unidad_medida' => 'string',
        ];
    }

    // === MULTI-TENANCY ===
    protected static function booted()
    {
        static::creating(function ($producto) {
            if (Auth::check() && !$producto->empresa_id) {
                $producto->empresa_id = Auth::user()->empresa_id;
            }
        });
    }

    // === RELACIONES ===
    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function almacenes()
    {
        return $this->hasMany(AlmacenProducto::class);
    }

    public function movimientosStock()
    {
        return $this->hasMany(MovimientoStock::class);
    }

    public function detallesComprobante()
    {
        return $this->hasMany(ComprobanteDetalle::class);
    }

    public function detallesCompra()
    {
        return $this->hasMany(CompraDetalle::class);
    }

    // === SCOPES ===
    public function scopeActivos($query)
    {
        return $query->where('estado', 'activo');
    }

    public function scopeInactivos($query)
    {
        return $query->where('estado', 'inactivo');
    }

    public function scopeBajoStock($query)
    {
        return $query->whereRaw(
            '(SELECT COALESCE(SUM(stock_actual), 0) FROM almacen_productos WHERE producto_id = productos.id) <= stock_minimo'
        );
    }

    public function scopeBuscar($query, $termino)
    {
        return $query->where(function ($q) use ($termino) {
            $q->where('codigo', 'like', "%{$termino}%")
                ->orWhere('nombre', 'like', "%{$termino}%")
                ->orWhere('descripcion', 'like', "%{$termino}%");
        });
    }

    // === ACCESSORS CON CACHE ===
    public function getStockActualAttribute(): float
    {
        return Cache::remember(
            "producto_{$this->id}_stock",
            300,
            fn() =>
            $this->almacenes()->sum('stock_actual') ?? 0
        );
    }

    public function getPrecioPromedioAttribute(): float
    {
        return Cache::remember("producto_{$this->id}_costo_promedio", 3600, function () {
            $movimientos = $this->movimientosStock()
                ->where('tipo', 'entrada')
                ->orderByDesc('created_at')
                ->limit(10)
                ->get();

            if ($movimientos->isEmpty()) {
                return $this->precio_compra;
            }

            $totalCosto = $movimientos->sum(fn($m) => $m->costo_unitario * $m->cantidad);
            $totalCantidad = $movimientos->sum('cantidad');

            return $totalCantidad > 0 ? round($totalCosto / $totalCantidad, 2) : 0;
        });
    }

    public function getMargenAttribute(): float
    {
        return $this->precio_compra > 0
            ? round((($this->precio_venta - $this->precio_compra) / $this->precio_compra) * 100, 2)
            : 0;
    }

    public function getEsBajoStockAttribute(): bool
    {
        return $this->stock_actual <= $this->stock_minimo;
    }

    // === MÉTODOS ===
    public function stockEnAlmacen(int $almacenId): float
    {
        return $this->almacenes()
            ->where('almacen_id', $almacenId)
            ->value('stock_actual') ?? 0;
    }

    public function actualizarPrecios(?float $precioCompra = null, ?float $precioVenta = null): self
    {
        if ($precioCompra !== null) $this->precio_compra = $precioCompra;
        if ($precioVenta !== null) $this->precio_venta = $precioVenta;

        $this->save();
        Cache::forget("producto_{$this->id}_costo_promedio");
        return $this;
    }

    public function calcularCostoPEPS(): float
    {
        $entradas = $this->movimientosStock()
            ->where('tipo', 'entrada')
            ->orderBy('created_at')
            ->get();

        if ($entradas->isEmpty()) {
            return $this->precio_compra;
        }

        $salidas = $this->movimientosStock()
            ->where('tipo', 'salida')
            ->orderBy('created_at')
            ->get();

        $costoTotal = 0;
        $cantidadPendiente = $salidas->sum('cantidad');

        if ($cantidadPendiente <= 0) {
            return $this->precio_promedio;
        }

        // PEPS: usar entradas MÁS ANTIGUAS primero
        foreach ($entradas as $entrada) {
            if ($cantidadPendiente <= 0) break;

            $usar = min($entrada->cantidad, $cantidadPendiente);
            $costoTotal += $usar * $entrada->costo_unitario;
            $cantidadPendiente -= $usar;
        }

        return $cantidadPendiente > 0 ? $this->precio_promedio : round($costoTotal / $salidas->sum('cantidad'), 2);
    }

    public function calcularCostoUEPS(): float
    {
        // 1. Obtener todas las entradas (en orden cronológico)
        $entradas = $this->movimientosStock()
            ->where('tipo', 'entrada')
            ->orderBy('created_at')
            ->get();

        if ($entradas->isEmpty()) {
            return $this->precio_compra;
        }

        // 2. Obtener todas las salidas (en orden cronológico)
        $salidas = $this->movimientosStock()
            ->where('tipo', 'salida')
            ->orderBy('created_at')
            ->get();

        // 3. Simular UEPS: usar las entradas MÁS RECIENTES para cubrir salidas
        $costoTotal = 0;
        $cantidadPendiente = $salidas->sum('cantidad');

        if ($cantidadPendiente <= 0) {
            return $this->precio_promedio;
        }

        // Ir desde la última entrada hacia atrás
        foreach ($entradas->reverse() as $entrada) {
            if ($cantidadPendiente <= 0) break;

            $usar = min($entrada->cantidad, $cantidadPendiente);
            $costoTotal += $usar * $entrada->costo_unitario;
            $cantidadPendiente -= $usar;
        }

        return $cantidadPendiente > 0 ? $this->precio_promedio : round($costoTotal / $salidas->sum('cantidad'), 2);
    }

    public function esActivo(): bool
    {
        return $this->estado === 'activo';
    }

    public function activar(): self
    {
        $this->update(['estado' => 'activo']);
        return $this;
    }

    public function inactivar(): self
    {
        $this->update(['estado' => 'inactivo']);
        return $this;
    }
}
