<?php

namespace App\Models\Facturacion;

use App\Models\Empresa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class Serie extends Model
{
    use HasFactory;

    protected $fillable = [
        'empresa_id',
        'tipo_comprobante',
        'serie',
        'correlativo_actual',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'correlativo_actual' => 'integer',
            'activo' => 'boolean',
            'tipo_comprobante' => 'string',
        ];
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

    // === INCREMENTO SEGURO ===
    public function incrementarCorrelativo(): int
    {
        return DB::transaction(function () {
            $serie = DB::table('series')
                ->where('id', $this->id)
                ->lockForUpdate()
                ->first();

            if (!$serie) {
                throw new \Exception("Serie no encontrada: {$this->id}");
            }

            $nuevo = $serie->correlativo_actual + 1;

            DB::table('series')
                ->where('id', $this->id)
                ->update(['correlativo_actual' => $nuevo]);

            // Cache opcional
            Cache::put("serie_{$this->id}_correlativo", $nuevo, 60);

            return $nuevo;
        });
    }

    // === NÚMERO FORMATEADO ===
    public function generarNumero(): string
    {
        $numero = $this->incrementarCorrelativo();
        $formateado = str_pad($numero, 8, '0', STR_PAD_LEFT);
        return "{$this->serie}-{$formateado}";
    }

    // === VALIDACIÓN SUNAT ===
    public function validarFormato(): bool
    {
        $formatos = [
            'factura' => '/^F\d{3}$/',
            'boleta' => '/^B\d{3}$/',
            'nota_credito' => '/^(F|B)\d{3}$/',
            'nota_debito' => '/^(F|B)\d{3}$/',
        ];

        if (!isset($formatos[$this->tipo_comprobante])) {
            return true;
        }

        return preg_match($formatos[$this->tipo_comprobante], $this->serie) === 1;
    }

    // === ESTADO ===
    public function activar(): self
    {
        $this->update(['activo' => true]);
        return $this;
    }

    public function desactivar(): self
    {
        $this->update(['activo' => false]);
        return $this;
    }

    public function esActiva(): bool
    {
        return $this->activo;
    }

    // === SCOPES ===
    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    public function scopePorTipo($query, string $tipo)
    {
        return $query->where('tipo_comprobante', $tipo);
    }

    public function scopeDeEmpresa($query, int $empresaId)
    {
        return $query->where('empresa_id', $empresaId);
    }
}
