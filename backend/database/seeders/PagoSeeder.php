<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PagoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('pagos')->insert([
            // === PAGOS PARA COMPROBANTE 1 (Factura F001-00126 - PAGADO COMPLETO) ===
            [
                'comprobante_id' => 1,
                'medio_pago_id' => 8, // EFECTIVO (009)
                'caja_id' => 1, // Caja abierta del usuario 1
                'monto' => 1180.00,
                'fecha_pago' => Carbon::today()->subDays(5),
                'numero_referencia' => null,
                'estado' => 'confirmado',
                'fecha_confirmacion' => Carbon::today()->subDays(5)->setTime(10, 30, 0),
                'cuota_numero' => 1,
                'created_at' => Carbon::today()->subDays(5),
                'updated_at' => Carbon::today()->subDays(5),
            ],

            // === PAGOS PARA COMPROBANTE 2 (Boleta B002-03457 - PENDIENTE) ===
            // No tiene pagos aún (está en borrador)

            // === PAGOS PARA COMPROBANTE 3 (Nota de Crédito - APLICADA) ===
            [
                'comprobante_id' => 3,
                'medio_pago_id' => 3, // TRANSFERENCIA (003)
                'caja_id' => 2, // Caja cerrada del usuario 1
                'monto' => 236.00,
                'fecha_pago' => Carbon::today()->subDays(2),
                'numero_referencia' => 'DEV-2024-0236',
                'estado' => 'confirmado',
                'fecha_confirmacion' => Carbon::today()->subDays(2)->setTime(14, 15, 0),
                'cuota_numero' => 1,
                'created_at' => Carbon::today()->subDays(2),
                'updated_at' => Carbon::today()->subDays(2),
            ],

            // === PAGOS PARA COMPROBANTE 4 (Factura F002-01891 - PAGO PARCIAL) ===
            // Primer pago - 60% adelantado
            [
                'comprobante_id' => 4,
                'medio_pago_id' => 3, // TRANSFERENCIA (003)
                'caja_id' => 2, // Caja cerrada del usuario 2
                'monto' => 1360.00, // 60% de 2360
                'fecha_pago' => Carbon::today()->subDays(10),
                'numero_referencia' => 'TRANS-INT-2024-1891-01',
                'estado' => 'confirmado',
                'fecha_confirmacion' => Carbon::today()->subDays(10)->setTime(11, 0, 0),
                'cuota_numero' => 1,
                'created_at' => Carbon::today()->subDays(10),
                'updated_at' => Carbon::today()->subDays(10),
            ],
            
            // Segundo pago pendiente de confirmación
            [
                'comprobante_id' => 4,
                'medio_pago_id' => 6, // TARJETA DE CRÉDITO (005)
                'caja_id' => null, // Aún no asignado a caja
                'monto' => 500.00,
                'fecha_pago' => Carbon::today()->subDays(3),
                'numero_referencia' => 'TC-VISA-****4567',
                'estado' => 'pendiente',
                'fecha_confirmacion' => null,
                'cuota_numero' => 2,
                'created_at' => Carbon::today()->subDays(3),
                'updated_at' => Carbon::today()->subDays(3),
            ],

            // === EJEMPLOS DE PAGOS CON MEDIOS DIGITALES ===
            [
                'comprobante_id' => 1,
                'medio_pago_id' => 13, // YAPE
                'caja_id' => 1,
                'monto' => 150.00,
                'fecha_pago' => Carbon::today()->subDays(1),
                'numero_referencia' => 'YAPE-999888777',
                'estado' => 'confirmado',
                'fecha_confirmacion' => Carbon::today()->subDays(1)->setTime(16, 45, 0),
                'cuota_numero' => 1,
                'created_at' => Carbon::today()->subDays(1),
                'updated_at' => Carbon::today()->subDays(1),
            ],

            [
                'comprobante_id' => 1,
                'medio_pago_id' => 14, // PLIN
                'caja_id' => 1,
                'monto' => 75.00,
                'fecha_pago' => Carbon::today(),
                'numero_referencia' => 'PLIN-2024-ABC123',
                'estado' => 'pendiente',
                'fecha_confirmacion' => null,
                'cuota_numero' => 1,
                'created_at' => Carbon::today(),
                'updated_at' => Carbon::today(),
            ],
        ]);
    }
}