<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CajaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('cajas')->insert([
            [
                // === CAJA 1 - CAJA ABIERTA (Usuario Admin) ===
                'usuario_id' => 1, // Juan Pérez García - admin@empresademo.com
                
                // Montos
                'monto_inicial' => 500.00,
                'monto_final' => null, // Aún no cerrada
                'total_esperado' => null, // Se calcula al cerrar
                'diferencia_cuadratura' => 0.00,
                
                // Configuración
                'moneda' => 'PEN',
                'estado' => 'abierta',
                
                // Fechas
                'apertura' => Carbon::today()->setTime(8, 0, 0), // Hoy a las 8:00 AM
                'cierre' => null, // Aún no cerrada
                
                // Auditoría
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ],
            [
                // === CAJA 2 - CAJA CERRADA (Usuario Contador) ===
                'usuario_id' => 2, // María Rodriguez Lopez - contador@corporacionejemplo.pe
                
                // Montos
                'monto_inicial' => 1000.00,
                'monto_final' => 2850.00,
                'total_esperado' => 2845.00, // Esperaba 2845
                'diferencia_cuadratura' => 5.00, // Sobrante de 5 soles
                
                // Configuración
                'moneda' => 'PEN',
                'estado' => 'cerrada',
                
                // Fechas
                'apertura' => Carbon::yesterday()->setTime(9, 0, 0), // Ayer a las 9:00 AM
                'cierre' => Carbon::yesterday()->setTime(19, 30, 0), // Ayer a las 7:30 PM
                
                // Auditoría
                'created_at' => Carbon::yesterday()->setTime(9, 0, 0),
                'updated_at' => Carbon::yesterday()->setTime(19, 30, 0),
                'deleted_at' => null,
            ]
        ]);
    }
}