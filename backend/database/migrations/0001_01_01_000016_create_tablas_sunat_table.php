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
        Schema::create('tablas_sunat', function (Blueprint $table) {
            $table->id();

            // === CÓDIGO SUNAT (ej: 6 para DNI, 10 para afectación IGV) ===
            $table->string('codigo', 10);

            // === DESCRIPCIÓN ===
            $table->string('descripcion', 150);

            // === TIPO DE TABLA (tipo_documento, unidad_medida, etc.) ===
            $table->string('tipo_tabla', 50);

            // === ESTADO ===
            $table->boolean('activo')->default(true);

            // === AUDITORÍA ===
            $table->timestamps();

            // === ÍNDICES OPTOMIZADOS ===
            $table->index('tipo_tabla');
            $table->index('activo');
            $table->index('codigo');

            // Búsqueda por código + tipo (más común)
            $table->index(['tipo_tabla', 'codigo']);

            // Búsqueda por descripción (para scopeSearch)
            $table->index('descripcion');

            // === UNICIDAD: código único por tipo de tabla ===
            $table->unique(['tipo_tabla', 'codigo'], 'tablas_sunat_tipo_codigo_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tablas_sunat');
    }
};