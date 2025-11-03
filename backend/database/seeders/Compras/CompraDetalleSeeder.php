<?php

namespace Database\Seeders\Compras;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CompraDetalleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener todas las compras
        $compras = DB::table('compras')->get();
        
        if ($compras->isEmpty()) {
            $this->command->error('No hay compras registradas. Por favor, ejecuta primero el seeder de compras.');
            return;
        }

        // Obtener productos disponibles
        $productos = DB::table('productos')->get();
        
        if ($productos->isEmpty()) {
            $this->command->error('No hay productos registrados. Por favor, ejecuta primero el seeder de productos.');
            return;
        }

        $now = Carbon::now();

        foreach ($compras as $compra) {
            // Crear 2 detalles por cada compra
            $detalles = [
                [
                    'compra_id' => $compra->id,
                    'producto_id' => $productos[0]->id,
                    'cantidad' => 10.000,
                    'precio_unitario' => 125.00,
                    'subtotal' => 1250.00, // 10 * 125
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'compra_id' => $compra->id,
                    'producto_id' => isset($productos[1]) ? $productos[1]->id : $productos[0]->id,
                    'cantidad' => 5.000,
                    'precio_unitario' => 250.00,
                    'subtotal' => 1250.00, // 5 * 250
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ];

            foreach ($detalles as $detalle) {
                DB::table('compra_detalles')->insert($detalle);
            }

            // Actualizar el total de la compra (suma de los subtotales)
            $totalCompra = DB::table('compra_detalles')
                ->where('compra_id', $compra->id)
                ->sum('subtotal');
            
            DB::table('compras')
                ->where('id', $compra->id)
                ->update([
                    'total' => $totalCompra,
                    'updated_at' => $now,
                ]);
        }

        $this->command->info('Detalles de compras creados exitosamente.');
    }
}