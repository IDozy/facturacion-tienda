<?php

namespace Database\Seeders\Inventario;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TransferenciaStockSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener almacenes disponibles
        $almacenes = DB::table('almacenes')->get();
        
        if ($almacenes->count() < 2) {
            $this->command->error('Se necesitan al menos 2 almacenes. Por favor, ejecuta primero el seeder de almacenes.');
            return;
        }

        // Obtener un usuario
        $usuario = DB::table('users')->first();
        
        $now = Carbon::now();

        $transferencias = [
            [
                'almacen_origen_id' => $almacenes[0]->id,
                'almacen_destino_id' => $almacenes[1]->id,
                'usuario_id' => $usuario ? $usuario->id : null,
                'observacion' => 'Transferencia de mercadería por reposición de stock',
                'fecha_transferencia' => $now->copy()->subDays(3),
                'estado' => 'aplicada',
                'created_at' => $now->copy()->subDays(3),
                'updated_at' => $now->copy()->subDays(3),
            ],
            [
                'almacen_origen_id' => $almacenes[1]->id,
                'almacen_destino_id' => $almacenes[0]->id,
                'usuario_id' => $usuario ? $usuario->id : null,
                'observacion' => 'Transferencia pendiente de productos para campaña',
                'fecha_transferencia' => $now,
                'estado' => 'pendiente',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($transferencias as $transferencia) {
            DB::table('transferencias_stock')->insert($transferencia);
        }

        $this->command->info('Transferencias de stock creadas exitosamente.');
    }
}