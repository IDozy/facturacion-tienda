<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedioPago extends Model
{
    use HasFactory;

    protected $table = 'medios_pago';

    protected $fillable = [
        'codigo_sunat',
        'nombre',
        'descripcion',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    // === CONSTANTES SUNAT (Catálogo 59) ===
    const EFECTIVO = '009';
    const DEPOSITO_CUENTA = '001';
    const TRANSFERENCIA = '003';
    const TARJETA_CREDITO = '005';
    const TARJETA_DEBITO = '006';
    const CHEQUE = '007';
    const YAPE = '013';
    const PLIN = '014';

    // === RELACIONES ===
    public function pagos()
    {
        return $this->hasMany(Pago::class, 'medio_pago_id');
    }

    // === SCOPES ===
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopePorCodigo($query, string $codigo)
    {
        return $query->where('codigo_sunat', $codigo);
    }

    // === MÉTODOS ESTÁTICOS ===
    public static function efectivo(): ?self
    {
        return static::porCodigo(self::EFECTIVO)->first();
    }

    public static function transferencia(): ?self
    {
        return static::porCodigo(self::TRANSFERENCIA)->first();
    }

    // === MÉTODOS DE INSTANCIA ===
    public function esEfectivo(): bool
    {
        return $this->codigo_sunat === self::EFECTIVO;
    }

    public function requiereReferencia(): bool
    {
        return in_array($this->codigo_sunat, [
            self::DEPOSITO_CUENTA,
            self::TRANSFERENCIA,
            self::TARJETA_CREDITO,
            self::TARJETA_DEBITO,
            self::CHEQUE,
            self::YAPE,
            self::PLIN,
        ]);
    }

    public function getNombreCortoAttribute(): string
    {
        return str($this->nombre)->limit(20)->toString();
    }
}