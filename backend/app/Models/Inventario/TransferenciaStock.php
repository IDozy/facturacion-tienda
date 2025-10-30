<?php

namespace App\Models\Inventario;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class TransferenciaStock extends Model
{
    use HasFactory;

    protected $table = 'transferencias_stock';

    protected $fillable = [
        'almacen_origen_id',
        'almacen_destino_id',
        'usuario_id',
        'observacion',
        'fecha_transferencia',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'fecha_transferencia' => 'datetime',
            'estado' => 'string',
        ];
    }

    protected static function booted()
    {
        static::creating(function ($t) {
            $t->usuario_id ??= Auth::id();
            $t->fecha_transferencia ??= now();
            $t->estado ??= 'pendiente';
        });
    }

    // === RELACIONES ===
    public function almacenOrigen(): BelongsTo
    {
        return $this->belongsTo(Almacen::class, 'almacen_origen_id');
    }

    public function almacenDestino(): BelongsTo
    {
        return $this->belongsTo(Almacen::class, 'almacen_destino_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function movimientosStock(): MorphMany
    {
        return $this->morphMany(MovimientoStock::class, 'referencia');
    }

    // === ESTADO ===
    public function esPendiente(): bool { return $this->estado === 'pendiente'; }
    public function esAplicada(): bool { return $this->estado === 'aplicada'; }
    public function esAnulada(): bool { return $this->estado === 'anulada'; }

    // === CREAR CON MOVIMIENTOS ===
    public static function crearConMovimientos(array $data, array $detalles): self
    {
        return DB::transaction(function () use ($data, $detalles) {
            $transferencia = self::create($data);

            foreach ($detalles as $detalle) {
                $productoId = $detalle['producto_id'];
                $cantidad = $detalle['cantidad'];
                $costo = $detalle['costo_unitario'] ?? null;

                // Validar stock origen
                $stockOrigen = AlmacenProducto::getStock($detalle['almacen_origen_id'], $productoId);
                if ($stockOrigen < $cantidad) {
                    $producto = \App\Models\Inventario\Producto::find($productoId);
                    throw new \Exception("Stock insuficiente: {$producto->codigo} ({$stockOrigen} disponible)");
                }

                // Costo promedio con cache
                $costoFinal = $costo ?? Cache::remember(
                    "producto_{$productoId}_costo_promedio",
                    3600,
                    fn() => \App\Models\Inventario\Producto::find($productoId)->getCostoPromedio()
                );

                $ref = "Transferencia #{$transferencia->id}";

                // SALIDA ORIGEN
                $transferencia->movimientosStock()->create([
                    'producto_id' => $productoId,
                    'almacen_id' => $detalle['almacen_origen_id'],
                    'tipo' => 'salida',
                    'cantidad' => $cantidad,
                    'costo_unitario' => $costoFinal,
                    'referencia_tipo' => self::class,
                    'referencia_id' => $transferencia->id,
                    'observacion' => $ref,
                ]);

                // ENTRADA DESTINO
                $transferencia->movimientosStock()->create([
                    'producto_id' => $productoId,
                    'almacen_id' => $detalle['almacen_destino_id'],
                    'tipo' => 'entrada',
                    'cantidad' => $cantidad,
                    'costo_unitario' => $costoFinal,
                    'referencia_tipo' => self::class,
                    'referencia_id' => $transferencia->id,
                    'observacion' => $ref,
                ]);
            }

            return $transferencia;
        });
    }

    // === APLICAR TRANSFERENCIA ===
    public function aplicar(): self
    {
        if ($this->esAplicada()) return $this;

        DB::transaction(function () {
            foreach ($this->movimientosStock as $mov) {
                $ap = AlmacenProducto::firstOrCreate(
                    ['almacen_id' => $mov->almacen_id, 'producto_id' => $mov->producto_id],
                    ['stock_actual' => 0]
                );

                if ($mov->tipo === 'salida') {
                    $ap->restarStock($mov->cantidad);
                } else {
                    $ap->sumarStock($mov->cantidad);
                }
            }

            $this->update(['estado' => 'aplicada']);
        });

        return $this;
    }

    // === ANULAR ===
    public function anular(): self
    {
        if (!$this->esAplicada()) return $this;

        DB::transaction(function () {
            foreach ($this->movimientosStock as $mov) {
                $ap = AlmacenProducto::where('almacen_id', $mov->almacen_id)
                    ->where('producto_id', $mov->producto_id)
                    ->first();

                if ($ap) {
                    if ($mov->tipo === 'salida') {
                        $ap->sumarStock($mov->cantidad);
                    } else {
                        $ap->restarStock($mov->cantidad);
                    }
                }
            }

            $this->update(['estado' => 'anulada']);
        });

        return $this;
    }
}