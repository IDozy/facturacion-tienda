<?php

namespace App\Models\Facturacion;

use App\Models\Empresa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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

    // Relaciones

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    /**
     * Incrementa el correlativo de manera segura (bloqueo FOR UPDATE)
     */

    public function incrementarCorrelativo(): int
    {
        return DB::transaction(function () {
            $serie = DB::table('series')
                ->where('id', $this->id)
                ->lockForUpdate()
                ->first();

            $nuevoCorrelativo = $serie->correlativo_actual + 1;

            DB::table('series')
                ->where('id', $this->id)
                ->update(['correlativo_actual' => $nuevoCorrelativo]);

            return $nuevoCorrelativo;
        });
    }


    /**
     * Devuelve el numero completo (SERIE-NUMERO)
     */

    public function generarNumero(): string
    {
        $numero = str_pad($this->incrementarCorrelativo(), 8, '0', STR_PAD_LEFT);
        return "{$this->serie}-{$numero}";
    }
}
