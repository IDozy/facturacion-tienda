<?php

namespace App\Models\Inventario;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Empresa;
use Illuminate\Support\Facades\Auth;

class Categoria extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'descripcion',
        'empresa_id',
    ];

    // === MULTI-TENANCY ===
    protected static function booted()
    {
        static::creating(function ($categoria) {
            if (Auth::check() && !$categoria->empresa_id) {
                $categoria->empresa_id = Auth::user()->empresa_id;
            }
        });
    }

    // === RELACIONES ===
    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function productos()
    {
        return $this->hasMany(Producto::class);
    }

    // === SCOPES ===
    public function scopeBuscar($query, $termino)
    {
        return $query->where('nombre', 'like', "%{$termino}%")
            ->orWhere('descripcion', 'like', "%{$termino}%");
    }

    public function scopeConProductos($query)
    {
        return $query->whereHas('productos');
    }

    // === MÉTODOS ===
    public function cantidadProductos(): int
    {
        return $this->productos()->count();
    }

    public function tieneProductos(): bool
    {
        return $this->productos()->exists();
    }

    public function puedeEliminar(): bool
    {
        return !$this->tieneProductos();
    }

    public function getNombreCortoAttribute(): string
    {
        return str($this->nombre)->limit(25)->toString();
    }
}
