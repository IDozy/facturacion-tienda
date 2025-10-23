<?php

namespace App\Models\Compras;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\Compras\Proveedor;
use App\Models\User;
use App\Models\Compras\CompraDetalle;
use App\Models\Compras\Recepcion;

class Compra extends Model {
    use SoftDeletes;
    protected $fillable = ['proveedor_id', 'usuario_id', 'numero_comprobante', 'fecha_compra', 'fecha_vencimiento', 'moneda', 'tipo_cambio', 'total_gravada', 'total_exonerada', 'total_igv', 'total_descuentos', 'total', 'estado', 'observaciones'];
    protected $casts = ['fecha_compra' => 'date', 'fecha_vencimiento' => 'date'];
    
    public function proveedor() { return $this->belongsTo(Proveedor::class); }
    public function usuario() { return $this->belongsTo(User::class); }
    public function detalles() { return $this->hasMany(CompraDetalle::class); }
    public function recepciones() { return $this->hasMany(Recepcion::class); }
}