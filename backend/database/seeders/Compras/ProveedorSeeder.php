<?php

namespace Database\Seeders\Compras;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProveedorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $empresaIds = DB::table('empresas')->pluck('id')->toArray();
        
        if (empty($empresaIds)) {
            $this->command->error('No hay empresas registradas. Por favor, ejecuta primero el seeder de empresas.');
            return;
        }

        $proveedores = [
            [
                'tipo_documento' => 'RUC',
                'numero_documento' => '20100130204',
                'razon_social' => 'BANCO DE CREDITO DEL PERU',
                'direccion' => 'Calle Centenario 156, La Molina, Lima',
                'telefono' => '013111000',
                'email' => 'proveedores@bcp.com.pe',
                'estado' => 'activo',
            ],
            [
                'tipo_documento' => 'RUC',
                'numero_documento' => '20100035392',
                'razon_social' => 'TELEFONICA DEL PERU S.A.A.',
                'direccion' => 'Av. Arequipa 1155, Santa Beatriz, Lima',
                'telefono' => '012105000',
                'email' => 'proveedores@telefonica.com.pe',
                'estado' => 'activo',
            ],
        ];

        $now = Carbon::now();

        foreach ($empresaIds as $empresaId) {
            foreach ($proveedores as $proveedor) {
                DB::table('proveedores')->insert([
                    'tipo_documento' => $proveedor['tipo_documento'],
                    'numero_documento' => $proveedor['numero_documento'],
                    'razon_social' => $proveedor['razon_social'],
                    'direccion' => $proveedor['direccion'],
                    'telefono' => $proveedor['telefono'],
                    'email' => $proveedor['email'],
                    'empresa_id' => $empresaId,
                    'estado' => $proveedor['estado'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $this->command->info('Proveedores creados exitosamente.');
    }
}