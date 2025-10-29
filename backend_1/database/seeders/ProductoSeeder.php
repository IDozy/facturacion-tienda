<?php

namespace Database\Seeders;

use App\Models\Inventario\Producto;
use Illuminate\Database\Seeder;

class ProductoSeeder extends Seeder
{
    public function run(): void
    {
        $productos = [
            [
                'empresa_id' => 1,
                'categoria_id' => 1,
                'codigo' => 'TVSAM55',
                'codigo_sunat' => '3209.10.10.00',
                'codigo_barras' => '8806092063634',
                'descripcion' => 'Televisor Samsung 55"',
                'descripcion_larga' => 'Televisor LED 55 pulgadas 4K UHD con Smart TV, resolución 3840x2160, HDR10+, 60Hz',
                'unidad_medida' => 'NIU',
                'precio_costo' => 1500.00,
                'precio_unitario' => 1800.00,
                'precio_venta' => 2124.00, // 1800 + 18% IGV
                'tipo_igv' => '10',
                'porcentaje_igv' => 18.00,
                'stock' => 15,
                'stock_minimo' => 3,
                'stock_maximo' => 50,
                'ubicacion' => 'Piso 1 - Estante A',
                'imagen' => 'tv_samsung_55.jpg',
                'activo' => true,
            ],
            [
                'empresa_id' => 1,
                'categoria_id' => 2,
                'codigo' => 'CELIP13',
                'codigo_sunat' => '8517.62.21.00',
                'codigo_barras' => '190198783919',
                'descripcion' => 'iPhone 13 128GB',
                'descripcion_larga' => 'Apple iPhone 13, 128GB, color Midnight, pantalla OLED 6.1", A15 Bionic',
                'unidad_medida' => 'NIU',
                'precio_costo' => 700.00,
                'precio_unitario' => 900.00,
                'precio_venta' => 1062.00,
                'tipo_igv' => '10',
                'porcentaje_igv' => 18.00,
                'stock' => 8,
                'stock_minimo' => 2,
                'stock_maximo' => 30,
                'ubicacion' => 'Piso 2 - Estante B',
                'imagen' => 'iphone_13.jpg',
                'activo' => true,
            ],
            [
                'empresa_id' => 1,
                'categoria_id' => 3,
                'codigo' => 'LAPHDMI1',
                'codigo_sunat' => '8544.30.20.00',
                'codigo_barras' => '7501234567890',
                'descripcion' => 'Cable HDMI 2.1 - 2 metros',
                'descripcion_larga' => 'Cable HDMI 2.1 de 2 metros, soporta 8K@60Hz, compatible con PS5, Xbox Series X',
                'unidad_medida' => 'NIU',
                'precio_costo' => 8.00,
                'precio_unitario' => 15.00,
                'precio_venta' => 17.70,
                'tipo_igv' => '10',
                'porcentaje_igv' => 18.00,
                'stock' => 120,
                'stock_minimo' => 20,
                'stock_maximo' => 500,
                'ubicacion' => 'Piso 1 - Estante C',
                'imagen' => 'cable_hdmi.jpg',
                'activo' => true,
            ],
            [
                'empresa_id' => 1,
                'categoria_id' => 1,
                'codigo' => 'TBLGAL10',
                'codigo_sunat' => '8471.30.10.00',
                'codigo_barras' => '5901234567890',
                'descripcion' => 'Tablet Samsung Galaxy Tab S7',
                'descripcion_larga' => 'Tablet Samsung Galaxy Tab S7 11", pantalla AMOLED, procesador Snapdragon 865+, 128GB',
                'unidad_medida' => 'NIU',
                'precio_costo' => 380.00,
                'precio_unitario' => 480.00,
                'precio_venta' => 566.40,
                'tipo_igv' => '10',
                'porcentaje_igv' => 18.00,
                'stock' => 5,
                'stock_minimo' => 2,
                'stock_maximo' => 20,
                'ubicacion' => 'Piso 2 - Estante D',
                'imagen' => 'tablet_samsung.jpg',
                'activo' => true,
            ],
            [
                'empresa_id' => 1,
                'categoria_id' => 3,
                'codigo' => 'AUDBRDS50',
                'codigo_sunat' => '8518.30.20.00',
                'codigo_barras' => '9999888877776',
                'descripcion' => 'Audífonos Bluetooth Sony WH-1000XM5',
                'descripcion_larga' => 'Audífonos inalámbricos con cancelación de ruido, batería 30 horas, micrófono integrado',
                'unidad_medida' => 'NIU',
                'precio_costo' => 250.00,
                'precio_unitario' => 350.00,
                'precio_venta' => 413.00,
                'tipo_igv' => '10',
                'porcentaje_igv' => 18.00,
                'stock' => 2,
                'stock_minimo' => 3,
                'stock_maximo' => 15,
                'ubicacion' => 'Piso 1 - Estante E',
                'imagen' => 'audif_sony.jpg',
                'activo' => true,
            ],
        ];

        foreach ($productos as $producto) {
            Producto::create($producto);
        }

        $this->command->info('✅ ' . count($productos) . ' productos creados exitosamente');
    }
}