<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TablaSunatSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Catálogos oficiales de SUNAT más utilizados en facturación electrónica
     */
    public function run(): void
    {
        DB::table('tablas_sunat')->insert([
            // === CATÁLOGO 06: TIPO DE DOCUMENTO DE IDENTIDAD ===
            [
                'codigo' => '0',
                'descripcion' => 'DOC.TRIB.NO.DOM.SIN.RUC',
                'tipo_tabla' => 'tipo_documento',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo' => '1',
                'descripcion' => 'DNI',
                'tipo_tabla' => 'tipo_documento',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo' => '4',
                'descripcion' => 'CARNET DE EXTRANJERIA',
                'tipo_tabla' => 'tipo_documento',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo' => '6',
                'descripcion' => 'RUC',
                'tipo_tabla' => 'tipo_documento',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo' => '7',
                'descripcion' => 'PASAPORTE',
                'tipo_tabla' => 'tipo_documento',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo' => 'A',
                'descripcion' => 'CED. DIPLOMATICA DE IDENTIDAD',
                'tipo_tabla' => 'tipo_documento',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // === CATÁLOGO 01: TIPO DE COMPROBANTE ===
            [
                'codigo' => '01',
                'descripcion' => 'FACTURA',
                'tipo_tabla' => 'tipo_comprobante',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo' => '03',
                'descripcion' => 'BOLETA DE VENTA',
                'tipo_tabla' => 'tipo_comprobante',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo' => '07',
                'descripcion' => 'NOTA DE CREDITO',
                'tipo_tabla' => 'tipo_comprobante',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo' => '08',
                'descripcion' => 'NOTA DE DEBITO',
                'tipo_tabla' => 'tipo_comprobante',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo' => '09',
                'descripcion' => 'GUIA DE REMISION REMITENTE',
                'tipo_tabla' => 'tipo_comprobante',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // === CATÁLOGO 07: TIPO DE AFECTACIÓN DEL IGV ===
            [
                'codigo' => '10',
                'descripcion' => 'GRAVADO - OPERACIÓN ONEROSA',
                'tipo_tabla' => 'tipo_afectacion_igv',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo' => '11',
                'descripcion' => 'GRAVADO - RETIRO POR PREMIO',
                'tipo_tabla' => 'tipo_afectacion_igv',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo' => '20',
                'descripcion' => 'EXONERADO - OPERACIÓN ONEROSA',
                'tipo_tabla' => 'tipo_afectacion_igv',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo' => '30',
                'descripcion' => 'INAFECTO - OPERACIÓN ONEROSA',
                'tipo_tabla' => 'tipo_afectacion_igv',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo' => '40',
                'descripcion' => 'EXPORTACIÓN DE BIENES O SERVICIOS',
                'tipo_tabla' => 'tipo_afectacion_igv',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // === CATÁLOGO 03: UNIDAD DE MEDIDA COMERCIAL ===
            [
                'codigo' => 'NIU',
                'descripcion' => 'UNIDAD (BIENES)',
                'tipo_tabla' => 'unidad_medida',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo' => 'ZZ',
                'descripcion' => 'UNIDAD (SERVICIOS)',
                'tipo_tabla' => 'unidad_medida',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo' => 'KGM',
                'descripcion' => 'KILOGRAMO',
                'tipo_tabla' => 'unidad_medida',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo' => 'MTR',
                'descripcion' => 'METRO',
                'tipo_tabla' => 'unidad_medida',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo' => 'LTR',
                'descripcion' => 'LITRO',
                'tipo_tabla' => 'unidad_medida',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo' => 'BX',
                'descripcion' => 'CAJA',
                'tipo_tabla' => 'unidad_medida',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo' => 'PK',
                'descripcion' => 'PAQUETE',
                'tipo_tabla' => 'unidad_medida',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo' => 'GLI',
                'descripcion' => 'GALON INGLES (4,545956L)',
                'tipo_tabla' => 'unidad_medida',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // === CATÁLOGO 02: TIPO DE MONEDA ===
            [
                'codigo' => 'PEN',
                'descripcion' => 'SOL',
                'tipo_tabla' => 'tipo_moneda',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo' => 'USD',
                'descripcion' => 'DOLAR AMERICANO',
                'tipo_tabla' => 'tipo_moneda',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo' => 'EUR',
                'descripcion' => 'EURO',
                'tipo_tabla' => 'tipo_moneda',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // === CATÁLOGO 09: TIPO DE NOTA DE CRÉDITO ===
            [
                'codigo' => '01',
                'descripcion' => 'ANULACIÓN DE LA OPERACIÓN',
                'tipo_tabla' => 'tipo_nota_credito',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo' => '02',
                'descripcion' => 'ANULACIÓN POR ERROR EN EL RUC',
                'tipo_tabla' => 'tipo_nota_credito',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo' => '03',
                'descripcion' => 'CORRECCIÓN POR ERROR EN LA DESCRIPCIÓN',
                'tipo_tabla' => 'tipo_nota_credito',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo' => '04',
                'descripcion' => 'DESCUENTO GLOBAL',
                'tipo_tabla' => 'tipo_nota_credito',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo' => '05',
                'descripcion' => 'DESCUENTO POR ÍTEM',
                'tipo_tabla' => 'tipo_nota_credito',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo' => '06',
                'descripcion' => 'DEVOLUCIÓN TOTAL',
                'tipo_tabla' => 'tipo_nota_credito',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo' => '07',
                'descripcion' => 'DEVOLUCIÓN POR ÍTEM',
                'tipo_tabla' => 'tipo_nota_credito',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // === CATÁLOGO 10: TIPO DE NOTA DE DÉBITO ===
            [
                'codigo' => '01',
                'descripcion' => 'INTERESES POR MORA',
                'tipo_tabla' => 'tipo_nota_debito',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo' => '02',
                'descripcion' => 'AUMENTO EN EL VALOR',
                'tipo_tabla' => 'tipo_nota_debito',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo' => '03',
                'descripcion' => 'PENALIDADES',
                'tipo_tabla' => 'tipo_nota_debito',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // === CATÁLOGO 51: TIPO DE OPERACIÓN ===
            [
                'codigo' => '0101',
                'descripcion' => 'VENTA INTERNA',
                'tipo_tabla' => 'tipo_operacion',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo' => '0200',
                'descripcion' => 'EXPORTACIÓN DE BIENES',
                'tipo_tabla' => 'tipo_operacion',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo' => '0201',
                'descripcion' => 'EXPORTACIÓN DE SERVICIOS',
                'tipo_tabla' => 'tipo_operacion',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // === CATÁLOGO 12: CÓDIGOS DE DOCUMENTOS RELACIONADOS TRIBUTARIOS ===
            [
                'codigo' => '01',
                'descripcion' => 'FACTURA - EMITIDA PARA CORREGIR ERROR EN EL RUC',
                'tipo_tabla' => 'doc_relacionado',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo' => '02',
                'descripcion' => 'FACTURA - EMITIDA POR ANTICIPOS',
                'tipo_tabla' => 'doc_relacionado',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo' => '03',
                'descripcion' => 'BOLETA DE VENTA - EMITIDA PARA CORREGIR ERROR EN EL RUC',
                'tipo_tabla' => 'doc_relacionado',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
