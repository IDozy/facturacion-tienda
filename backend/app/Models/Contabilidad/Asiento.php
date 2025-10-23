<?php

namespace App\Models\Contabilidad;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\User;
use App\Models\Contabilidad\AsientoDetalle;

class Asiento extends Model {
    use SoftDeletes;
    protected $fillable = ['usuario_id', 'numero_asiento', 'diario', 'fecha_asiento', 'descripcion', 'referencia', 'glosa', 'estado', 'total_debe', 'total_haber'];
    protected $casts = ['fecha_asiento' => 'date'];
    
    public function usuario() { return $this->belongsTo(User::class); }
    public function detalles() { return $this->hasMany(AsientoDetalle::class); }
}
