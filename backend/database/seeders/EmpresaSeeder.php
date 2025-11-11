<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class EmpresaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('empresas')->insert([
            [
                // === EMPRESA 1 - MODO PRUEBA ===
                'razon_social' => 'EMPRESA DEMO SAC',
                'ruc' => '20123456789',
                'direccion' => 'Av. Los Empresarios 123, Lima, Perú',
                'telefono' => '01-1234567',
                'email' => 'demo@empresademo.com',
                'logo' => null,
                
                // Certificado digital (en producción deberías encriptar esto)
                'certificado_digital' => null, // Se agregará cuando tengas el archivo .pfx
                'clave_certificado' => null,
                
                // Credenciales SOL (en producción deberías encriptar esto)
                'usuario_sol' => 'MODDATOS',
                'clave_sol' => 'MODDATOS',
                
                // Configuración
                'modo' => 'prueba',
                'fecha_expiracion_certificado' => Carbon::now()->addYear(),
                'pse_autorizado' => false,
                
                // Auditoría
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                // === EMPRESA 2 - MODO PRODUCCIÓN ===
                'razon_social' => 'CORPORACIÓN EJEMPLO EIRL',
                'ruc' => '20987654321',
                'direccion' => 'Jr. Comercio 456, Miraflores, Lima',
                'telefono' => '01-9876543',
                'email' => 'facturacion@corporacionejemplo.pe',
                'logo' => null,
                
                // Certificado digital (en producción deberías encriptar esto)
                'certificado_digital' => null, // Se agregará cuando tengas el archivo .pfx
                'clave_certificado' => null,
                
                // Credenciales SOL (en producción deberías encriptar esto)
                'usuario_sol' => 'USUARIOSOL',
                'clave_sol' => 'ClaveSol123',
                
                // Configuración
                'modo' => 'produccion',
                'fecha_expiracion_certificado' => Carbon::now()->addMonths(6),
                'pse_autorizado' => true,
                
                // Auditoría
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}