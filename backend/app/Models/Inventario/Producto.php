<?php

namespace App\Models\Inventario;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Inventario\Categoria;
use App\Models\Inventario\Almacen;
use App\Models\Inventario\MovimientoStock;
use App\Models\Compras\CompraDetalle;
use App\Models\Facturacion\ComprobanteDetalle;

class Producto extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'categoria_id',
        'almacen_principal_id',
        'codigo',
        'codigo_sunat',
        'codigo_barras',
        'descripcion',
        'descripcion_larga',
        'unidad_medida',
        'precio_costo',
        'precio_unitario',
        'precio_venta',
        'tipo_igv',
        'porcentaje_igv',
        'stock',
        'stock_minimo',
        'stock_maximo',
        'ubicacion',
        'imagen',
        'stock_por_almacen',
        'activo'
    ];

    protected $casts = [
        'stock_por_almacen' => 'json',
        'activo' => 'boolean',
        'precio_costo' => 'decimal:2',
        'precio_unitario' => 'decimal:2',
        'precio_venta' => 'decimal:2',
        'stock' => 'decimal:2',
        'porcentaje_igv' => 'decimal:2',
    ];

    // Relaciones
    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    public function almacen()
    {
        return $this->belongsTo(Almacen::class, 'almacen_principal_id');
    }

    public function comprobanteDetalles()
    {
        return $this->hasMany(ComprobanteDetalle::class);
    }

    public function compraDetalles()
    {
        return $this->hasMany(CompraDetalle::class);
    }

    public function movimientos()
    {
        return $this->hasMany(MovimientoStock::class);
    }

    // Mutators para sincronización de precios
    public function setPrecioUnitarioAttribute($value)
    {
        $this->attributes['precio_unitario'] = round($value, 2);
        $this->syncPrecioVenta();
    }

    public function setPorcentajeIgvAttribute($value)
    {
        $this->attributes['porcentaje_igv'] = round($value, 2);
        $this->syncPrecioVenta();
    }

    private function syncPrecioVenta()
    {
        if (isset($this->attributes['precio_unitario']) && isset($this->attributes['porcentaje_igv'])) {
            $this->attributes['precio_venta'] = round(
                $this->attributes['precio_unitario'] * (1 + ($this->attributes['porcentaje_igv'] / 100)),
                2
            );
        }
    }

    // Accessors - No necesitas estos, usa los casts
    public function getPrecioConIgvAttribute()
    {
        return round($this->precio_unitario * (1 + ($this->porcentaje_igv / 100)), 2);
    }

    public function getPrecioSinIgvAttribute()
    {
        return round($this->precio_unitario, 2);
    }

    // Helpers útiles
    public function getMargenAttribute()
    {
        if ($this->precio_costo == 0) return 0;
        return round((($this->precio_unitario - $this->precio_costo) / $this->precio_costo) * 100, 2);
    }

    public function getStockDisponibleAttribute()
    {
        return $this->stock - $this->stock_minimo;
    }

    public function estaEnStockBajo(): bool
    {
        return $this->stock <= $this->stock_minimo;
    }

    public function estaInactivo(): bool
    {
        return !$this->activo;
    }

    // Scopes útiles
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopeConStockBajo($query)
    {
        return $query->whereColumn('stock', '<=', 'stock_minimo');
    }

    public function scopePorCategoria($query, $categoriaId)
    {
        return $query->where('categoria_id', $categoriaId);
    }

    public function scopeBuscaPor($query, $termino)
    {
        return $query->where('codigo', 'like', "%{$termino}%")
                     ->orWhere('descripcion', 'like', "%{$termino}%")
                     ->orWhere('codigo_barras', 'like', "%{$termino}%");
    }

    // Métodos de negocio
    public function incrementarStock(float $cantidad, string $motivo = null): void
    {
        $this->update(['stock' => $this->stock + $cantidad]);
        $this->registrarMovimiento('entrada', $cantidad, $motivo);
    }

    public function decrementarStock(float $cantidad, string $motivo = null): void
    {
        if ($this->stock < $cantidad) {
            throw new \Exception('Stock insuficiente');
        }
        $this->update(['stock' => $this->stock - $cantidad]);
        $this->registrarMovimiento('salida', $cantidad, $motivo);
    }

    private function registrarMovimiento(string $tipo, float $cantidad, string $motivo = null): void
    {
        MovimientoStock::create([
            'producto_id' => $this->id,
            'tipo' => $tipo,
            'cantidad' => $cantidad,
            'motivo' => $motivo,
        ]);
    }
}