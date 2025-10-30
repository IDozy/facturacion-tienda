<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ComprobanteDetalleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('comprobante_detalles')->insert([
            // === DETALLES PARA COMPROBANTE 1 (Factura F001-00126) ===
            [
                'comprobante_id' => 1,
                'producto_id' => 2, // Mouse Inalámbrico Logitech MX Master 3
                'cantidad' => 2.000,
                'precio_unitario' => 380.00,
                'tipo_afectacion' => 'gravado',
                'subtotal' => 760.00,
                'igv' => 136.80,
                'total' => 896.80,
                'descuento_monto' => 0.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'comprobante_id' => 1,
                'producto_id' => 3, // Papel Bond A4 75gr
                'cantidad' => 10.000,
                'precio_unitario' => 18.00,
                'tipo_afectacion' => 'gravado',
                'subtotal' => 180.00,
                'igv' => 32.40,
                'total' => 212.40,
                'descuento_monto' => 0.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'comprobante_id' => 1,
                'producto_id' => 4, // Soporte Técnico Mensual
                'cantidad' => 1.000,
                'precio_unitario' => 90.00, // Con descuento aplicado
                'tipo_afectacion' => 'gravado',
                'subtotal' => 60.00, // 90 - 30 de descuento
                'igv' => 10.80,
                'total' => 70.80,
                'descuento_monto' => 20.00, // Descuento aplicado
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // === DETALLES PARA COMPROBANTE 2 (Boleta B002-03457) ===
            [
                'comprobante_id' => 2,
                'producto_id' => 6, // Agua Mineral Sin Gas
                'cantidad' => 5.000,
                'precio_unitario' => 15.00,
                'tipo_afectacion' => 'gravado',
                'subtotal' => 75.00,
                'igv' => 13.50,
                'total' => 88.50,
                'descuento_monto' => 0.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'comprobante_id' => 2,
                'producto_id' => 7, // Galletas Soda Field Pack
                'cantidad' => 20.000,
                'precio_unitario' => 4.50,
                'tipo_afectacion' => 'gravado',
                'subtotal' => 90.00,
                'igv' => 16.20,
                'total' => 106.20,
                'descuento_monto' => 0.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'comprobante_id' => 2,
                'producto_id' => 8, // Lejía Clorox 4L
                'cantidad' => 8.000,
                'precio_unitario' => 18.50,
                'tipo_afectacion' => 'gravado',
                'subtotal' => 135.00,
                'igv' => 24.30,
                'total' => 159.30,
                'descuento_monto' => 0.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // === DETALLES PARA COMPROBANTE 3 (Nota de Crédito FC01-00016) ===
            [
                'comprobante_id' => 3,
                'producto_id' => 2, // Mouse Inalámbrico (devolución parcial)
                'cantidad' => 1.000,
                'precio_unitario' => 380.00,
                'tipo_afectacion' => 'gravado',
                'subtotal' => 200.00,
                'igv' => 36.00,
                'total' => 236.00,
                'descuento_monto' => 0.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // === DETALLES PARA COMPROBANTE 4 (Factura F002-01891 en USD) ===
            [
                'comprobante_id' => 4,
                'producto_id' => 9, // Caja de Cartón
                'cantidad' => 200.000,
                'precio_unitario' => 6.00,
                'tipo_afectacion' => 'gravado',
                'subtotal' => 1200.00,
                'igv' => 216.00,
                'total' => 1416.00,
                'descuento_monto' => 50.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'comprobante_id' => 4,
                'producto_id' => 10, // Taladro Percutor Bosch
                'cantidad' => 3.000,
                'precio_unitario' => 290.00,
                'tipo_afectacion' => 'gravado',
                'subtotal' => 800.00,
                'igv' => 144.00,
                'total' => 944.00,
                'descuento_monto' => 50.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}