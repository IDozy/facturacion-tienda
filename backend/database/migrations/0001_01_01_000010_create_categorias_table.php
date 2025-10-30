<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorias', function (Blueprint $table) {
            $table->id();

            // === DATOS DE LA CATEGORÍA ===
            $table->string('nombre', 100);
            $table->text('descripcion')->nullable();

            // === MULTI-TENANCY ===
            $table->foreignId('empresa_id')
                  ->constrained('empresas')
                  ->onDelete('cascade');

            // === TIMESTAMPS ===
            $table->timestamps();

            // === ÍNDICES OPTIMIZADOS ===
            $table->index(['empresa_id', 'nombre']);
            $table->index('nombre');
            $table->index('descripcion');

            // === RESTRICCIÓN ÚNICA ===
            // Una categoría con el mismo nombre por empresa
            $table->unique(['empresa_id', 'nombre'], 'categorias_empresa_nombre_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categorias');
    }
};