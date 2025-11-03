<?php

namespace Database\Seeders\Contabilidad;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PeriodoContableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $empresaIds = DB::table('empresas')->pluck('id')->toArray();
        
        if (empty($empresaIds)) {
            $this->command->error('No hay empresas registradas. Por favor, ejecuta primero el seeder de empresas.');
            return;
        }

        $now = Carbon::now();
        $currentMonth = $now->month;
        $currentYear = $now->year;
        
        // Crear periodos para el mes actual y el mes anterior
        $periodos = [
            [
                'mes' => $currentMonth == 1 ? 12 : $currentMonth - 1,
                'año' => $currentMonth == 1 ? $currentYear - 1 : $currentYear,
                'estado' => 'cerrado',
                'cerrado_por' => DB::table('users')->first()->id ?? null,
                'cerrado_en' => $now->copy()->subMonth()->endOfMonth(),
            ],
            [
                'mes' => $currentMonth,
                'año' => $currentYear,
                'estado' => 'abierto',
                'cerrado_por' => null,
                'cerrado_en' => null,
            ],
        ];

        foreach ($empresaIds as $empresaId) {
            foreach ($periodos as $periodo) {
                // Calcular fecha inicio y fin del periodo
                $fechaPeriodo = Carbon::create($periodo['año'], $periodo['mes'], 1);
                $fechaInicio = $fechaPeriodo->copy()->startOfMonth();
                $fechaFin = $fechaPeriodo->copy()->endOfMonth();

                DB::table('periodos_contables')->insert([
                    'empresa_id' => $empresaId,
                    'mes' => $periodo['mes'],
                    'año' => $periodo['año'],
                    'estado' => $periodo['estado'],
                    'fecha_inicio' => $fechaInicio->format('Y-m-d'),
                    'fecha_fin' => $fechaFin->format('Y-m-d'),
                    'cerrado_por' => $periodo['cerrado_por'],
                    'cerrado_en' => $periodo['cerrado_en'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $this->command->info('Periodos contables creados exitosamente.');
    }
}