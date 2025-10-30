<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class RespuestaSunat extends Model
{
    use HasFactory;

    protected $table = 'respuestas_sunat';

    protected $fillable = [
        'comprobante_id',
        'codigo_respuesta',
        'descripcion_respuesta',
        'intento',
        'fecha_proximo_reintento',
        'cdr',
        'xml',
        'estado_envio',
    ];

    protected $casts = [
        'intento' => 'integer',
        'fecha_proximo_reintento' => 'datetime',
    ];

    protected $hidden = [
        'cdr',
        'xml',
    ];

    const MAX_INTENTOS = 3;

    // Mutators para encriptar CDR y XML
    public function setCdrAttribute($value)
    {
        if ($value) {
            $this->attributes['cdr'] = Crypt::encryptString($value);
        }
    }

    public function getCdrAttribute($value)
    {
        if ($value) {
            return Crypt::decryptString($value);
        }
        return null;
    }

    public function setXmlAttribute($value)
    {
        if ($value) {
            $this->attributes['xml'] = Crypt::encryptString($value);
        }
    }

    public function getXmlAttribute($value)
    {
        if ($value) {
            return Crypt::decryptString($value);
        }
        return null;
    }

    // Relaciones
    public function comprobante()
    {
        return $this->belongsTo(Facturacion\Comprobante::class);
    }

    // Scopes
    public function scopePendientes($query)
    {
        return $query->where('estado_envio', 'pendiente');
    }

    public function scopeAceptados($query)
    {
        return $query->where('estado_envio', 'aceptado');
    }

    public function scopeRechazados($query)
    {
        return $query->where('estado_envio', 'rechazado');
    }

    public function scopeParaReintento($query)
    {
        return $query->where('estado_envio', 'pendiente')
            ->where('intento', '<', self::MAX_INTENTOS)
            ->where(function ($q) {
                $q->whereNull('fecha_proximo_reintento')
                    ->orWhere('fecha_proximo_reintento', '<=', now());
            });
    }

    // Métodos
    public function esAceptado()
    {
        return $this->estado_envio === 'aceptado';
    }

    public function esRechazado()
    {
        return $this->estado_envio === 'rechazado';
    }

    public function esPendiente()
    {
        return $this->estado_envio === 'pendiente';
    }

    public function puedeReintentar()
    {
        return $this->estado_envio === 'pendiente' && 
               $this->intento < self::MAX_INTENTOS;
    }

    public function programarReintento()
    {
        if (!$this->puedeReintentar()) {
            return false;
        }

        $minutosEspera = pow(2, $this->intento) * 5; // 5, 10, 20 minutos
        
        $this->update([
            'fecha_proximo_reintento' => now()->addMinutes($minutosEspera),
        ]);

        return true;
    }

    public function marcarComoAceptado($codigoRespuesta, $descripcion, $cdr = null)
    {
        $this->update([
            'codigo_respuesta' => $codigoRespuesta,
            'descripcion_respuesta' => $descripcion,
            'cdr' => $cdr,
            'estado_envio' => 'aceptado',
        ]);

        // Actualizar estado del comprobante
        $this->comprobante->update(['estado' => 'aceptado_sunat']);
    }

    public function marcarComoRechazado($codigoRespuesta, $descripcion)
    {
        $this->update([
            'codigo_respuesta' => $codigoRespuesta,
            'descripcion_respuesta' => $descripcion,
            'estado_envio' => 'rechazado',
        ]);
    }
}