<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Producto;

class ProductoSeeder extends Seeder
{
    public function run(): void
    {
        $productos = [
            [
                'codigo' => 'PROD001',
                'codigo_barras' => '7501234567890',
                'descripcion' => 'Laptop HP Pavilion 15',
                'descripcion_larga' => 'Laptop HP Pavilion 15, Intel Core i5, 8GB RAM, 256GB SSD',
                'unidad_medida' => 'NIU',
                'precio_unitario' => 2118.64, // Sin IGV
                'precio_venta' => 2500.00, // Con IGV
                'tipo_igv' => '10', // Gravado
                'porcentaje_igv' => 18.00,
                'stock' => 15,
                'stock_minimo' => 5,
                'ubicacion' => 'Piso 2 - Electrónica - Estante A',
                'categoria' => 'Computadoras',
                'activo' => true,
            ],
            [
                'codigo' => 'PROD002',
                'codigo_barras' => '7501234567891',
                'descripcion' => 'Mouse Logitech MX Master 3',
                'unidad_medida' => 'NIU',
                'precio_unitario' => 42.37,
                'precio_venta' => 50.00,
                'tipo_igv' => '10',
                'porcentaje_igv' => 18.00,
                'stock' => 50,
                'stock_minimo' => 10,
                'ubicacion' => 'Piso 1 - Accesorios - Caja 3',
                'categoria' => 'Accesorios',
                'activo' => true,
            ],
            [
                'codigo' => 'PROD003',
                'codigo_barras' => '7501234567892',
                'descripcion' => 'Teclado Mecánico Logitech G Pro',
                'unidad_medida' => 'NIU',
                'precio_unitario' => 127.12,
                'precio_venta' => 150.00,
                'tipo_igv' => '10',
                'porcentaje_igv' => 18.00,
                'stock' => 30,
                'stock_minimo' => 8,
                'ubicacion' => 'Piso 1 - Accesorios - Caja 5',
                'categoria' => 'Accesorios',
                'activo' => true,
            ],
            [
                'codigo' => 'PROD004',
                'codigo_barras' => '7501234567893',
                'descripcion' => 'Monitor LG 27" 4K UltraHD',
                'unidad_medida' => 'NIU',
                'precio_unitario' => 1186.44,
                'precio_venta' => 1400.00,
                'tipo_igv' => '10',
                'porcentaje_igv' => 18.00,
                'stock' => 10,
                'stock_minimo' => 3,
                'ubicacion' => 'Piso 2 - Monitores - Estante B',
                'categoria' => 'Monitores',
                'activo' => true,
            ],
            [
                'codigo' => 'PROD005',
                'codigo_barras' => '7501234567894',
                'descripcion' => 'Impresora HP LaserJet Pro',
                'unidad_medida' => 'NIU',
                'precio_unitario' => 847.46,
                'precio_venta' => 1000.00,
                'tipo_igv' => '10',
                'porcentaje_igv' => 18.00,
                'stock' => 8,
                'stock_minimo' => 2,
                'ubicacion' => 'Piso 3 - Impresoras',
                'categoria' => 'Impresoras',
                'activo' => true,
            ],
            [
                'codigo' => 'SERV001',
                'descripcion' => 'Servicio de Instalación de Software',
                'unidad_medida' => 'ZZ', // Servicio
                'precio_unitario' => 84.75,
                'precio_venta' => 100.00,
                'tipo_igv' => '10',
                'porcentaje_igv' => 18.00,
                'stock' => 999,
                'stock_minimo' => 0,
                'categoria' => 'Servicios',
                'activo' => true,
            ],
            [
                'codigo' => 'PROD006',
                'codigo_barras' => '7501234567895',
                'descripcion' => 'Cable HDMI 2.0 - 2 metros',
                'unidad_medida' => 'NIU',
                'precio_unitario' => 16.95,
                'precio_venta' => 20.00,
                'tipo_igv' => '10',
                'porcentaje_igv' => 18.00,
                'stock' => 100,
                'stock_minimo' => 20,
                'ubicacion' => 'Piso 1 - Cables - Caja 1',
                'categoria' => 'Cables',
                'activo' => true,
            ],
            [
                'codigo' => 'PROD007',
                'codigo_barras' => '7501234567896',
                'descripcion' => 'Disco Duro Externo 1TB Seagate',
                'unidad_medida' => 'NIU',
                'precio_unitario' => 169.49,
                'precio_venta' => 200.00,
                'tipo_igv' => '10',
                'porcentaje_igv' => 18.00,
                'stock' => 25,
                'stock_minimo' => 5,
                'ubicacion' => 'Piso 2 - Almacenamiento - Estante C',
                'categoria' => 'Almacenamiento',
                'activo' => true,
            ],
        ];

        foreach ($productos as $producto) {
            Producto::create($producto);
        }

        $this->command->info('✅ 8 productos creados');
    }
}
