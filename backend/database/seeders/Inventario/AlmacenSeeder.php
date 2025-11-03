<?php

namespace Database\Seeders\Inventario;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AlmacenSeeder extends Seeder
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

        $almacenes = [
            [
                'nombre' => 'Almacén Principal',
                'ubicacion' => 'Av. Industrial 1234, Cercado de Lima',
                'activo' => true,
            ],
            [
                'nombre' => 'Almacén Secundario',
                'ubicacion' => 'Jr. Los Depósitos 567, San Juan de Lurigancho',
                'activo' => true,
            ],
        ];

        $now = Carbon::now();

        foreach ($empresaIds as $empresaId) {
            foreach ($almacenes as $almacen) {
                DB::table('almacenes')->insert([
                    'nombre' => $almacen['nombre'],
                    'ubicacion' => $almacen['ubicacion'],
                    'empresa_id' => $empresaId,
                    'activo' => $almacen['activo'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $this->command->info('Almacenes creados exitosamente.');
    }
}