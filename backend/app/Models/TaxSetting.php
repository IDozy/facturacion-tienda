<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaxSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'empresa_id',
        'regimen',
        'tipo_contribuyente',
        'afectacion_igv',
        'codigo_establecimiento',
        'certificado_url',
        'certificado_estado',
        'certificado_vigencia_desde',
        'certificado_vigencia_hasta',
        'ambiente',
        'updated_by',
    ];

    protected $casts = [
        'certificado_vigencia_desde' => 'date',
        'certificado_vigencia_hasta' => 'date',
    ];
}
