<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('productos')->insert([
            // === PRODUCTOS PARA EMPRESA 1 (EMPRESA DEMO SAC) ===
            
            // Categoría: Electrónicos (ID: 1)
            [
                'codigo' => 'PROD-001',
                'nombre' => 'Laptop HP ProBook 450 G8',
                'descripcion' => 'Laptop empresarial Core i5, 8GB RAM, 256GB SSD, Windows 11 Pro',
                'categoria_id' => 1,
                'unidad_medida' => 'UNIDAD',
                'precio_compra' => 2500.00,
                'precio_venta' => 3200.00,
                'stock_minimo' => 5.000,
                'cod_producto_sunat' => '43211503',
                'empresa_id' => 1,
                'estado' => 'activo',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo' => 'PROD-002',
                'nombre' => 'Mouse Inalámbrico Logitech MX Master 3',
                'descripcion' => 'Mouse ergonómico profesional con conectividad Bluetooth y USB',
                'categoria_id' => 1,
                'unidad_medida' => 'UNIDAD',
                'precio_compra' => 250.00,
                'precio_venta' => 380.00,
                'stock_minimo' => 10.000,
                'cod_producto_sunat' => '43211708',
                'empresa_id' => 1,
                'estado' => 'activo',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Categoría: Oficina (ID: 2)
            [
                'codigo' => 'PROD-003',
                'nombre' => 'Papel Bond A4 75gr (Paquete x 500)',
                'descripcion' => 'Papel bond tamaño A4 de 75 gramos, paquete de 500 hojas',
                'categoria_id' => 2,
                'unidad_medida' => 'PAQUETE',
                'precio_compra' => 12.00,
                'precio_venta' => 18.00,
                'stock_minimo' => 50.000,
                'cod_producto_sunat' => '14111507',
                'empresa_id' => 1,
                'estado' => 'activo',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Categoría: Servicios (ID: 3)
            [
                'codigo' => 'SERV-001',
                'nombre' => 'Soporte Técnico Mensual',
                'descripcion' => 'Servicio de soporte técnico remoto y presencial, incluye mantenimiento preventivo',
                'categoria_id' => 3,
                'unidad_medida' => 'SERVICIO',
                'precio_compra' => 0.00,
                'precio_venta' => 500.00,
                'stock_minimo' => 0.000,
                'cod_producto_sunat' => '81112501',
                'empresa_id' => 1,
                'estado' => 'activo',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Categoría: Software (ID: 4)
            [
                'codigo' => 'SOFT-001',
                'nombre' => 'Licencia Microsoft Office 365 Business',
                'descripcion' => 'Licencia anual de Office 365 para empresas, incluye Word, Excel, PowerPoint',
                'categoria_id' => 4,
                'unidad_medida' => 'LICENCIA',
                'precio_compra' => 280.00,
                'precio_venta' => 420.00,
                'stock_minimo' => 0.000,
                'cod_producto_sunat' => '43231513',
                'empresa_id' => 1,
                'estado' => 'activo',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // === PRODUCTOS PARA EMPRESA 2 (CORPORACIÓN EJEMPLO EIRL) ===
            
            // Categoría: Alimentos y Bebidas (ID: 5)
            [
                'codigo' => 'ALI-001',
                'nombre' => 'Agua Mineral Sin Gas 625ml x 15 unidades',
                'descripcion' => 'Paquete de agua mineral sin gas, botella de 625ml, caja por 15 unidades',
                'categoria_id' => 5,
                'unidad_medida' => 'CAJA',
                'precio_compra' => 8.50,
                'precio_venta' => 15.00,
                'stock_minimo' => 100.000,
                'cod_producto_sunat' => '50202301',
                'empresa_id' => 2,
                'estado' => 'activo',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo' => 'ALI-002',
                'nombre' => 'Galletas Soda Field Pack x 6',
                'descripcion' => 'Pack de galletas soda, 6 paquetes de 34g cada uno',
                'categoria_id' => 5,
                'unidad_medida' => 'PACK',
                'precio_compra' => 2.80,
                'precio_venta' => 4.50,
                'stock_minimo' => 200.000,
                'cod_producto_sunat' => '50181701',
                'empresa_id' => 2,
                'estado' => 'activo',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Categoría: Limpieza (ID: 6)
            [
                'codigo' => 'LIMP-001',
                'nombre' => 'Lejía Clorox 4L',
                'descripcion' => 'Lejía desinfectante concentrada, presentación de 4 litros',
                'categoria_id' => 6,
                'unidad_medida' => 'GALON',
                'precio_compra' => 12.00,
                'precio_venta' => 18.50,
                'stock_minimo' => 50.000,
                'cod_producto_sunat' => '47131805',
                'empresa_id' => 2,
                'estado' => 'activo',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Categoría: Embalaje (ID: 7)
            [
                'codigo' => 'EMB-001',
                'nombre' => 'Caja de Cartón 60x40x40 cm',
                'descripcion' => 'Caja de cartón corrugado resistente, medidas 60x40x40 cm',
                'categoria_id' => 7,
                'unidad_medida' => 'UNIDAD',
                'precio_compra' => 3.50,
                'precio_venta' => 6.00,
                'stock_minimo' => 100.000,
                'cod_producto_sunat' => '24121503',
                'empresa_id' => 2,
                'estado' => 'activo',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Categoría: Herramientas (ID: 8)
            [
                'codigo' => 'HERR-001',
                'nombre' => 'Taladro Percutor Bosch GSB 550 RE',
                'descripcion' => 'Taladro percutor 550W con maletín y kit de brocas',
                'categoria_id' => 8,
                'unidad_medida' => 'UNIDAD',
                'precio_compra' => 180.00,
                'precio_venta' => 290.00,
                'stock_minimo' => 5.000,
                'cod_producto_sunat' => '27111701',
                'empresa_id' => 2,
                'estado' => 'activo',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Categoría: Seguridad Industrial (ID: 9)
            [
                'codigo' => 'SEG-001',
                'nombre' => 'Casco de Seguridad Tipo I Clase E',
                'descripcion' => 'Casco de seguridad industrial con protección eléctrica, color amarillo',
                'categoria_id' => 9,
                'unidad_medida' => 'UNIDAD',
                'precio_compra' => 25.00,
                'precio_venta' => 45.00,
                'stock_minimo' => 20.000,
                'cod_producto_sunat' => '46181501',
                'empresa_id' => 2,
                'estado' => 'activo',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Producto sin categoría (ejemplo)
            [
                'codigo' => 'MISC-001',
                'nombre' => 'Servicio de Transporte Local',
                'descripcion' => 'Servicio de transporte de mercadería dentro de la ciudad',
                'categoria_id' => null,
                'unidad_medida' => 'SERVICIO',
                'precio_compra' => 0.00,
                'precio_venta' => 150.00,
                'stock_minimo' => 0.000,
                'cod_producto_sunat' => '78102200',
                'empresa_id' => 2,
                'estado' => 'activo',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}