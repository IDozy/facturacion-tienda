<?php

namespace App\Models\Facturacion;

use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Inventario\MovimientoStock;
use App\Models\RespuestaSunat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comprobante extends Model
{
    use HasFactory;

    protected $fillable = [
        'cliente_id',
        'empresa_id',
        'serie_id',
        'tipo_comprobante',
        'numero',
        'fecha_emision',
        'total',
        'estado',
        'documento_referencia_id',
        'motivo_anulacion',
        'hash_cpe',
    ];

    // Relaciones

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function serie()
    {
        return $this->belongsTo(Serie::class);
    }

    public function detalles()
    {
        return $this->hasMany(ComprobanteDetalle::class);
    }

    public function respuestaSunat()
    {
        return $this->hasOne(RespuestaSunat::class);
    }

    public function movimientosStock()
    {
        return $this->morphMany(MovimientoStock::class, 'referencia');
    }

    public function documentoReferencia()
    {
        return $this->belongsTo(Comprobante::class, 'documento_referencia_id');
    }
}
