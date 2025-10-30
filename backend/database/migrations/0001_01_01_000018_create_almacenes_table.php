<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('almacenes', function (Blueprint $table) {
            $table->id();

            // === DATOS DEL ALMACÉN ===
            $table->string('nombre', 100);
            $table->string('ubicacion', 150)->nullable();

            // === MULTI-TENANCY ===
            $table->foreignId('empresa_id')
                  ->constrained('empresas')
                  ->onDelete('cascade');

            // === ESTADO ===
            $table->boolean('activo')->default(true);

            // === TIMESTAMPS ===
            $table->timestamps();

            // === ÍNDICES ===
            $table->index(['empresa_id', 'activo']);
            $table->index('nombre');
            $table->index('ubicacion');

            // === RESTRICCIÓN ÚNICA ===
            // Un almacén con el mismo nombre por empresa
            $table->unique(['empresa_id', 'nombre'], 'almacenes_empresa_nombre_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('almacenes');
    }
};