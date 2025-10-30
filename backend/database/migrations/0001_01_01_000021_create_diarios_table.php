<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diarios', function (Blueprint $table) {
            $table->id();

            // === MULTI-TENANCY ===
            $table->foreignId('empresa_id')
                  ->constrained('empresas')
                  ->onDelete('cascade');

            // === CÓDIGO Y NOMBRE ===
            $table->string('codigo', 10); // DV, DC, DB, etc.
            $table->string('nombre', 100);

            // === TIPO DE DIARIO ===
            $table->enum('tipo', ['manual', 'automatico'])
                  ->default('manual');

            // === CORRELATIVO ===
            $table->string('prefijo', 10)->default(''); // DV-, DC-
            $table->unsignedInteger('correlativo_actual')->default(0);

            // === DESCRIPCIÓN Y ESTADO ===
            $table->text('descripcion')->nullable();
            $table->boolean('activo')->default(true);

            // === TIMESTAMPS ===
            $table->timestamps();

            // === ÍNDICES OPTIMIZADOS ===
            $table->index(['empresa_id', 'codigo']);
            $table->index(['empresa_id', 'tipo', 'activo']);
            $table->index(['empresa_id', 'activo']);
            $table->index('codigo');
            $table->index('tipo');

            // === RESTRICCIONES ÚNICAS ===
            $table->unique(['empresa_id', 'codigo'], 'diarios_empresa_codigo_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diarios');
    }
};