<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Serie extends Model
{
    protected $table = 'series';

    protected $fillable = [
        'empresa_id',
        'tipo_comprobante',
        'serie',
        'correlativo_actual',
        'descripcion',
        'activo',
        'por_defecto',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'por_defecto' => 'boolean',
    ];

    // Relaciones
    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function comprobantes()
    {
        return $this->hasMany(Comprobante::class, 'serie', 'serie');
    }

    // Método: Obtener siguiente correlativo
    public function obtenerSiguienteCorrelativo()
    {
        $this->increment('correlativo_actual');
        return $this->correlativo_actual;
    }

    // Accesor: Nombre del tipo de comprobante
    public function getTipoComprobanteNombreAttribute()
    {
        $tipos = [
            '01' => 'Factura',
            '03' => 'Boleta',
            '07' => 'Nota de Crédito',
            '08' => 'Nota de Débito',
        ];

        return $tipos[$this->tipo_comprobante] ?? 'Desconocido';
    }
}
