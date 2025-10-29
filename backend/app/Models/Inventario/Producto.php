<?php

namespace App\Models\Inventario;

use App\Models\Empresa;
use App\Models\Facturacion\ComprobanteDetalle;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Producto extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable =[
        'codigo',
        'nombre',
        'descripcion',
        'categoria_id',
        'unidad_medida',
        'precio_compra',
        'precio_venta',
        'stock_minimo',
        'empresa_id',
    ];

    // Relaciones
    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function comprobanteDetalles()
    {
        return $this->hasMany(ComprobanteDetalle::class);
    }

    public function movimientosStock()
    {
        return $this->hasMany(MovimientoStock::class);
    }
}
