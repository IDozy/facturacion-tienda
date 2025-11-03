<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RetencionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener comprobantes disponibles
        $comprobantes = DB::table('comprobantes')->take(2)->get();
        
        if ($comprobantes->isEmpty()) {
            $this->command->error('No hay comprobantes registrados. Por favor, ejecuta primero el seeder de comprobantes.');
            return;
        }

        $now = Carbon::now();

        $retenciones = [
            [
                'comprobante_id' => $comprobantes[0]->id,
                'tipo' => 'retencion',
                'monto' => 75.00,
                'porcentaje' => 3.00, // 3% de retención típica
                'estado' => 'aplicada',
                'created_at' => $now->copy()->subDays(5),
                'updated_at' => $now->copy()->subDays(5),
            ],
            [
                'comprobante_id' => isset($comprobantes[1]) ? $comprobantes[1]->id : $comprobantes[0]->id,
                'tipo' => 'percepcion',
                'monto' => 50.00,
                'porcentaje' => 2.00, // 2% de percepción
                'estado' => 'pendiente',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($retenciones as $retencion) {
            DB::table('retenciones')->insert($retencion);
        }

        $this->command->info('Retenciones creadas exitosamente.');
    }
}