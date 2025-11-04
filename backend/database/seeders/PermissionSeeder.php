<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Lista de permisos iniciales
        $permissions = [
            'ver usuarios', 'crear usuarios', 'editar usuarios', 'eliminar usuarios',
            'ver productos', 'crear productos', 'editar productos', 'eliminar productos',
            'ver ventas', 'crear ventas', 'anular ventas',
            'ver compras', 'crear compras'
        ];

        foreach ($permissions as $permiso) {
            Permission::firstOrCreate(['name' => $permiso]);
        }

        // Crear rol Administrador y asignar todos los permisos
        $admin = Role::firstOrCreate(['name' => 'Administrador']);
        $admin->givePermissionTo(Permission::all());

        // Crear rol Cajero y asignar permisos específicos
        $cajero = Role::firstOrCreate(['name' => 'Cajero']);
        $cajero->givePermissionTo([
            'ver ventas', 'crear ventas', 'anular ventas',
            'ver productos'
        ]);

        // Crear rol Contador y asignar permisos específicos
        $contador = Role::firstOrCreate(['name' => 'Contador']);
        $contador->givePermissionTo([
            'ver compras', 'crear compras',
            'ver ventas'
        ]);
    }
}
