<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            // === 1️⃣ Tablas base del sistema ===
            EmpresaSeeder::class,
           

             ConfiguracionSeeder::class,
            TablaSunatSeeder::class,            
            ConfiguracionEmpresaSeeder::class,

            // === 3️⃣ Usuarios, roles y permisos ===
            PermissionSeeder::class,
            UserSeeder::class,
            CajaSeeder::class,

            // === 4️⃣ Clientes y proveedores ===
            ClienteSeeder::class,
            'Database\Seeders\Compras\ProveedorSeeder',

            // === 5️⃣ Catálogos e inventario ===
            'Database\Seeders\Inventario\CategoriaSeeder',
            'Database\Seeders\Inventario\AlmacenSeeder',
            'Database\Seeders\Inventario\ProductoSeeder',
            'Database\Seeders\Inventario\AlmacenProductoSeeder',

            // === 6️⃣ Medios de pago ===
            MedioPagoSeeder::class,

            // === 7️⃣ Contabilidad (requiere empresas y usuarios) ===
            'Database\Seeders\Contabilidad\PeriodoContableSeeder',
            'Database\Seeders\Contabilidad\PlanCuentasSeeder',
            'Database\Seeders\Contabilidad\DiarioSeeder',
            'Database\Seeders\Contabilidad\AsientoSeeder',
            'Database\Seeders\Contabilidad\AsientoDetalleSeeder',

            // === 8️⃣ Facturación ===
            'Database\Seeders\Facturacion\SerieSeeder',
            'Database\Seeders\Facturacion\ComprobanteSeeder',
            'Database\Seeders\Facturacion\ComprobanteDetalleSeeder',
            'Database\Seeders\Facturacion\GuiaRemisionSeeder',

            // === 9️⃣ Compras ===
            'Database\Seeders\Compras\CompraSeeder',
            'Database\Seeders\Compras\CompraDetalleSeeder',

            // === 🔟 Movimientos e inventario ===
            'Database\Seeders\Inventario\MovimientoStockSeeder',
            'Database\Seeders\Inventario\AjusteInventarioSeeder',
            'Database\Seeders\Inventario\TransferenciaStockSeeder',

            // === 11️⃣ Pagos y retenciones ===
            PagoSeeder::class,
            RetencionSeeder::class,

            // === 12️⃣ Respuestas SUNAT y libros electrónicos ===
            RespuestaSunatSeeder::class,
            LibroElectronicoSeeder::class,

            // === 13️⃣ Auditoría (último, registra todo lo anterior) ===
            AuditoriaSeeder::class,
        ]);
    }
}
