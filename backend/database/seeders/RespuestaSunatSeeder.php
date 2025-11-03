<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Carbon\Carbon;

class RespuestaSunatSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Simulamos CDR y XML encriptados (en producción vendrían de SUNAT)
        $cdrExample = Crypt::encryptString('<?xml version="1.0" encoding="UTF-8"?><ar:ApplicationResponse>CDR de ejemplo</ar:ApplicationResponse>');
        $xmlExample = Crypt::encryptString('<?xml version="1.0" encoding="UTF-8"?><Invoice>Factura XML de ejemplo</Invoice>');

        DB::table('respuestas_sunat')->insert([
            // === RESPUESTA PARA COMPROBANTE 1 (Factura F001-00126) - ACEPTADA ===
            [
                'comprobante_id' => 1,
                'codigo_respuesta' => '0',
                'descripcion_respuesta' => 'La Factura numero F001-00126, ha sido aceptada',
                'intento' => 1,
                'fecha_proximo_reintento' => null,
                'cdr' => $cdrExample,
                'xml' => $xmlExample,
                'estado_envio' => 'aceptado',
                'created_at' => Carbon::today()->subDays(5)->setTime(9, 15, 0),
                'updated_at' => Carbon::today()->subDays(5)->setTime(9, 15, 0),
            ],

            // === RESPUESTA PARA COMPROBANTE 2 (Boleta B002-03457) - PENDIENTE ===
            // No tiene respuesta aún porque está en borrador

            // === RESPUESTA PARA COMPROBANTE 3 (Nota de Crédito FC01-00016) - ACEPTADA ===
            [
                'comprobante_id' => 3,
                'codigo_respuesta' => '0',
                'descripcion_respuesta' => 'La Nota de Credito numero FC01-00016, ha sido aceptada',
                'intento' => 1,
                'fecha_proximo_reintento' => null,
                'cdr' => $cdrExample,
                'xml' => $xmlExample,
                'estado_envio' => 'aceptado',
                'created_at' => Carbon::today()->subDays(2)->setTime(10, 30, 0),
                'updated_at' => Carbon::today()->subDays(2)->setTime(10, 30, 0),
            ],

        ]);
    }
}