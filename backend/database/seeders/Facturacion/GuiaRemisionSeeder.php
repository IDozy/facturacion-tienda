<?php

namespace Database\Seeders\Facturacion;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class GuiaRemisionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener empresas disponibles
        $empresas = DB::table('empresas')->take(2)->get();
        
        if ($empresas->isEmpty()) {
            $this->command->error('No hay empresas registradas. Por favor, ejecuta primero el seeder de empresas.');
            return;
        }

        // Obtener comprobantes si existen
        $comprobantes = DB::table('comprobantes')->take(2)->get();
        
        $now = Carbon::now();

        $guias = [
            [
                'empresa_id' => $empresas[0]->id,
                'comprobante_id' => $comprobantes->isNotEmpty() ? $comprobantes[0]->id : null,
                'serie' => 'T001',
                'numero' => 1,
                'fecha_emision' => $now->copy()->subDays(7)->format('Y-m-d'),
                'motivo_traslado' => 'venta',
                'peso_total' => 250.50,
                'punto_partida' => 'Av. Industrial 1234, Cercado de Lima, Lima',
                'punto_llegada' => 'Jr. Comercio 567, Miraflores, Lima',
                'transportista_ruc' => '20123456789',
                'transportista_razon_social' => 'TRANSPORTES RÁPIDOS S.A.C.',
                'placa_vehiculo' => 'ABC-123',
                'conductor_dni' => '12345678',
                'conductor_nombre' => 'Juan Pérez García',
                'estado' => 'emitida',
                'motivo_anulacion' => null,
                'created_at' => $now->copy()->subDays(7),
                'updated_at' => $now->copy()->subDays(7),
            ],
            [
                'empresa_id' => isset($empresas[1]) ? $empresas[1]->id : $empresas[0]->id,
                'comprobante_id' => isset($comprobantes[1]) ? $comprobantes[1]->id : null,
                'serie' => 'T001',
                'numero' => isset($empresas[1]) ? 1 : 2, // Si es la misma empresa, usar número 2
                'fecha_emision' => $now->format('Y-m-d'),
                'motivo_traslado' => 'traslado_interno',
                'peso_total' => 180.00,
                'punto_partida' => 'Almacén Principal - Av. Industrial 1234, Lima',
                'punto_llegada' => 'Almacén Secundario - Jr. Los Depósitos 567, SJL',
                'transportista_ruc' => '20987654321',
                'transportista_razon_social' => 'LOGÍSTICA EXPRESS S.A.',
                'placa_vehiculo' => 'XYZ-789',
                'conductor_dni' => '87654321',
                'conductor_nombre' => 'Carlos Rodríguez López',
                'estado' => 'emitida',
                'motivo_anulacion' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($guias as $guia) {
            DB::table('guias_remision')->insert($guia);
        }

        $this->command->info('Guías de remisión creadas exitosamente.');
    }
}