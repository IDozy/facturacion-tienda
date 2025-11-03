<?php

namespace Database\Seeders\Facturacion;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ComprobanteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('comprobantes')->insert([
            // === COMPROBANTE 1 - FACTURA EMITIDA Y ACEPTADA ===
            [
                // Relaciones
                'cliente_id' => 1, // Carlos Mendoza Villegas (DNI)
                'empresa_id' => 1, // EMPRESA DEMO SAC
                'serie_id' => 1, // F001
                'usuario_id' => 1, // Juan Pérez García
                'comprobante_referencia_id' => null,

                // Datos del comprobante
                'tipo_comprobante' => 'factura',
                'numero' => 'F001-00126',
                'fecha_emision' => Carbon::today()->subDays(5),
                
                // Montos
                'total' => 1180.00,
                'descuento_total' => 20.00,
                'igv_total' => 180.00,
                'total_neto' => 1180.00,
                'subtotal_gravado' => 1000.00,
                'subtotal_exonerado' => 0.00,
                'subtotal_inafecto' => 0.00,
                'saldo_pendiente' => 0.00, // Ya pagado

                // Estado
                'estado' => 'aceptado_sunat',
                'motivo_anulacion' => null,

                // SUNAT
                'hash_cpe' => 'ABC123XYZ789DEF456GHI012JKL345MNO678PQR901STU234VWX567YZA890BCD',

                // Datos del cliente (snapshot)
                'tipo_documento_cliente' => 'DNI',
                'numero_documento_cliente' => '45678912',
                'razon_social_cliente' => 'Carlos Mendoza Villegas',

                // Forma de pago
                'forma_pago' => 'contado',
                'plazo_pago_dias' => null,

                // Otros
                'es_exportacion' => false,
                'codigo_moneda' => 'PEN',
                'tipo_cambio' => null,
                'observaciones' => 'Venta de productos varios - Cliente frecuente',

                // Timestamps
                'created_at' => Carbon::today()->subDays(5),
                'updated_at' => Carbon::today()->subDays(5),
            ],

            // === COMPROBANTE 2 - BOLETA EN BORRADOR ===
            [
                // Relaciones
                'cliente_id' => 2, // DISTRIBUIDORA NACIONAL SAC
                'empresa_id' => 2, // CORPORACIÓN EJEMPLO EIRL
                'serie_id' => 7, // B002
                'usuario_id' => 2, // María Rodriguez Lopez
                'comprobante_referencia_id' => null,

                // Datos del comprobante
                'tipo_comprobante' => 'boleta',
                'numero' => 'B002-03457',
                'fecha_emision' => Carbon::today(),
                
                // Montos
                'total' => 354.00,
                'descuento_total' => 0.00,
                'igv_total' => 54.00,
                'total_neto' => 354.00,
                'subtotal_gravado' => 300.00,
                'subtotal_exonerado' => 0.00,
                'subtotal_inafecto' => 0.00,
                'saldo_pendiente' => 354.00, // Pendiente de pago

                // Estado
                'estado' => 'borrador',
                'motivo_anulacion' => null,

                // SUNAT
                'hash_cpe' => null, // Aún no enviado

                // Datos del cliente (snapshot)
                'tipo_documento_cliente' => 'RUC',
                'numero_documento_cliente' => '20567891234',
                'razon_social_cliente' => 'DISTRIBUIDORA NACIONAL SAC',

                // Forma de pago
                'forma_pago' => 'credito',
                'plazo_pago_dias' => 30,

                // Otros
                'es_exportacion' => false,
                'codigo_moneda' => 'PEN',
                'tipo_cambio' => null,
                'observaciones' => 'Pendiente de aprobación del cliente',

                // Timestamps
                'created_at' => Carbon::today(),
                'updated_at' => Carbon::today(),
            ],

            // === COMPROBANTE 3 - NOTA DE CRÉDITO (ANULACIÓN DE FACTURA) ===
            [
                // Relaciones
                'cliente_id' => 1, // Carlos Mendoza Villegas
                'empresa_id' => 1, // EMPRESA DEMO SAC
                'serie_id' => 3, // FC01
                'usuario_id' => 1, // Juan Pérez García
                'comprobante_referencia_id' => 1, // Referencia a la factura F001-00126

                // Datos del comprobante
                'tipo_comprobante' => 'nota_credito',
                'numero' => 'FC01-00016',
                'fecha_emision' => Carbon::today()->subDays(2),
                
                // Montos (parcial)
                'total' => 236.00,
                'descuento_total' => 0.00,
                'igv_total' => 36.00,
                'total_neto' => 236.00,
                'subtotal_gravado' => 200.00,
                'subtotal_exonerado' => 0.00,
                'subtotal_inafecto' => 0.00,
                'saldo_pendiente' => 0.00,

                // Estado
                'estado' => 'aceptado_sunat',
                'motivo_anulacion' => null,

                // SUNAT
                'hash_cpe' => 'NCR456DEF789GHI012JKL345MNO678PQR901STU234VWX567YZA890BCD123XYZ',

                // Datos del cliente (snapshot)
                'tipo_documento_cliente' => 'DNI',
                'numero_documento_cliente' => '45678912',
                'razon_social_cliente' => 'Carlos Mendoza Villegas',

                // Forma de pago
                'forma_pago' => 'contado',
                'plazo_pago_dias' => null,

                // Otros
                'es_exportacion' => false,
                'codigo_moneda' => 'PEN',
                'tipo_cambio' => null,
                'observaciones' => 'Devolución parcial de mercadería - Producto defectuoso',

                // Timestamps
                'created_at' => Carbon::today()->subDays(2),
                'updated_at' => Carbon::today()->subDays(2),
            ],

            // === COMPROBANTE 4 - FACTURA EN DÓLARES ===
            [
                // Relaciones
                'cliente_id' => 2, // DISTRIBUIDORA NACIONAL SAC
                'empresa_id' => 2, // CORPORACIÓN EJEMPLO EIRL
                'serie_id' => 5, // F002
                'usuario_id' => 2, // María Rodriguez Lopez
                'comprobante_referencia_id' => null,

                // Datos del comprobante
                'tipo_comprobante' => 'factura',
                'numero' => 'F002-01891',
                'fecha_emision' => Carbon::today()->subDays(10),
                
                // Montos en USD
                'total' => 2360.00,
                'descuento_total' => 100.00,
                'igv_total' => 360.00,
                'total_neto' => 2360.00,
                'subtotal_gravado' => 2000.00,
                'subtotal_exonerado' => 0.00,
                'subtotal_inafecto' => 0.00,
                'saldo_pendiente' => 1000.00, // Pago parcial

                // Estado
                'estado' => 'aceptado_sunat',
                'motivo_anulacion' => null,

                // SUNAT
                'hash_cpe' => 'USD789GHI012JKL345MNO678PQR901STU234VWX567YZA890BCD123XYZ456ABC',

                // Datos del cliente (snapshot)
                'tipo_documento_cliente' => 'RUC',
                'numero_documento_cliente' => '20567891234',
                'razon_social_cliente' => 'DISTRIBUIDORA NACIONAL SAC',

                // Forma de pago
                'forma_pago' => 'credito',
                'plazo_pago_dias' => 60,

                // Otros
                'es_exportacion' => true,
                'codigo_moneda' => 'USD',
                'tipo_cambio' => 3.750,
                'observaciones' => 'Exportación de mercadería - Pago 60% adelantado',

                // Timestamps
                'created_at' => Carbon::today()->subDays(10),
                'updated_at' => Carbon::today()->subDays(8),
            ],
        ]);
    }
}