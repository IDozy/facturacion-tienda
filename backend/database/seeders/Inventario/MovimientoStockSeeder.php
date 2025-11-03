<?php

namespace Database\Seeders\Inventario;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MovimientoStockSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener productos disponibles
        $productos = DB::table('productos')->get();
        
        if ($productos->isEmpty()) {
            $this->command->error('No hay productos registrados. Por favor, ejecuta primero el seeder de productos.');
            return;
        }

        // Obtener almacenes disponibles
        $almacenes = DB::table('almacenes')->get();
        
        if ($almacenes->isEmpty()) {
            $this->command->error('No hay almacenes registrados. Por favor, ejecuta primero el seeder de almacenes.');
            return;
        }

        // Obtener compras para referenciar
        $compras = DB::table('compras')->get();
        
        $now = Carbon::now();

        $movimientos = [
            [
                'producto_id' => $productos[0]->id,
                'almacen_id' => $almacenes[0]->id,
                'tipo' => 'entrada',
                'cantidad' => 100.000,
                'costo_unitario' => 25.50,
                'referencia_id' => isset($compras[0]) ? $compras[0]->id : 1,
                'referencia_type' => 'App\\Models\\Compra',
                'created_at' => $now->copy()->subDays(10),
                'updated_at' => $now->copy()->subDays(10),
            ],
            [
                'producto_id' => isset($productos[1]) ? $productos[1]->id : $productos[0]->id,
                'almacen_id' => isset($almacenes[1]) ? $almacenes[1]->id : $almacenes[0]->id,
                'tipo' => 'salida',
                'cantidad' => 50.000,
                'costo_unitario' => 30.00,
                'referencia_id' => 1, // Podría ser una venta
                'referencia_type' => 'App\\Models\\Venta',
                'created_at' => $now->copy()->subDays(5),
                'updated_at' => $now->copy()->subDays(5),
            ],
        ];

        foreach ($movimientos as $movimiento) {
            DB::table('movimientos_stock')->insert($movimiento);
        }

        $this->command->info('Movimientos de stock creados exitosamente.');
    }
}