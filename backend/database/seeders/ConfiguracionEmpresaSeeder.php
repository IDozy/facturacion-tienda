<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ConfiguracionEmpresaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener empresas disponibles (máximo 2)
        $empresas = DB::table('empresas')->take(2)->get();
        
        if ($empresas->isEmpty()) {
            $this->command->error('No hay empresas registradas. Por favor, ejecuta primero el seeder de empresas.');
            return;
        }

        $now = Carbon::now();

        $configuraciones = [
            [
                'empresa_id' => $empresas[0]->id,
                'igv_porcentaje' => 18.00, // IGV estándar en Perú
                'retencion_porcentaje_default' => 3.00,
                'percepcion_porcentaje_default' => 2.00,
                'moneda_default' => 'PEN',
                'tolerancia_cuadratura' => 1.00,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        // Si hay una segunda empresa, agregar configuración diferente
        if (isset($empresas[1])) {
            $configuraciones[] = [
                'empresa_id' => $empresas[1]->id,
                'igv_porcentaje' => 18.00,
                'retencion_porcentaje_default' => 2.50, // Configuración ligeramente diferente
                'percepcion_porcentaje_default' => 1.50,
                'moneda_default' => 'PEN',
                'tolerancia_cuadratura' => 0.50,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach ($configuraciones as $configuracion) {
            DB::table('configuraciones_empresa')->insert($configuracion);
        }

        $this->command->info('Configuraciones de empresa creadas exitosamente.');
    }
}