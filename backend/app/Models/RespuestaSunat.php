<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RespuestaSunat extends Model
{
    use HasFactory;

    protected $fillable = [
        'comrobante_id',
        'codigo_respuesta',
        'descripcion_respuesta',
        'intento',
        'fecha_proximo_reintento',
        'cdr',
        'xml',
        'estado_envio',
    ];

    protected $casts = [
        'fecha_proximo_reintento' => 'datetime',
    ];

    /**
     * Estado de envío:
     * - pendiente: aún no se ha enviado a SUNAT
     * - en_proceso: en cola o en envío
     * - aceptado: SUNAT aceptó el comprobante
     * - rechazado: SUNAT lo rechazó
     * - error: fallo técnico, puede reintentarse
     */
    public const ESTADOS = [
        'pendiente',
        'en_proceso',
        'aceptado',
        'rechazado',
        'error',
    ];

    // Relación 1:1 con Comprobante
    public function comprobante()
    {
        return $this->belongsTo(Comprobante::class);
    }

    // Marcar intento fallido y programar nuevo reintento
    public function registrarError(string $descripcion, ?int $minutosReintento = 15): void
    {
        $this->update([
            'estado_envio' => 'error',
            'descripcion_respuesta' => $descripcion,
            'intento' => $this->intento + 1,
            'fecha_proximo_reintento' => now()->addMinutes($minutosReintento),
        ]);
    }

    // Registrar respuesta aceptada
    public function registrarAceptado(string $codigo, string $descripcion, ?string $cdr = null): void
    {
        $this->update([
            'estado_envio' => 'aceptado',
            'codigo_respuesta' => $codigo,
            'descripcion_respuesta' => $descripcion,
            'cdr' => $cdr,
            'fecha_proximo_reintento' => null,
        ]);
    }
}
