<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Auditoria extends Model {
    protected $fillable = ['usuario_id', 'modelo', 'modelo_id', 'accion', 'cambios', 'ip', 'user_agent'];
    protected $casts = ['cambios' => 'json'];
    
    public function usuario() { return $this->belongsTo(User::class); }
}