<?php

namespace App\Models;

use App\Models\Facturacion\Comprobante;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Cliente extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tipo_documento',
        'numero_documento',
        'razon_social',
        'direccion',
        'email',
        'telefono',
        'empresa_id',
        'estado',
    ];

    protected static function booted()
    {
        static::creating(function ($cliente) {
            if (Auth::check() && !$cliente->empresa_id) {
                $cliente->empresa_id = Auth::user()->empresa_id;
            }
        });
    }

    // === RELACIONES ===
    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function comprobantes()
    {
        return $this->hasMany(Comprobante::class);
    }

    // === SCOPES ===
    public function scopeActivos($query)
    {
        return $query->where('estado', 'activo');
    }

    public function scopeInactivos($query)
    {
        return $query->where('estado', 'inactivo');
    }

    public function scopePorTipoDocumento($query, $tipo)
    {
        return $query->where('tipo_documento', $tipo);
    }

    public function scopePorNumeroDocumento($query, $numero)
    {
        return $query->where('numero_documento', $numero);
    }

    // === ACCESSORS ===
    public function getNombreCompletoAttribute()
    {
        return $this->razon_social;
    }

    public function getDocumentoCompletoAttribute()
    {
        return "{$this->tipo_documento}: {$this->numero_documento}";
    }

    // === MÉTODOS DE UTILIDAD ===
    public function tieneDeuda()
    {
        return $this->comprobantes()
            ->where('estado', '!=', 'anulado')
            ->where('saldo_pendiente', '>', 0)
            ->exists();
    }

    public function montoDeudaTotal()
    {
        return $this->comprobantes()
            ->where('estado', '!=', 'anulado')
            ->sum('saldo_pendiente');
    }
}
