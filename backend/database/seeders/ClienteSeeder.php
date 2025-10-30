<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ClienteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('clientes')->insert([
            [
                // === CLIENTE 1 - PERSONA NATURAL (DNI) ===
                'tipo_documento' => 'DNI',
                'numero_documento' => '45678912',
                'razon_social' => 'Carlos Mendoza Villegas',
                'direccion' => 'Av. Larco 456, Miraflores, Lima',
                'email' => 'carlos.mendoza@gmail.com',
                'telefono' => '998877665',

                // Multi-tenancy (cliente de EMPRESA DEMO SAC)
                'empresa_id' => 1,

                // Estado
                'estado' => 'activo',

                // Auditoría
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ],
            [
                // === CLIENTE 2 - EMPRESA (RUC) ===
                'tipo_documento' => 'RUC',
                'numero_documento' => '20567891234',
                'razon_social' => 'DISTRIBUIDORA NACIONAL SAC',
                'direccion' => 'Jr. Comercio 789, San Isidro, Lima',
                'email' => 'compras@distribuidoranacional.pe',
                'telefono' => '01-4567890',

                // Multi-tenancy (cliente de CORPORACIÓN EJEMPLO EIRL)
                'empresa_id' => 2,

                // Estado
                'estado' => 'activo',

                // Auditoría
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ]
        ]);
    }
}
