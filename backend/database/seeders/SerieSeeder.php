<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Serie;

class SerieSeeder extends Seeder
{
    public function run(): void
    {
        $series = [
            [
                'empresa_id' => 1,
                'tipo_comprobante' => '01', // Factura
                'serie' => 'F001',
                'correlativo_actual' => 0,
                'descripcion' => 'Facturas - Serie Principal',
                'activo' => true,
                'por_defecto' => true,
            ],
            [
                'empresa_id' => 1,
                'tipo_comprobante' => '03', // Boleta
                'serie' => 'B001',
                'correlativo_actual' => 0,
                'descripcion' => 'Boletas - Serie Principal',
                'activo' => true,
                'por_defecto' => true,
            ],
            [
                'empresa_id' => 1,
                'tipo_comprobante' => '07', // Nota de Crédito
                'serie' => 'FC01',
                'correlativo_actual' => 0,
                'descripcion' => 'Notas de Crédito - Facturas',
                'activo' => true,
                'por_defecto' => true,
            ],
            [
                'empresa_id' => 1,
                'tipo_comprobante' => '07', // Nota de Crédito
                'serie' => 'BC01',
                'correlativo_actual' => 0,
                'descripcion' => 'Notas de Crédito - Boletas',
                'activo' => true,
                'por_defecto' => false,
            ],
        ];

        foreach ($series as $serie) {
            Serie::create($serie);
        }

        $this->command->info('✅ 4 series creadas');
    }
}
