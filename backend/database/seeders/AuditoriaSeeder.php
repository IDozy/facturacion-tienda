<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AuditoriaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener un usuario
        $usuario = DB::table('users')->first();
        
        // Obtener algunos registros para simular auditoría
        $producto = DB::table('productos')->first();
        $compra = DB::table('compras')->first();
        
        $now = Carbon::now();

        $auditorias = [
            [
                'usuario_id' => $usuario ? $usuario->id : null,
                'tabla' => 'productos',
                'registro_id' => $producto ? $producto->id : 1,
                'accion' => 'create',
                'valores_anteriores' => null,
                'valores_nuevos' => json_encode([
                    'nombre' => 'Producto Ejemplo',
                    'precio' => 100.00,
                    'stock' => 50,
                    'created_at' => $now->copy()->subDays(20)->format('Y-m-d H:i:s')
                ]),
                'ip' => '192.168.1.100',
                'created_at' => $now->copy()->subDays(20),
            ],
            [
                'usuario_id' => $usuario ? $usuario->id : null,
                'tabla' => 'compras',
                'registro_id' => $compra ? $compra->id : 1,
                'accion' => 'update',
                'valores_anteriores' => json_encode([
                    'total' => 2500.00,
                    'estado' => 'registrada'
                ]),
                'valores_nuevos' => json_encode([
                    'total' => 2750.00,
                    'estado' => 'registrada',
                    'updated_at' => $now->copy()->subDays(5)->format('Y-m-d H:i:s')
                ]),
                'ip' => '192.168.1.101',
                'created_at' => $now->copy()->subDays(5),
            ],
        ];

        foreach ($auditorias as $auditoria) {
            DB::table('auditorias')->insert($auditoria);
        }

        $this->command->info('Registros de auditoría creados exitosamente.');
    }
}