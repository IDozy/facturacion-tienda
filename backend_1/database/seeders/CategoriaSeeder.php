<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Inventario\Categoria;

class CategoriaSeeder extends Seeder
{
    public function run(): void
    {
        // 🔹 Crea algunas categorías de ejemplo
        Categoria::create([
            'empresa_id' => 1,
            'nombre' => 'Electrónica',
            'codigo' => 'ELEC001',
            'descripcion' => 'Productos electrónicos, componentes y accesorios.',
            'imagen' => 'electro.jpg',
            'activo' => true,
        ]);

        Categoria::create([
            'empresa_id' => 1,
            'nombre' => 'Ferretería',
            'codigo' => 'FER001',
            'descripcion' => 'Herramientas, materiales y productos de ferretería.',
            'imagen' => 'ferre.jpg',
            'activo' => true,
        ]);

        Categoria::create([
            'empresa_id' => 1,
            'nombre' => 'Oficina',
            'codigo' => 'OFI001',
            'descripcion' => 'Artículos de oficina y papelería.',
            'imagen' => 'oficina.jpg',
            'activo' => true,
        ]);
    }
}
