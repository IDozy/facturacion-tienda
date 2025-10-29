<?php

namespace App\Models\Inventario;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransferenciaStock extends Model
{
    use HasFactory;

    protected $fillable = [
        'almacen_origen_id',
        'almacen_destino_id',
        'usuario_id',
        'observacion',
    ];

    // Relaciones
    public function almacenOrigen(){
        return $this->belongsTo(Almacen::class, 'almacen_origen_id');
    }

    public function almacenDestino(){
        return $this->belongsTo(Almacen::class, 'almacen_destino_id');
    }

    public function usuario(){
        return $this->belongsTo(User::class);
    }

    public function movimientosStock(){
        return $this->morphMany(MovimientoStock::class, 'referencia');
    }
}
