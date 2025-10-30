<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MedioPagoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Basado en el Catálogo No. 59 de SUNAT - Medios de Pago
     */
    public function run(): void
    {
        DB::table('medios_pago')->insert([
            [
                'codigo_sunat' => '001',
                'nombre' => 'DEPÓSITO EN CUENTA',
                'descripcion' => 'Depósito directo en cuenta bancaria del proveedor',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo_sunat' => '003',
                'nombre' => 'TRANSFERENCIA DE FONDOS',
                'descripcion' => 'Transferencia bancaria entre cuentas',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo_sunat' => '004',
                'nombre' => 'ORDEN DE PAGO',
                'descripcion' => 'Orden de pago simple',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo_sunat' => '005',
                'nombre' => 'TARJETA DE DÉBITO',
                'descripcion' => 'Pago con tarjeta de débito',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo_sunat' => '006',
                'nombre' => 'TARJETA DE CRÉDITO',
                'descripcion' => 'Pago con tarjeta de crédito',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo_sunat' => '007',
                'nombre' => 'CHEQUES',
                'descripcion' => 'Pago mediante cheque bancario',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo_sunat' => '008',
                'nombre' => 'EFECTIVO',
                'descripcion' => 'Pago en efectivo (billetes y monedas)',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo_sunat' => '009',
                'nombre' => 'EFECTOS DE COMERCIO',
                'descripcion' => 'Títulos valores como letras de cambio o pagarés',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo_sunat' => '010',
                'nombre' => 'VALES',
                'descripcion' => 'Vales o vouchers',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo_sunat' => '011',
                'nombre' => 'INSTRUMENTOS FINANCIEROS',
                'descripcion' => 'Otros instrumentos financieros',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo_sunat' => '012',
                'nombre' => 'CANJE DE FACTURA NEGOCIABLE',
                'descripcion' => 'Canje por factura negociable',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo_sunat' => '013',
                'nombre' => 'YAPE',
                'descripcion' => 'Pago mediante aplicación YAPE del BCP',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo_sunat' => '014',
                'nombre' => 'PLIN',
                'descripcion' => 'Pago mediante aplicación PLIN interbancaria',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo_sunat' => '101',
                'nombre' => 'TRANSFERENCIAS – COMERCIO EXTERIOR',
                'descripcion' => 'Transferencias internacionales para comercio exterior',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo_sunat' => '102',
                'nombre' => 'CHEQUES BANCARIOS – COMERCIO EXTERIOR',
                'descripcion' => 'Cheques para operaciones de comercio exterior',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo_sunat' => '103',
                'nombre' => 'ORDEN DE PAGO SIMPLE – COMERCIO EXTERIOR',
                'descripcion' => 'Órdenes de pago para comercio exterior',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo_sunat' => '104',
                'nombre' => 'ORDEN DE PAGO DOCUMENTARIO – COMERCIO EXTERIOR',
                'descripcion' => 'Orden de pago documentario para comercio exterior',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo_sunat' => '105',
                'nombre' => 'REMESA SIMPLE – COMERCIO EXTERIOR',
                'descripcion' => 'Remesa simple para operaciones de comercio exterior',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo_sunat' => '106',
                'nombre' => 'REMESA DOCUMENTARIA – COMERCIO EXTERIOR',
                'descripcion' => 'Remesa documentaria para comercio exterior',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo_sunat' => '107',
                'nombre' => 'CARTA DE CRÉDITO SIMPLE – COMERCIO EXTERIOR',
                'descripcion' => 'Carta de crédito simple para importaciones/exportaciones',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo_sunat' => '108',
                'nombre' => 'CARTA DE CRÉDITO DOCUMENTARIO – COMERCIO EXTERIOR',
                'descripcion' => 'Carta de crédito documentario para comercio internacional',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo_sunat' => '999',
                'nombre' => 'OTROS MEDIOS DE PAGO',
                'descripcion' => 'Otros medios de pago no especificados',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}