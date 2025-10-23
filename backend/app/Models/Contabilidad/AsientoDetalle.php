<?php

namespace App\Models\Contabilidad;

use Illuminate\Database\Eloquent\Model;

use App\Models\Contabilidad\Asiento;
use App\Models\Contabiliad\PlanCuenta;

class AsientoDetalle extends Model {
    protected $fillable = ['asiento_id', 'cuenta_id', 'item', 'descripcion', 'debe', 'haber'];
    protected $casts = ['debe' => 'decimal:2', 'haber' => 'decimal:2'];
    
    public function asiento() { return $this->belongsTo(Asiento::class); }
    public function cuenta() { return $this->belongsTo(PlanCuenta::class, 'cuenta_id'); }
}