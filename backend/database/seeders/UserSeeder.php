<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Roles
        $adminRole = Role::firstOrCreate(['name' => 'Administrador']);
        $contadorRole = Role::firstOrCreate(['name' => 'contador']);
        $cajeroRole = Role::firstOrCreate(['name' => 'cajero']); // si quieres agregar cajero

        // Usuario Admin
        $admin = User::updateOrCreate(
            ['numero_documento' => '12345678', 'empresa_id' => 1],
            [
                'nombre' => 'Juan Pérez García',
                'email' => 'admin@empresademo.com',
                'password' => Hash::make('password123'),
                'tipo_documento' => 'DNI',
                'telefono' => '999888777',
                'activo' => true,
            ]
        );
        $admin->assignRole($adminRole);

        // Usuario Contador
        $contador = User::updateOrCreate(
            ['numero_documento' => '87654321', 'empresa_id' => 2],
            [
                'nombre' => 'María Rodriguez Lopez',
                'email' => 'contador@corporacionejemplo.pe',
                'password' => Hash::make('segura456'),
                'tipo_documento' => 'DNI',
                'telefono' => '966555444',
                'activo' => true,
            ]
        );
        $contador->assignRole($contadorRole);

        // Usuario Cajero (ejemplo)
        $cajero = User::updateOrCreate(
            ['numero_documento' => '11223344', 'empresa_id' => 1],
            [
                'nombre' => 'Carlos Gómez',
                'email' => 'cajero@empresademo.com',
                'password' => Hash::make('cajero123'),
                'tipo_documento' => 'DNI',
                'telefono' => '955667788',
                'activo' => true,
            ]
        );
        $cajero->assignRole($cajeroRole);
    }
}
