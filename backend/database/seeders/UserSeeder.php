<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('users')->insert([
            [
                // === USUARIO 1 - ADMINISTRADOR EMPRESA DEMO ===
                'nombre' => 'Juan Pérez García',
                'email' => 'admin@empresademo.com',
                'email_verified_at' => now(),
                'password' => Hash::make('password123'),

                // Datos personales
                'tipo_documento' => 'DNI',
                'numero_documento' => '12345678',
                'telefono' => '999888777',

                // Multi-tenancy (relacionado con empresa 1)
                'empresa_id' => 1, // EMPRESA DEMO SAC

                'activo' => true,
                'remember_token' => null,

                // Auditoría
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ],
            [
                // === USUARIO 2 - CONTADOR CORPORACIÓN EJEMPLO ===
                'nombre' => 'María Rodriguez Lopez',
                'email' => 'contador@corporacionejemplo.pe',
                'email_verified_at' => now(),
                'password' => Hash::make('segura456'),

                // Datos personales
                'tipo_documento' => 'DNI',
                'numero_documento' => '87654321',
                'telefono' => '966555444',

                // Multi-tenancy (relacionado con empresa 2)
                'empresa_id' => 2, // CORPORACIÓN EJEMPLO EIRL

                'activo' => true,
                'remember_token' => null,

                // Auditoría
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ]
        ]);
    }
}
