<?php

namespace App\Models\Compras;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Empresa;
use Illuminate\Support\Facades\Auth;

class Proveedor extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'proveedores';

    protected $fillable = [
        'tipo_documento',
        'numero_documento',
        'razon_social',
        'direccion',
        'telefono',
        'email',
        'empresa_id',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'estado' => 'string',
            'empresa_id' => 'integer',
        ];
    }

    // === MULTI-TENANCY ===
    protected static function booted()
    {
        static::creating(function ($proveedor) {
            if (Auth::check() && !$proveedor->empresa_id) {
                $proveedor->empresa_id = Auth::user()->empresa_id;
            }
        });
    }

    // === RELACIONES ===
    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function compras()
    {
        return $this->hasMany(Compra::class);
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

    public function scopeBuscar($query, $termino)
    {
        return $query->where(function ($q) use ($termino) {
            $q->where('razon_social', 'like', "%{$termino}%")
              ->orWhere('numero_documento', 'like', "%{$termino}%")
              ->orWhere('email', 'like', "%{$termino}%");
        });
    }

    public function scopeDeEmpresa($query, $empresaId)
    {
        return $query->where('empresa_id', $empresaId);
    }

    // === ACCESSORS ===
    public function getDocumentoCompletoAttribute(): string
    {
        return "{$this->tipo_documento}: {$this->numero_documento}";
    }

    public function getNombreCortoAttribute(): string
    {
        return str($this->razon_social)->limit(30)->toString();
    }

    // === MÉTODOS DE NEGOCIO ===
    public function totalComprado(): float
    {
        return $this->compras()
            ->where('estado', '!=', 'anulada')
            ->sum('total');
    }

    public function ultimaCompra()
    {
        return $this->compras()
            ->where('estado', '!=', 'anulada')
            ->latest('fecha_emision')
            ->first();
    }

    public function esActivo(): bool
    {
        return $this->estado === 'activo';
    }

    public function activar(): self
    {
        $this->update(['estado' => 'activo']);
        return $this;
    }

    public function inactivar(): self
    {
        $this->update(['estado' => 'inactivo']);
        return $this;
    }
}