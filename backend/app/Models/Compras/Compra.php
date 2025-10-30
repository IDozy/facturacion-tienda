<?php

namespace App\Models\Compras;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Empresa;
use App\Models\Inventario\Almacen;
use App\Models\Inventario\MovimientoStock;
use Illuminate\Support\Facades\Auth;

class Compra extends Model
{
    use HasFactory;

    protected $fillable = [
        'proveedor_id',
        'empresa_id',
        'almacen_id',
        'fecha_emision',
        'total',
        'estado',
    ];

    protected $casts = [
        'fecha_emision' => 'date',
        'total' => 'decimal:2',
    ];

    // Boot method
    protected static function booted()
    {
        static::creating(function ($compra) {
            if (Auth::check() && !$compra->empresa_id) {
                $compra->empresa_id = Auth::user()->empresa_id;
            }
        });

        static::created(function ($compra) {
            // Generar movimientos de stock al crear compra
            $compra->generarMovimientosStock();
        });
    }

    // Relaciones
    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function almacen()
    {
        return $this->belongsTo(Almacen::class);
    }

    public function detalles()
    {
        return $this->hasMany(CompraDetalle::class);
    }

    public function movimientosStock()
    {
        return $this->morphMany(MovimientoStock::class, 'referencia');
    }

    // Scopes
    public function scopeRegistradas($query)
    {
        return $query->where('estado', 'registrada');
    }

    public function scopeAnuladas($query)
    {
        return $query->where('estado', 'anulada');
    }

    public function scopeDelPeriodo($query, $fechaInicio, $fechaFin)
    {
        return $query->whereBetween('fecha_emision', [$fechaInicio, $fechaFin]);
    }

    public function scopeDelProveedor($query, $proveedorId)
    {
        return $query->where('proveedor_id', $proveedorId);
    }

    // Métodos
    public function calcularTotal()
    {
        return $this->detalles->sum('subtotal');
    }

    public function anular(): self
    {
        if ($this->estado === 'anulada') {
            return $this;
        }

        // Revertir stock
        foreach ($this->detalles as $detalle) {
            $almacenProducto = $this->almacen->productos()
                ->where('producto_id', $detalle->producto_id)
                ->first();

            if ($almacenProducto) {
                $almacenProducto->decrement('stock_actual', $detalle->cantidad);
            }
        }

        // Eliminar movimientos
        $this->movimientosStock()->delete();

        $this->update(['estado' => 'anulada']);
        return $this;
    }

    public function generarMovimientosStock(): void
    {
        foreach ($this->detalles as $detalle) {
            MovimientoStock::create([
                'producto_id' => $detalle->producto_id,
                'almacen_id' => $this->almacen_id,
                'tipo' => 'entrada',
                'cantidad' => $detalle->cantidad,
                'costo_unitario' => $detalle->precio_unitario,
                'referencia_tipo' => self::class,
                'referencia_id' => $this->id,
            ]);

            $almacenProducto = $this->almacen->productos()
                ->where('producto_id', $detalle->producto_id)
                ->first();

            if ($almacenProducto) {
                $almacenProducto->increment('stock_actual', $detalle->cantidad);
            } else {
                $this->almacen->productos()->create([
                    'producto_id' => $detalle->producto_id,
                    'stock_actual' => $detalle->cantidad,
                ]);
            }
        }
    }

    public function esRegistrada(): bool
    {
        return $this->estado === 'registrada';
    }

    public function esAnulada(): bool
    {
        return $this->estado === 'anulada';
    }
}
