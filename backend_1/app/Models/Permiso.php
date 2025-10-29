<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Models\Rol;

class Permiso extends Model {
    protected $fillable = ['nombre', 'descripcion', 'modulo'];
    
    public function roles() { return $this->belongsToMany(Rol::class, 'permiso_rol'); }
}