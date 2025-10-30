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

            // === CÓDIGOS Y NOMBRE ===
            $table->string('codigo', 50);
            $table->string('nombre', 150);
            $table->text('descripcion')->nullable();

            // === CLASIFICACIÓN ===
            $table->foreignId('categoria_id')
                  ->nullable()
                  ->constrained('categorias')
                  ->onDelete('set null');

            // === UNIDAD Y PRECIOS ===
            $table->string('unidad_medida', 10)->default('UNIDAD'); // UNIDAD, KG, LT, M, etc.
            $table->decimal('precio_compra', 14, 2)->default(0);
            $table->decimal('precio_venta', 14, 2)->default(0);
            $table->decimal('stock_minimo', 12, 3)->default(0);

            // === SUNAT ===
            $table->string('cod_producto_sunat', 8)->nullable(); // Código SUNAT (Catálogo 25)

            // === MULTI-TENANCY ===
            $table->foreignId('empresa_id')
                  ->constrained('empresas')
                  ->onDelete('cascade');

            // === ESTADO ===
            $table->enum('estado', ['activo', 'inactivo'])
                  ->default('activo');

            // === SOFT DELETES & TIMESTAMPS ===
            $table->softDeletes();
            $table->timestamps();

            // === ÍNDICES OPTIMIZADOS ===
            $table->index(['empresa_id', 'estado']);
            $table->index(['empresa_id', 'categoria_id']);
            $table->index(['empresa_id', 'codigo']);
            $table->index(['nombre']);
            $table->index(['cod_producto_sunat']);
            $table->index(['stock_minimo']);
            $table->index('estado');

            // === RESTRICCIONES ÚNICAS POR EMPRESA ===
            $table->unique(['empresa_id', 'codigo'], 'productos_empresa_codigo_unique');
            $table->unique(['empresa_id', 'cod_producto_sunat'], 'productos_empresa_sunat_unique');

            // === FULLTEXT PARA BÚSQUEDA (MySQL) ===
            // $table->fullText(['nombre', 'descripcion', 'codigo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};