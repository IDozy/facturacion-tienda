<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class TablaSunat extends Model
{
    use HasFactory;

    protected $table = 'tablas_sunat';

    protected $fillable = [
        'codigo',
        'descripcion',
        'tipo_tabla',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'tipo_tabla' => 'string',
    ];

    // === TIPOS DE TABLA ===
    const TIPO_DOCUMENTO = 'tipo_documento';
    const TIPO_AFECTACION = 'tipo_afectacion';
    const UNIDAD_MEDIDA = 'unidad_medida';
    const TIPO_MONEDA = 'tipo_moneda';
    const TIPO_PAIS = 'tipo_pais';
    const TIPO_COMPROBANTE = 'tipo_comprobante';
    const TIPO_OPERACION = 'tipo_operacion';
    const TIPO_NOTA = 'tipo_nota';

    // === SCOPES ===
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopePorTipo($query, $tipo)
    {
        return $query->where('tipo_tabla', $tipo);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('descripcion', 'like', "%{$search}%")
                ->orWhere('codigo', 'like', "%{$search}%");
        });
    }

    // === CATÁLOGOS CACHÉ ===
    public static function tiposDocumento()
    {
        return Cache::remember(
            'sunat_tipos_documento',
            now()->addDay(),
            fn() =>
            static::porTipo(self::TIPO_DOCUMENTO)->activos()->get()
        );
    }

    public static function tiposAfectacion()
    {
        return Cache::remember(
            'sunat_tipos_afectacion',
            now()->addDay(),
            fn() =>
            static::porTipo(self::TIPO_AFECTACION)->activos()->get()
        );
    }

    public static function unidadesMedida()
    {
        return Cache::remember(
            'sunat_unidades_medida',
            now()->addDay(),
            fn() =>
            static::porTipo(self::UNIDAD_MEDIDA)->activos()->get()
        );
    }

    public static function tiposMoneda()
    {
        return Cache::remember(
            'sunat_tipos_moneda',
            now()->addDay(),
            fn() =>
            static::porTipo(self::TIPO_MONEDA)->activos()->get()
        );
    }

    public static function tiposComprobante()
    {
        return Cache::remember(
            'sunat_tipos_comprobante',
            now()->addDay(),
            fn() =>
            static::porTipo(self::TIPO_COMPROBANTE)->activos()->get()
        );
    }

    // === BÚSQUEDA POR CÓDIGO ===
    public static function obtenerPorCodigo(string $codigo, ?string $tipoTabla = null): ?self
    {
        $query = static::where('codigo', $codigo);
        if ($tipoTabla) {
            $query->where('tipo_tabla', $tipoTabla);
        }
        return $query->first();
    }

    // === VALIDACIÓN ===
    public static function validarCodigo(string $codigo, string $tipoTabla): bool
    {
        return static::where('codigo', $codigo)
            ->where('tipo_tabla', $tipoTabla)
            ->where('activo', true)
            ->exists();
    }

    // === ACCESOR ===
    public function getNombreAttribute(): string
    {
        return "{$this->codigo} - {$this->descripcion}";
    }
}
