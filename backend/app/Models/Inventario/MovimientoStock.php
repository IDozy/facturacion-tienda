<?php

namespace App\Models\Inventario;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MovimientoStock extends Model
{
    use HasFactory;

    protected $table = 'movimientos_stock';

    protected $fillable = [
        'producto_id',
        'almacen_id',
        'tipo',
        'cantidad',
        'costo_unitario',
        'referencia_tipo',
        'referencia_id',
    ];

    protected $casts = [
        'cantidad' => 'decimal:3',
        'costo_unitario' => 'decimal:2',
    ];

    // Relaciones
    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function almacen()
    {
        return $this->belongsTo(Almacen::class);
    }

    // Relación polimórfica
    public function referencia()
    {
        return $this->morphTo();
    }

    // Scopes
    public function scopeEntradas($query)
    {
        return $query->where('tipo', 'entrada');
    }

    public function scopeSalidas($query)
    {
        return $query->where('tipo', 'salida');
    }

    public function scopeTransferencias($query)
    {
        return $query->where('tipo', 'transferencia');
    }

    public function scopeDelPeriodo($query, $fechaInicio, $fechaFin)
    {
        return $query->whereBetween('created_at', [$fechaInicio, $fechaFin]);
    }

    // Accessors
    public function getCostoTotalAttribute()
    {
        return $this->cantidad * $this->costo_unitario;
    }

    public function getDescripcionTipoAttribute()
    {
        $tipos = [
            'entrada' => 'Entrada',
            'salida' => 'Salida',
            'transferencia' => 'Transferencia',
        ];

        return $tipos[$this->tipo] ?? $this->tipo;
    }

    // Métodos
    public function esEntrada()
    {
        return $this->tipo === 'entrada';
    }

    public function esSalida()
    {
        return $this->tipo === 'salida';
    }

    public function esTransferencia()
    {
        return $this->tipo === 'transferencia';
    }

    public function getDocumentoOrigen()
    {
        if ($this->referencia) {
            return $this->referencia;
        }
        return null;
    }
}