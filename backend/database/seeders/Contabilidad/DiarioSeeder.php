<?php

namespace Database\Seeders\Contabilidad;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DiarioSeeder extends Seeder
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

        $diarios = [
            [
                'codigo' => 'DV',
                'nombre' => 'Diario de Ventas',
                'tipo' => 'automatico',
                'prefijo' => 'DV-',
                'correlativo_actual' => 0,
                'descripcion' => 'Registro automático de operaciones de ventas',
                'activo' => true,
            ],
            [
                'codigo' => 'DC',
                'nombre' => 'Diario de Compras',
                'tipo' => 'automatico',
                'prefijo' => 'DC-',
                'correlativo_actual' => 0,
                'descripcion' => 'Registro automático de operaciones de compras',
                'activo' => true,
            ],
        ];

        $now = Carbon::now();

        foreach ($empresaIds as $empresaId) {
            foreach ($diarios as $diario) {
                DB::table('diarios')->insert([
                    'empresa_id' => $empresaId,
                    'codigo' => $diario['codigo'],
                    'nombre' => $diario['nombre'],
                    'tipo' => $diario['tipo'],
                    'prefijo' => $diario['prefijo'],
                    'correlativo_actual' => $diario['correlativo_actual'],
                    'descripcion' => $diario['descripcion'],
                    'activo' => $diario['activo'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $this->command->info('Diarios creados exitosamente.');
    }
}