<?php

namespace App\Models\Facturacion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\{Empresa, Cliente, User, Pago, RespuestaSunat, Retencion};
use App\Models\Inventario\MovimientoStock;
use Illuminate\Support\Facades\Auth;

class Comprobante extends Model
{
    use HasFactory;

    protected $fillable = [
        'cliente_id',
        'empresa_id',
        'serie_id',
        'tipo_comprobante',
        'numero',
        'fecha_emision',
        'total',
        'descuento_total',
        'igv_total',
        'total_neto',
        'subtotal_gravado',
        'subtotal_exonerado',
        'subtotal_inafecto',
        'estado',
        'comprobante_referencia_id',
        'motivo_anulacion',
        'hash_cpe',
        'saldo_pendiente',
        'numero_documento_cliente',
        'razon_social_cliente',
        'tipo_documento_cliente',
        'forma_pago',
        'plazo_pago_dias',
        'es_exportacion',
        'codigo_moneda',
        'tipo_cambio',
        'observaciones',
        'usuario_id',
    ];

    protected $casts = [
        'fecha_emision' => 'date',
        'total' => 'decimal:2',
        'descuento_total' => 'decimal:2',
        'igv_total' => 'decimal:2',
        'total_neto' => 'decimal:2',
        'subtotal_gravado' => 'decimal:2',
        'subtotal_exonerado' => 'decimal:2',
        'subtotal_inafecto' => 'decimal:2',
        'saldo_pendiente' => 'decimal:2',
        'plazo_pago_dias' => 'integer',
        'es_exportacion' => 'boolean',
        'tipo_cambio' => 'decimal:3',
    ];

    // Boot method
    protected static function booted()
    {
        static::creating(function ($comprobante) {
            if (Auth::check() && !$comprobante->empresa_id) {
                $comprobante->empresa_id = Auth::user()->empresa_id;
            }
            if (Auth::check() && !$comprobante->usuario_id) {
                $comprobante->usuario_id = Auth::id();
            }
            
            // Copiar datos del cliente (copy-on-write)
            if ($comprobante->cliente_id) {
                $cliente = $comprobante->cliente;
                $comprobante->tipo_documento_cliente = $cliente->tipo_documento;
                $comprobante->numero_documento_cliente = $cliente->numero_documento;
                $comprobante->razon_social_cliente = $cliente->nombre;
            }
            
            // Si es exportación, IGV es 0
            if ($comprobante->es_exportacion) {
                $comprobante->igv_total = 0;
            }
            
            // Establecer saldo pendiente inicial
            $comprobante->saldo_pendiente = $comprobante->total;
        });

        static::created(function ($comprobante) {
            // Generar movimientos de stock (salidas)
            $comprobante->generarMovimientosStock();
            
            // Obtener siguiente número de serie
            if ($comprobante->serie_id && !$comprobante->numero) {
                $serie = $comprobante->serie;
                $comprobante->numero = $serie->siguienteNumero();
                $comprobante->saveQuietly();
            }
        });
    }

    // Relaciones
    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function serie()
    {
        return $this->belongsTo(Serie::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class);
    }

    public function comprobanteReferencia()
    {
        return $this->belongsTo(Comprobante::class, 'comprobante_referencia_id');
    }

    public function notasCredito()
    {
        return $this->hasMany(Comprobante::class, 'comprobante_referencia_id')
            ->where('tipo_comprobante', 'nota_credito');
    }

    public function notasDebito()
    {
        return $this->hasMany(Comprobante::class, 'comprobante_referencia_id')
            ->where('tipo_comprobante', 'nota_debito');
    }

    public function detalles()
    {
        return $this->hasMany(ComprobanteDetalle::class);
    }

    public function pagos()
    {
        return $this->hasMany(Pago::class);
    }

    public function respuestaSunat()
    {
        return $this->hasOne(RespuestaSunat::class);
    }

    public function retencion()
    {
        return $this->hasOne(Retencion::class);
    }

    public function guiasRemision()
    {
        return $this->hasMany(GuiaRemision::class);
    }

    public function movimientosStock()
    {
        return $this->morphMany(MovimientoStock::class, 'referencia');
    }

    // Scopes
    public function scopeEmitidos($query)
    {
        return $query->where('estado', 'emitido');
    }

    public function scopeAceptadosSunat($query)
    {
        return $query->where('estado', 'aceptado_sunat');
    }

    public function scopeAnulados($query)
    {
        return $query->where('estado', 'anulado');
    }

    public function scopeFacturas($query)
    {
        return $query->where('tipo_comprobante', 'factura');
    }

    public function scopeBoletas($query)
    {
        return $query->where('tipo_comprobante', 'boleta');
    }

    public function scopeConSaldo($query)
    {
        return $query->where('saldo_pendiente', '>', 0);
    }

    public function scopeDelPeriodo($query, $fechaInicio, $fechaFin)
    {
        return $query->whereBetween('fecha_emision', [$fechaInicio, $fechaFin]);
    }

    // Métodos
    public function getNumeroCompletoAttribute()
    {
        return $this->serie->serie . '-' . str_pad($this->numero, 8, '0', STR_PAD_LEFT);
    }

    public function actualizarSaldoPendiente()
    {
        $totalPagado = $this->pagos()
            ->where('estado', 'confirmado')
            ->sum('monto');
        
        $this->saldo_pendiente = $this->total - $totalPagado;
        $this->save();
        
        return $this;
    }

    public function estaPagado()
    {
        return $this->saldo_pendiente <= 0;
    }

    public function anular($motivo)
    {
        if ($this->estado === 'anulado') {
            return $this;
        }

        // Revertir movimientos de stock
        $this->movimientosStock()->delete();
        
        $this->update([
            'estado' => 'anulado',
            'motivo_anulacion' => $motivo,
        ]);

        return $this;
    }

    public function generarMovimientosStock()
    {
        foreach ($this->detalles as $detalle) {
            // Buscar almacén principal (o primer almacén activo)
            $almacen = $this->empresa->almacenes()->activos()->first();
            
            if ($almacen) {
                MovimientoStock::create([
                    'producto_id' => $detalle->producto_id,
                    'almacen_id' => $almacen->id,
                    'tipo' => 'salida',
                    'cantidad' => $detalle->cantidad,
                    'costo_unitario' => $detalle->producto->precio_promedio,
                    'referencia_tipo' => Comprobante::class,
                    'referencia_id' => $this->id,
                ]);

                // Actualizar stock en almacén
                $almacenProducto = $almacen->productos()
                    ->where('producto_id', $detalle->producto_id)
                    ->first();
                    
                if ($almacenProducto) {
                    $almacenProducto->decrement('stock_actual', $detalle->cantidad);
                }
            }
        }
    }

    public function calcularTotales()
    {
        $subtotalGravado = 0;
        $subtotalExonerado = 0;
        $subtotalInafecto = 0;
        $descuentoTotal = 0;
        $igvTotal = 0;

        foreach ($this->detalles as $detalle) {
            switch ($detalle->tipo_afectacion) {
                case 'gravado':
                    $subtotalGravado += $detalle->subtotal;
                    $igvTotal += $detalle->igv;
                    break;
                case 'exonerado':
                    $subtotalExonerado += $detalle->subtotal;
                    break;
                case 'inafecto':
                    $subtotalInafecto += $detalle->subtotal;
                    break;
            }
            $descuentoTotal += $detalle->descuento_monto;
        }

        $this->subtotal_gravado = $subtotalGravado;
        $this->subtotal_exonerado = $subtotalExonerado;
        $this->subtotal_inafecto = $subtotalInafecto;
        $this->descuento_total = $descuentoTotal;
        $this->igv_total = $this->es_exportacion ? 0 : $igvTotal;
        $this->total_neto = $subtotalGravado + $subtotalExonerado + $subtotalInafecto;
        $this->total = $this->total_neto + $this->igv_total - $descuentoTotal;
        
        return $this->save();
    }
}