<?php

namespace App\Models\Compras;

use App\Models\Empresa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Proveedor extends Model
{
    use HasFactory , SoftDeletes;

    protected $fillable = [
        'tipo_documento',
        'numero_documento',
        'razon_social',
        'direccion',
        'telefono',
        'email',
        'empresa_id',
    ];
    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }
}
