<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Cliente;

class ClienteSeeder extends Seeder
{
    public function run(): void
    {
        $clientes = [
            // Cliente con RUC (Empresa)
            [
                'tipo_documento' => '6',
                'numero_documento' => '20987654321',
                'nombre_razon_social' => 'CORPORACION EJEMPLO S.A.C.',
                'nombre_comercial' => 'Corp Ejemplo',
                'direccion' => 'Jr. Comercio 456',
                'distrito' => 'Miraflores',
                'provincia' => 'Lima',
                'departamento' => 'Lima',
                'ubigeo' => '150122',
                'telefono' => '945678912',
                'email' => 'contacto@corpejemplo.com',
                'activo' => true,
            ],
            // Cliente con DNI (Persona Natural)
            [
                'tipo_documento' => '1',
                'numero_documento' => '12345678',
                'nombre_razon_social' => 'JUAN PEREZ GARCIA',
                'direccion' => 'Av. Ejemplo 789',
                'distrito' => 'San Isidro',
                'provincia' => 'Lima',
                'departamento' => 'Lima',
                'telefono' => '912345678',
                'email' => 'juan.perez@email.com',
                'activo' => true,
            ],
            [
                'tipo_documento' => '1',
                'numero_documento' => '87654321',
                'nombre_razon_social' => 'MARIA LOPEZ FERNANDEZ',
                'direccion' => 'Calle Las Flores 321',
                'distrito' => 'Surco',
                'provincia' => 'Lima',
                'departamento' => 'Lima',
                'telefono' => '923456789',
                'email' => 'maria.lopez@email.com',
                'activo' => true,
            ],
            [
                'tipo_documento' => '6',
                'numero_documento' => '20111222333',
                'nombre_razon_social' => 'INVERSIONES XYZ E.I.R.L.',
                'nombre_comercial' => 'Inversiones XYZ',
                'direccion' => 'Av. Negocios 555',
                'distrito' => 'San Borja',
                'provincia' => 'Lima',
                'departamento' => 'Lima',
                'telefono' => '934567890',
                'email' => 'info@inversionesxyz.com',
                'activo' => true,
            ],
            [
                'tipo_documento' => '1',
                'numero_documento' => '45678912',
                'nombre_razon_social' => 'CARLOS RODRIGUEZ SOTO',
                'direccion' => 'Jr. Los Olivos 147',
                'distrito' => 'La Molina',
                'provincia' => 'Lima',
                'departamento' => 'Lima',
                'telefono' => '945678123',
                'email' => 'carlos.rodriguez@email.com',
                'activo' => true,
            ],
        ];

        foreach ($clientes as $cliente) {
            Cliente::create($cliente);
        }

        $this->command->info('✅ 5 clientes creados');
    }
}
