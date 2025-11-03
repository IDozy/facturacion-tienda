<?php

namespace Database\Seeders\Inventario;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AlmacenProductoSeeder extends Seeder
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

        // Obtener productos disponibles
        $productos = DB::table('productos')->take(2)->get();
        
        if ($productos->isEmpty()) {
            $this->command->error('No hay productos registrados. Por favor, ejecuta primero el seeder de productos.');
            return;
        }

        $now = Carbon::now();

        foreach ($almacenes as $almacen) {
            $stockInicial = 100;
            
            foreach ($productos as $index => $producto) {
                // Variar el stock para cada producto
                $stock = $stockInicial + ($index * 50);
                
                DB::table('almacen_productos')->insert([
                    'almacen_id' => $almacen->id,
                    'producto_id' => $producto->id,
                    'stock_actual' => $stock,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $this->command->info('Stock de productos por almacén creado exitosamente.');
    }
}