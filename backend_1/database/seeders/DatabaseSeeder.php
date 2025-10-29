<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Crear usuario administrador para el sistema
        User::create([
            'name' => 'Administrador',
            'email' => 'admin@miempresa.com',
            'password' => bcrypt('password123'), // Cambiar en producción
            'email_verified_at' => now(),
        ]);

        $this->command->info('✅ Usuario administrador creado');

        // Ejecutar los seeders del sistema de facturación
        $this->call([
            EmpresaSeeder::class,
            ClienteSeeder::class,
            CategoriaSeeder::class,
            ProductoSeeder::class,
            SerieSeeder::class,
        ]);

        $this->command->info('🎉 Base de datos poblada correctamente');
        $this->command->info('📧 Email: admin@miempresa.com');
        $this->command->info('🔑 Password: password123');
    }
}
