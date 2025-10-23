<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\User;
use App\Models\Permiso;

class Rol extends Model {
    use SoftDeletes;
    protected $fillable = ['nombre', 'descripcion', 'permisos', 'activo'];
    protected $casts = ['permisos' => 'json', 'activo' => 'boolean'];
    
    public function usuarios() { return $this->hasMany(User::class, 'rol_id'); }
    public function permisos() { return $this->belongsToMany(Permiso::class, 'permiso_rol'); }
}