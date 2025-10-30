<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SerieSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('series')->insert([
            // === SERIES PARA EMPRESA 1 (EMPRESA DEMO SAC) ===
            [
                'empresa_id' => 1,
                'tipo_comprobante' => 'factura',
                'serie' => 'F001',
                'correlativo_actual' => 125,
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'empresa_id' => 1,
                'tipo_comprobante' => 'boleta',
                'serie' => 'B001',
                'correlativo_actual' => 456,
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'empresa_id' => 1,
                'tipo_comprobante' => 'nota_credito',
                'serie' => 'FC01',
                'correlativo_actual' => 15,
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'empresa_id' => 1,
                'tipo_comprobante' => 'nota_debito',
                'serie' => 'FD01',
                'correlativo_actual' => 5,
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // === SERIES PARA EMPRESA 2 (CORPORACIÓN EJEMPLO EIRL) ===
            [
                'empresa_id' => 2,
                'tipo_comprobante' => 'factura',
                'serie' => 'F002',
                'correlativo_actual' => 1890,
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'empresa_id' => 2,
                'tipo_comprobante' => 'factura',
                'serie' => 'F003',
                'correlativo_actual' => 0,
                'activo' => false, // Serie de respaldo inactiva
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'empresa_id' => 2,
                'tipo_comprobante' => 'boleta',
                'serie' => 'B002',
                'correlativo_actual' => 3456,
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'empresa_id' => 2,
                'tipo_comprobante' => 'nota_credito',
                'serie' => 'FC02',
                'correlativo_actual' => 89,
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'empresa_id' => 2,
                'tipo_comprobante' => 'nota_debito',
                'serie' => 'FD02',
                'correlativo_actual' => 12,
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}