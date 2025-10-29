<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Configuracion extends Model
{
    use HasFactory;

    protected $fillable = [
        'clave',
        'valor',
        'tipo',
        'empresa_id',
    ];

    protected $casts = [
        'valor' => 'array',
    ];

    // Relaciones

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public static function obtener(string $clave, ?int $empresaId = null, $porDefecto = null)
    {
        $query = static::where('clave', $clave);

        if ($empresaId) {
            $query->where('empresa_id', $empresaId);
        }

        $config = $query->first();
        return $config ? $config->valor : $porDefecto;
    }

    public static function establecer(string $clave, $valor, ?int $empresaId = null, string $tipo = 'texto')
    {
        return static::updateOrCreate(
            ['clave' => $clave, 'empresa_id' => $empresaId],
            ['valor' => $valor, 'tipo' => $tipo]
        );
    }
}
