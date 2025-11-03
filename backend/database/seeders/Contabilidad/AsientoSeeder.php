<?php

namespace Database\Seeders\Contabilidad;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AsientoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener diarios disponibles
        $diarios = DB::table('diarios')->get();
        
        if ($diarios->isEmpty()) {
            $this->command->error('No hay diarios registrados. Por favor, ejecuta primero el seeder de diarios.');
            return;
        }

        // Obtener periodo contable abierto
        $periodoAbierto = DB::table('periodos_contables')
            ->where('estado', 'abierto')
            ->first();
        
        if (!$periodoAbierto) {
            $this->command->error('No hay periodos contables abiertos. Por favor, ejecuta primero el seeder de periodos.');
            return;
        }

        // Obtener un usuario para el registro
        $usuario = DB::table('users')->first();
        
        // Obtener comprobantes si existen
        $comprobantes = DB::table('comprobantes')->get();
        
        $now = Carbon::now();

        // Crear un asiento para cada diario (máximo 2)
        $contador = 0;
        foreach ($diarios->take(2) as $diario) {
            $contador++;
            
            // Actualizar correlativo del diario
            $nuevoCorrelativo = $diario->correlativo_actual + 1;
            $numeroAsiento = str_pad($nuevoCorrelativo, 5, '0', STR_PAD_LEFT);
            
            $asiento = [
                'diario_id' => $diario->id,
                'periodo_contable_id' => $periodoAbierto->id,
                'comprobante_id' => isset($comprobantes[$contador - 1]) ? $comprobantes[$contador - 1]->id : null,
                'registrado_por' => $usuario ? $usuario->id : null,
                'numero' => $numeroAsiento,
                'fecha' => $contador == 1 
                    ? $now->copy()->subDays(5)->format('Y-m-d')
                    : $now->format('Y-m-d'),
                'glosa' => $contador == 1 
                    ? 'Registro de compra de mercadería'
                    : 'Registro de venta de productos',
                'total_debe' => $contador == 1 ? 2500.00 : 3200.00,
                'total_haber' => $contador == 1 ? 2500.00 : 3200.00,
                'estado' => 'registrado',
                'registrado_en' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            DB::table('asientos')->insert($asiento);
            
            // Actualizar correlativo del diario
            DB::table('diarios')
                ->where('id', $diario->id)
                ->update([
                    'correlativo_actual' => $nuevoCorrelativo,
                    'updated_at' => $now
                ]);
        }

        $this->command->info('Asientos creados exitosamente.');
    }
}