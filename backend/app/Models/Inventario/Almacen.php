<?php

namespace App\Models\Inventario;

use App\Models\Empresa;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Almacen extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'ubicacion',
        'empresa_id',
        'responsable_id',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    // Relaciones
    public function empresa(){
        return $this->belongsTo(Empresa::class);
    }

    public function responsable(){
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function movimientosStock(){
        return $this->hasMany(MovimientoStock::class);
    }

}
