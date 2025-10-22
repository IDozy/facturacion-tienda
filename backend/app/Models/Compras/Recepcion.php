<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Recepcion extends Model {
    use SoftDeletes;
    protected $fillable = ['compra_id', 'almacen_id', 'usuario_id', 'numero_recepcion', 'fecha_recepcion', 'observaciones', 'estado'];
    protected $casts = ['fecha_recepcion' => 'date'];
    
    public function compra() { return $this->belongsTo(Compra::class); }
    public function almacen() { return $this->belongsTo(Almacen::class); }
    public function usuario() { return $this->belongsTo(User::class); }
    public function detalles() { return $this->hasMany(RecepcionDetalle::class); }
}