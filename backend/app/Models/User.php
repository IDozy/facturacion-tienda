<?php

namespace App\Models;

use App\Models\Facturacion\Comprobante;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, SoftDeletes;

    protected $fillable = [
        'nombre',
        'email',
        'password',
        'tipo_documento',
        'numero_documento',
        'telefono',
        'empresa_id',
        'activo',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'activo' => 'boolean',
    ];


    // === GLOBAL SCOPE MULTI-TENANCY ===
    protected static function booted()
    {
        static::addGlobalScope('empresa', function ($query) {
            if (app()->runningInConsole()) return; // evita afectar seeders y migraciones
            if (Auth::check() && $empresaId = Auth::user()->empresa_id) {
                $query->where('empresa_id', $empresaId);
            }
        });
    }


    // === RELACIONES ===
    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function cajas()
    {
        return $this->hasMany(Caja::class, 'usuario_id');
    }

    public function comprobantes()
    {
        return $this->hasMany(Comprobante::class, 'usuario_id');
    }

    public function auditorias()
    {
        return $this->hasMany(Auditoria::class, 'usuario_id');
    }

    // === MUTATOR ===
    public function setPasswordAttribute($value)
    {
        if ($value && !Hash::needsRehash($value)) {
            $this->attributes['password'] = $value;
            return;
        }

        $this->attributes['password'] = Hash::make($value);
    }


    // === ACCESSORS ===
    public function getNombreCompletoAttribute(): string
    {
        return trim("{$this->nombre} ({$this->numero_documento})");
    }

    // === SCOPES ===
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopeDeEmpresa($query, $empresaId)
    {
        return $query->where('empresa_id', $empresaId);
    }
}
