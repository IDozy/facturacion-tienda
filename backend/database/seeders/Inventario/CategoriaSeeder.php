<?php

namespace Database\Seeders\Inventario;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoriaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('categorias')->insert([
            // === CATEGORÍAS PARA EMPRESA 1 (EMPRESA DEMO SAC) ===
            [
                'nombre' => 'Electrónicos',
                'descripcion' => 'Productos electrónicos y tecnología: laptops, celulares, tablets, accesorios',
                'empresa_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'Oficina',
                'descripcion' => 'Suministros y materiales de oficina: papel, lapiceros, archivadores, etc.',
                'empresa_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'Servicios',
                'descripcion' => 'Servicios profesionales y técnicos: consultoría, mantenimiento, soporte',
                'empresa_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'Software',
                'descripcion' => 'Licencias de software, aplicaciones y sistemas',
                'empresa_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // === CATEGORÍAS PARA EMPRESA 2 (CORPORACIÓN EJEMPLO EIRL) ===
            [
                'nombre' => 'Alimentos y Bebidas',
                'descripcion' => 'Productos alimenticios, bebidas, snacks y productos perecibles',
                'empresa_id' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'Limpieza',
                'descripcion' => 'Productos de limpieza industrial y doméstica',
                'empresa_id' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'Embalaje',
                'descripcion' => 'Materiales de empaque: cajas, bolsas, cintas, etiquetas',
                'empresa_id' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'Herramientas',
                'descripcion' => 'Herramientas manuales y eléctricas para uso industrial',
                'empresa_id' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'Seguridad Industrial',
                'descripcion' => 'Equipos de protección personal: cascos, guantes, lentes, botas',
                'empresa_id' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}