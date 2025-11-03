<?php

namespace Database\Seeders\Compras;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CompraSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener IDs necesarios
        $empresaIds = DB::table('empresas')->pluck('id')->toArray();
        
        if (empty($empresaIds)) {
            $this->command->error('No hay empresas registradas. Por favor, ejecuta primero el seeder de empresas.');
            return;
        }

        $now = Carbon::now();

        foreach ($empresaIds as $empresaId) {
            // Obtener proveedores y almacenes de esta empresa
            $proveedores = DB::table('proveedores')
                ->where('empresa_id', $empresaId)
                ->pluck('id')
                ->toArray();
            
            $almacenes = DB::table('almacenes')
                ->where('empresa_id', $empresaId)
                ->pluck('id')
                ->toArray();

            if (empty($proveedores) || empty($almacenes)) {
                $this->command->warn("No hay proveedores o almacenes para la empresa ID: {$empresaId}. Saltando...");
                continue;
            }

            $compras = [
                [
                    'proveedor_id' => $proveedores[0],
                    'empresa_id' => $empresaId,
                    'almacen_id' => $almacenes[0],
                    'fecha_emision' => Carbon::now()->subDays(15)->format('Y-m-d'),
                    'total' => 2500.00,
                    'estado' => 'registrada',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'proveedor_id' => isset($proveedores[1]) ? $proveedores[1] : $proveedores[0],
                    'empresa_id' => $empresaId,
                    'almacen_id' => isset($almacenes[1]) ? $almacenes[1] : $almacenes[0],
                    'fecha_emision' => Carbon::now()->subDays(7)->format('Y-m-d'),
                    'total' => 4750.50,
                    'estado' => 'registrada',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ];

            foreach ($compras as $compra) {
                DB::table('compras')->insert($compra);
            }
        }

        $this->command->info('Compras creadas exitosamente.');
    }
}