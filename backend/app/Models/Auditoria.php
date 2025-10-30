<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Auditoria extends Model
{
    use HasFactory;

    protected $table = 'auditorias';

    protected $fillable = [
        'usuario_id',
        'tabla',
        'registro_id',
        'accion',
        'valores_anteriores',
        'valores_nuevos',
        'ip',
        'created_at',
    ];

    protected $casts = [
        'valores_anteriores' => 'array',
        'valores_nuevos' => 'array',
        'created_at' => 'datetime',
        'accion' => 'string',
    ];

    public $timestamps = false;

    // === RELACIONES ===
    public function usuario()
    {
        return $this->belongsTo(User::class);
    }

    // === SCOPES ===
    public function scopePorTabla($query, $tabla)
    {
        return $query->where('tabla', $tabla);
    }

    public function scopePorRegistro($query, $tabla, $registroId)
    {
        return $query->where('tabla', $tabla)->where('registro_id', $registroId);
    }

    public function scopePorAccion($query, $accion)
    {
        return $query->where('accion', $accion);
    }

    public function scopePorUsuario($query, $usuarioId)
    {
        return $query->where('usuario_id', $usuarioId);
    }

    public function scopeRecientes($query, $dias = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($dias));
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->whereHas('usuario', fn($qu) => $qu->where('nombre', 'like', "%{$search}%"))
              ->orWhere('tabla', 'like', "%{$search}%")
              ->orWhere('ip', 'like', "%{$search}%");
        });
    }

    // === MÉTODOS ESTÁTICOS ===
    public static function registrar($tabla, $registroId, $accion, $valoresAnteriores = null, $valoresNuevos = null)
    {
        return static::create([
            'usuario_id' => Auth::id(),
            'tabla' => $tabla,
            'registro_id' => $registroId,
            'accion' => $accion,
            'valores_anteriores' => $valoresAnteriores,
            'valores_nuevos' => $valoresNuevos,
            'ip' => request()->ip(),
            'created_at' => now(),
        ]);
    }

    public static function registrarCreacion($modelo)
    {
        return static::registrar(
            $modelo->getTable(),
            $modelo->id,
            'create',
            null,
            $modelo->toArray()
        );
    }

    public static function registrarActualizacion($modelo)
    {
        $dirty = $modelo->getDirty();
        if (empty($dirty)) return null;

        $anteriores = array_intersect_key($modelo->getOriginal(), $dirty);

        return static::registrar(
            $modelo->getTable(),
            $modelo->id,
            'update',
            $anteriores,
            $dirty
        );
    }

    public static function registrarEliminacion($modelo)
    {
        return static::registrar(
            $modelo->getTable(),
            $modelo->id,
            'delete',
            $modelo->toArray(),
            null
        );
    }

    // === ACCESSORS ===
    public function getDescripcionAccionAttribute(): string
    {
        return match ($this->accion) {
            'create' => 'Creación',
            'update' => 'Actualización',
            'delete' => 'Eliminación',
            default => $this->accion,
        };
    }

    public function getCambiosAttribute(): array
    {
        return match ($this->accion) {
            'create' => $this->valores_nuevos ?? [],
            'delete' => $this->valores_anteriores ?? [],
            'update' => $this->calcularDiff(),
            default => [],
        };
    }

    protected function calcularDiff(): array
    {
        if (!$this->valores_anteriores || !$this->valores_nuevos) return [];

        $cambios = [];
        foreach ($this->valores_nuevos as $campo => $nuevo) {
            $anterior = $this->valores_anteriores[$campo] ?? null;
            if ($anterior !== $nuevo) {
                $cambios[$campo] = [
                    'anterior' => $anterior,
                    'nuevo' => $nuevo,
                ];
            }
        }
        return $cambios;
    }
}