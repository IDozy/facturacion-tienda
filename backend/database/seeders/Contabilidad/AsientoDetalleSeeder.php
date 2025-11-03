<?php

namespace Database\Seeders\Contabilidad;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AsientoDetalleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener asientos existentes
        $asientos = DB::table('asientos')->get();
        
        if ($asientos->isEmpty()) {
            $this->command->error('No hay asientos registrados. Por favor, ejecuta primero el seeder de asientos.');
            return;
        }

        // Obtener cuentas del plan contable
        $cuentas = DB::table('plan_cuentas')->get();
        
        if ($cuentas->isEmpty()) {
            $this->command->error('No hay cuentas registradas. Por favor, ejecuta primero el seeder de plan de cuentas.');
            return;
        }

        $now = Carbon::now();

        foreach ($asientos as $asiento) {
            // Crear 2 detalles para cada asiento (uno al debe y otro al haber)
            $detalles = [
                [
                    'asiento_id' => $asiento->id,
                    'cuenta_id' => $cuentas[0]->id, // Cuenta de efectivo
                    'descripcion' => 'Movimiento al debe - ' . $asiento->glosa,
                    'debe' => $asiento->total_debe,
                    'haber' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'asiento_id' => $asiento->id,
                    'cuenta_id' => isset($cuentas[1]) ? $cuentas[1]->id : $cuentas[0]->id, // Cuenta de mercaderías
                    'descripcion' => 'Movimiento al haber - ' . $asiento->glosa,
                    'debe' => 0,
                    'haber' => $asiento->total_haber,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ];

            foreach ($detalles as $detalle) {
                DB::table('asiento_detalles')->insert($detalle);
            }
        }

        $this->command->info('Detalles de asientos creados exitosamente.');
    }
}