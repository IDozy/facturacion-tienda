<?php

namespace App\Models\Compras;

use App\Models\Empresa;
use App\Models\Inventario\MovimientoStock;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Compra extends Model
{
    use HasFactory;

    protected $fillable = [
        'proveedor_id',
        'empresa_id',
        'fecha_emision',
        'total',
        'estado',
    ];

    // Relaciones

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function detalles()
    {
        return $this->hasMany(CompraDetalle::class);
    }

    public function movimientosStock()
    {
        return $this->morphMany(MovimientoStock::class, 'referencia');
    }


}
