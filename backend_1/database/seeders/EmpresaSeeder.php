<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Empresa;

class EmpresaSeeder extends Seeder
{
    public function run(): void
    {
        Empresa::create([
            'ruc' => '20123456789',
            'razon_social' => 'MI EMPRESA DEMO S.A.C.',
            'nombre_comercial' => 'Tienda Demo',
            'direccion' => 'Av. Los Negocios 123',
            'urbanizacion' => 'Urbanización Central',
            'distrito' => 'Lima',
            'provincia' => 'Lima',
            'departamento' => 'Lima',
            'ubigeo' => '150101',
            'codigo_pais' => 'PE',
            'telefono' => '987654321',
            'email' => 'contacto@miempresa.com',
            'web' => 'https://miempresa.com',
            'usuario_sol' => 'MODDATOS',
            'clave_sol' => 'MODDATOS',
            'modo_prueba' => true, // En modo prueba (beta SUNAT)
            'activo' => true,
        ]);

        $this->command->info('✅ Empresa creada');
    }
}
