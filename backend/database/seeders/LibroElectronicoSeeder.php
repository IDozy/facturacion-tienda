<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LibroElectronicoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener periodos contables disponibles
        $periodos = DB::table('periodos_contables')->take(2)->get();
        
        if ($periodos->isEmpty()) {
            $this->command->error('No hay periodos contables registrados. Por favor, ejecuta primero el seeder de periodos contables.');
            return;
        }

        $now = Carbon::now();

        $libros = [
            [
                'periodo_contable_id' => $periodos[0]->id,
                'tipo_libro' => '080100', // Libro de Compras
                'archivo_txt' => 'LE20123456789202410080100001111.txt',
                'hash_archivo' => sha1('contenido_archivo_compras'),
                'estado' => 'enviado',
                'fecha_generacion' => $now->copy()->subDays(5),
                'fecha_envio_sunat' => $now->copy()->subDays(3),
                'motivo_rechazo' => null,
                'created_at' => $now->copy()->subDays(5),
                'updated_at' => $now->copy()->subDays(3),
            ],
            [
                'periodo_contable_id' => isset($periodos[1]) ? $periodos[1]->id : $periodos[0]->id,
                'tipo_libro' => '140100', // Libro de Ventas e Ingresos
                'archivo_txt' => 'LE20123456789202411140100001111.txt',
                'hash_archivo' => sha1('contenido_archivo_ventas'),
                'estado' => 'generado',
                'fecha_generacion' => $now,
                'fecha_envio_sunat' => null,
                'motivo_rechazo' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($libros as $libro) {
            // Verificar que no exista ya un libro para ese periodo y tipo
            $existe = DB::table('libros_electronicos')
                ->where('periodo_contable_id', $libro['periodo_contable_id'])
                ->where('tipo_libro', $libro['tipo_libro'])
                ->exists();

            if (!$existe) {
                DB::table('libros_electronicos')->insert($libro);
            }
        }

        $this->command->info('Libros electrónicos creados exitosamente.');
    }
}