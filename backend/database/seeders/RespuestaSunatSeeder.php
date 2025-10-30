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

            // === RESPUESTA PARA COMPROBANTE 4 (Factura F002-01891) - ACEPTADA CON OBSERVACIONES ===
            [
                'comprobante_id' => 4,
                'codigo_respuesta' => '4000',
                'descripcion_respuesta' => 'El comprobante fue aceptado con observaciones: El tipo de cambio es menor al tipo de cambio de la fecha de emisión publicado por SUNAT',
                'intento' => 2, // Se aceptó en el segundo intento
                'fecha_proximo_reintento' => null,
                'cdr' => $cdrExample,
                'xml' => $xmlExample,
                'estado_envio' => 'aceptado',
                'created_at' => Carbon::today()->subDays(10)->setTime(11, 45, 0),
                'updated_at' => Carbon::today()->subDays(10)->setTime(11, 50, 0),
            ],

            // === EJEMPLO DE COMPROBANTE RECHAZADO ===
            [
                'comprobante_id' => null, // Comprobante de prueba
                'codigo_respuesta' => '2017',
                'descripcion_respuesta' => 'El numero de documento del receptor debe ser RUC',
                'intento' => 1,
                'fecha_proximo_reintento' => null,
                'cdr' => null,
                'xml' => $xmlExample,
                'estado_envio' => 'rechazado',
                'created_at' => Carbon::today()->subDays(7),
                'updated_at' => Carbon::today()->subDays(7),
            ],

            // === EJEMPLO DE COMPROBANTE PENDIENTE DE REINTENTO ===
            [
                'comprobante_id' => null, // Comprobante de prueba
                'codigo_respuesta' => '1033',
                'descripcion_respuesta' => 'El servicio de SUNAT no está disponible temporalmente',
                'intento' => 2,
                'fecha_proximo_reintento' => Carbon::now()->addMinutes(10), // Programado para reintentar
                'cdr' => null,
                'xml' => $xmlExample,
                'estado_envio' => 'pendiente',
                'created_at' => Carbon::today()->subHours(2),
                'updated_at' => Carbon::now()->subMinutes(30),
            ],

            // === EJEMPLO DE ERROR DESPUÉS DE 3 INTENTOS ===
            [
                'comprobante_id' => null, // Comprobante de prueba
                'codigo_respuesta' => '2204',
                'descripcion_respuesta' => 'La Factura Electrónica no está autorizada a ser emitida en el Sistema de Emisión Electrónica SUNAT',
                'intento' => 3, // Máximo de intentos alcanzado
                'fecha_proximo_reintento' => null,
                'cdr' => null,
                'xml' => $xmlExample,
                'estado_envio' => 'rechazado',
                'created_at' => Carbon::today()->subDays(3),
                'updated_at' => Carbon::today()->subDays(3),
            ],
        ]);
    }
}