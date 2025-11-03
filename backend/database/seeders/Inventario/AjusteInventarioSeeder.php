<?php

namespace Database\Seeders\Inventario;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AjusteInventarioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener almacenes disponibles
        $almacenes = DB::table('almacenes')->get();
        
        if ($almacenes->isEmpty()) {
            $this->command->error('No hay almacenes registrados. Por favor, ejecuta primero el seeder de almacenes.');
            return;
        }

        // Obtener un usuario
        $usuario = DB::table('users')->first();
        
        $now = Carbon::now();

        $ajustes = [
            [
                'almacen_id' => $almacenes[0]->id,
                'usuario_id' => $usuario ? $usuario->id : null,
                'tipo_ajuste' => 'conteo_fisico',
                'observacion' => 'Ajuste por inventario físico mensual - diferencias encontradas',
                'fecha_ajuste' => $now->copy()->subDays(10)->format('Y-m-d'),
                'estado' => 'aplicado',
                'created_at' => $now->copy()->subDays(10),
                'updated_at' => $now->copy()->subDays(10),
            ],
            [
                'almacen_id' => isset($almacenes[1]) ? $almacenes[1]->id : $almacenes[0]->id,
                'usuario_id' => $usuario ? $usuario->id : null,
                'tipo_ajuste' => 'merma',
                'observacion' => 'Productos dañados por humedad en almacén',
                'fecha_ajuste' => $now->format('Y-m-d'),
                'estado' => 'pendiente',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($ajustes as $ajuste) {
            DB::table('ajustes_inventario')->insert($ajuste);
        }

        $this->command->info('Ajustes de inventario creados exitosamente.');
    }
}