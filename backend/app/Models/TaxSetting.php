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

        // ❌ Eliminado: 'codigo_establecimiento'

        'certificado_url',
        'certificado_estado',
        'certificado_vigencia_desde',
        'certificado_vigencia_hasta',
        'ambiente',

        // ✅ SUNAT SOL (cifrado) + flags (sin exponer secretos)
        'sunat_user_encrypted',
        'sunat_password_encrypted',
        'has_sol_credentials',

        // ✅ Certificado digital (storage key + metadatos)
        'certificate_storage_key',
        'certificate_password_encrypted',
        'certificate_valid_from',
        'certificate_valid_until',
        'certificate_status',

        'updated_by',
    ];

    protected $casts = [
        'certificado_vigencia_desde' => 'date',
        'certificado_vigencia_hasta' => 'date',

        'certificate_valid_from' => 'date',
        'certificate_valid_until' => 'date',
        'has_sol_credentials' => 'boolean',
    ];
}
