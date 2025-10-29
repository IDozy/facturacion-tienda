<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('productos', function (Blueprint $table) {
            $table->id();

            // 🔹 Relaciones principales
            $table->foreignId('empresa_id')
                ->constrained('empresas')
                ->cascadeOnDelete();

            $table->foreignId('categoria_id')
                ->nullable()
                ->constrained('categorias')
                ->nullOnDelete();

            $table->foreignId('almacen_principal_id')
                ->nullable()
                ->constrained('almacenes')
                ->nullOnDelete();

            // 🔹 Identificación
            $table->string('codigo', 50)->unique()->index(); // SKU
            $table->string('codigo_sunat', 20)->nullable();
            $table->string('codigo_barras')->nullable()->unique();

            // 🔹 Descripción
            $table->string('descripcion'); // Nombre corto
            $table->text('descripcion_larga')->nullable(); // Detalle técnico

            // 🔹 Unidad de medida
            $table->string('unidad_medida', 3)->default('NIU');

            // 🔹 Precios (con decimales adecuados)
            $table->decimal('precio_costo', 12, 2)->default(0);
            $table->decimal('precio_unitario', 12, 2)->default(0); // Sin IGV
            $table->decimal('precio_venta', 12, 2)->default(0); // Con IGV

            // 🔹 IGV
            $table->string('tipo_igv', 2)->default('10');
            $table->decimal('porcentaje_igv', 5, 2)->default(18.00);

            // 🔹 Inventario
            $table->decimal('stock', 12, 2)->default(0);
            $table->decimal('stock_minimo', 12, 2)->default(0);
            $table->decimal('stock_maximo', 12, 2)->nullable();

            // 🔹 Ubicación y almacén
            $table->string('ubicacion')->nullable(); // Ej: "Piso 2 - Estante B"
            $table->json('stock_por_almacen')->nullable(); // Control por almacén

            // 🔹 Imagen
            $table->string('imagen')->nullable();

            // 🔹 Estado
            $table->boolean('activo')->default(true)->index();

            // 🔹 Auditoría
            $table->timestamp('fecha_ingreso')->nullable();
            $table->timestamp('fecha_actualizacion_stock')->nullable();
            $table->softDeletes();
            $table->timestamps();

            // 🔹 Índices para búsquedas frecuentes
            $table->index('empresa_id');
            $table->index('categoria_id');
            $table->index('almacen_principal_id');
            $table->index(['empresa_id', 'activo']); // Composite index
            $table->index('codigo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};