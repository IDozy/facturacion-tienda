<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ConfiguracionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('configuraciones')->insert([
            // === CONFIGURACIONES GLOBALES (SIN EMPRESA) ===
            [
                'clave' => 'sistema.version',
                'valor' => json_encode('1.0.0'),
                'tipo' => 'texto',
                'empresa_id' => null, // Global
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'clave' => 'sistema.mantenimiento',
                'valor' => json_encode(false),
                'tipo' => 'booleano',
                'empresa_id' => null, // Global
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // === CONFIGURACIONES EMPRESA 1 (DEMO SAC) ===
            [
                'clave' => 'facturacion.serie_boleta',
                'valor' => json_encode('B001'),
                'tipo' => 'texto',
                'empresa_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'clave' => 'facturacion.serie_factura',
                'valor' => json_encode('F001'),
                'tipo' => 'texto',
                'empresa_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'clave' => 'facturacion.igv',
                'valor' => json_encode(18),
                'tipo' => 'numero',
                'empresa_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'clave' => 'facturacion.envio_automatico',
                'valor' => json_encode(true),
                'tipo' => 'booleano',
                'empresa_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'clave' => 'notificaciones.emails_copia',
                'valor' => json_encode(['contabilidad@empresademo.com', 'gerencia@empresademo.com']),
                'tipo' => 'array',
                'empresa_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // === CONFIGURACIONES EMPRESA 2 (CORPORACIÓN EJEMPLO) ===
            [
                'clave' => 'facturacion.serie_boleta',
                'valor' => json_encode('B002'),
                'tipo' => 'texto',
                'empresa_id' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'clave' => 'facturacion.serie_factura',
                'valor' => json_encode('F002'),
                'tipo' => 'texto',
                'empresa_id' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'clave' => 'facturacion.igv',
                'valor' => json_encode(18),
                'tipo' => 'numero',
                'empresa_id' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'clave' => 'facturacion.envio_automatico',
                'valor' => json_encode(false),
                'tipo' => 'booleano',
                'empresa_id' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'clave' => 'facturacion.contingencia',
                'valor' => json_encode([
                    'activo' => false,
                    'serie' => 'C001',
                    'ultimo_numero' => 0
                ]),
                'tipo' => 'objeto',
                'empresa_id' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'clave' => 'impresion.formato_ticket',
                'valor' => json_encode([
                    'ancho' => 80,
                    'margen_superior' => 10,
                    'margen_inferior' => 10,
                    'incluir_qr' => true
                ]),
                'tipo' => 'objeto',
                'empresa_id' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}