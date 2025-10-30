<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('configuraciones', function (Blueprint $table) {
            $table->id();

            // === CLAVE DE CONFIGURACIÓN ===
            $table->string('clave', 100);

            // === VALOR (almacenado como JSON) ===
            $table->json('valor');

            // === TIPO (texto, numero, booleano, array, etc.) ===
            $table->string('tipo', 50)->default('texto');

            // === MULTI-TENANCY ===
            $table->foreignId('empresa_id')
                  ->nullable()
                  ->constrained('empresas')
                  ->onDelete('cascade');

            // === AUDITORÍA ===
            $table->timestamps();

            // === ÍNDICES OPTOMIZADOS ===
            $table->index('clave');
            $table->index('empresa_id');
            $table->index(['empresa_id', 'clave']); // Búsqueda más común

            // === UNICIDAD: una clave por empresa (o global si empresa_id es null) ===
            $table->unique(['empresa_id', 'clave'], 'configuraciones_empresa_clave_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('configuraciones');
    }
};