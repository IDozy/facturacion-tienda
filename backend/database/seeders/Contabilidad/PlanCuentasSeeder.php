<?php

namespace Database\Seeders\Contabilidad;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PlanCuentasSeeder extends Seeder
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

        foreach ($empresaIds as $empresaId) {
            // Crear cuentas principales (nivel 1)
            $cuentas = [
                [
                    'codigo' => '10',
                    'nombre' => 'EFECTIVO Y EQUIVALENTES DE EFECTIVO',
                    'tipo' => 'activo',
                    'padre_id' => null,
                    'nivel' => 1,
                    'es_auxiliar' => false,
                    'empresa_id' => $empresaId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'codigo' => '20',
                    'nombre' => 'MERCADERÍAS',
                    'tipo' => 'activo',
                    'padre_id' => null,
                    'nivel' => 1,
                    'es_auxiliar' => false,
                    'empresa_id' => $empresaId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ];

            foreach ($cuentas as $cuenta) {
                DB::table('plan_cuentas')->insert($cuenta);
            }
        }

        $this->command->info('Plan de cuentas creado exitosamente.');
    }
}