<?php

namespace App\Models\Contabilidad;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\Contabilidad\AsientoDetalle;

class PlanCuenta extends Model {
    use SoftDeletes;
    protected $table = 'plan_cuentas';
    protected $fillable = ['cuenta_padre_id', 'codigo', 'nombre', 'tipo', 'naturaleza', 'es_subcuenta', 'descripcion', 'saldo_inicial', 'saldo_actual', 'activo'];
    protected $casts = ['es_subcuenta' => 'boolean', 'activo' => 'boolean'];
    
    public function cuentaPadre() { return $this->belongsTo(PlanCuenta::class, 'cuenta_padre_id'); }
    public function subcuentas() { return $this->hasMany(PlanCuenta::class, 'cuenta_padre_id'); }
    public function asientoDetalles() { return $this->hasMany(AsientoDetalle::class, 'cuenta_id'); }
}