<?php

namespace App\Models\Inventario;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class AjusteInventario extends Model
{
    use HasFactory;

    protected $table = 'ajustes_inventario';

    protected $fillable = [
        'almacen_id',
        'usuario_id',
        'tipo_ajuste',
        'observacion',
        'estado',
        'fecha_ajuste',
    ];

    protected function casts(): array
    {
        return [
            'fecha_ajuste' => 'date:Y-m-d',
            'estado' => 'string',
            'tipo_ajuste' => 'string',
        ];
    }

    protected static function booted()
    {
        static::creating(function ($ajuste) {
            $ajuste->usuario_id ??= Auth::id();
            $ajuste->fecha_ajuste ??= now()->format('Y-m-d');
            $ajuste->estado ??= 'pendiente';
        });
    }

    // === RELACIONES ===
    public function almacen()
    {
        return $this->belongsTo(Almacen::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class);
    }

    public function movimientosStock()
    {
        return $this->morphMany(MovimientoStock::class, 'referencia');
    }

    // === SCOPES ===
    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }

    public function scopeAplicados($query)
    {
        return $query->where('estado', 'aplicado');
    }

    public function scopeDelMes($query, $año, $mes)
    {
        return $query->whereYear('fecha_ajuste', $año)
            ->whereMonth('fecha_ajuste', $mes);
    }

    // === MÉTODOS DE ESTADO ===
    public function aplicar(): self
    {
        if ($this->estado !== 'pendiente') {
            return $this;
        }

        foreach ($this->movimientosStock as $movimiento) {
            $almacenProducto = AlmacenProducto::firstOrCreate(
                ['almacen_id' => $this->almacen_id, 'producto_id' => $movimiento->producto_id],
                ['stock_actual' => 0]
            );

            if ($movimiento->tipo === 'entrada') {
                $almacenProducto->sumarStock($movimiento->cantidad);
            } elseif ($movimiento->tipo === 'salida') {
                $almacenProducto->restarStock($movimiento->cantidad);
            }
        }

        $this->update(['estado' => 'aplicado']);
        Cache::forget("almacen_{$this->almacen_id}_valor_inventario");

        return $this;
    }

    public function anular(): self
    {
        if ($this->estado !== 'aplicado') {
            return $this;
        }

        foreach ($this->movimientosStock as $movimiento) {
            $almacenProducto = AlmacenProducto::where('almacen_id', $this->almacen_id)
                ->where('producto_id', $movimiento->producto_id)
                ->first();

            if ($almacenProducto) {
                if ($movimiento->tipo === 'entrada') {
                    $almacenProducto->restarStock($movimiento->cantidad);
                } elseif ($movimiento->tipo === 'salida') {
                    $almacenProducto->sumarStock($movimiento->cantidad);
                }
            }
        }

        $this->update(['estado' => 'anulado']);
        Cache::forget("almacen_{$this->almacen_id}_valor_inventario");

        return $this;
    }

    // === ESTADOS ===
    public function esPendiente(): bool
    {
        return $this->estado === 'pendiente';
    }
    public function esAplicado(): bool
    {
        return $this->estado === 'aplicado';
    }
    public function esAnulado(): bool
    {
        return $this->estado === 'anulado';
    }

    // === ACCESSORS ===
    public function getDescripcionTipoAttribute(): string
    {
        return match ($this->tipo_ajuste) {
            'merma' => 'Ajuste por Merma',
            'sobrante' => 'Ajuste por Sobrante',
            'conteo_fisico' => 'Conteo Físico',
            'otro' => 'Otro Ajuste',
            default => $this->tipo_ajuste,
        };
    }

    public function getCantidadProductosAttribute(): int
    {
        return $this->movimientosStock()->count();
    }
}
