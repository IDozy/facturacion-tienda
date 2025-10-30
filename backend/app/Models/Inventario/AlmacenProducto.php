<?php

namespace App\Models\Inventario;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AlmacenProducto extends Model
{
    use HasFactory;

    protected $table = 'almacen_productos';

    protected $fillable = [
        'almacen_id',
        'producto_id',
        'stock_actual',
    ];

    protected $casts = [
        'stock_actual' => 'decimal:3',
    ];

    // Relaciones
    public function almacen()
    {
        return $this->belongsTo(Almacen::class);
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    // Métodos
    public function ajustarStock($cantidad, $tipo = 'suma')
    {
        if ($tipo === 'suma') {
            $this->stock_actual += $cantidad;
        } elseif ($tipo === 'resta') {
            if ($this->stock_actual < $cantidad) {
                throw new \Exception('Stock insuficiente');
            }
            $this->stock_actual -= $cantidad;
        } else {
            $this->stock_actual = $cantidad;
        }

        return $this->save();
    }

    public function tieneStockSuficiente($cantidad)
    {
        return $this->stock_actual >= $cantidad;
    }

    public function esBajoStock()
    {
        return $this->stock_actual <= $this->producto->stock_minimo;
    }

    public function valorInventario()
    {
        return $this->stock_actual * $this->producto->precio_promedio;
    }
}
