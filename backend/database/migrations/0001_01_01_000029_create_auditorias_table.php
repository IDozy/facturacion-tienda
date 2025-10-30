<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auditorias', function (Blueprint $table) {
            $table->id();

            // === USUARIO ===
            $table->foreignId('usuario_id')
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('set null');

            // === TABLA Y REGISTRO ===
            $table->string('tabla', 100);
            $table->unsignedBigInteger('registro_id');

            // === ACCIÓN ===
            $table->enum('accion', ['create', 'update', 'delete'])
                  ->index(); // Ya está indexado aquí

            // === VALORES (JSON) ===
            $table->json('valores_anteriores')->nullable();
            $table->json('valores_nuevos')->nullable();

            // === AUDITORÍA ===
            $table->ipAddress('ip');
            $table->timestamp('created_at')->useCurrent();

            // === ÍNDICES OPTIMIZADOS ===
            $table->index(['tabla', 'registro_id']);
            $table->index(['usuario_id']);
            // REMOVIDO: $table->index(['accion']); <-- Duplicado
            $table->index(['created_at']);
            $table->index('ip');

            // === ÍNDICE COMPUESTO PARA BÚSQUEDA RÁPIDA ===
            $table->index(['tabla', 'accion', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auditorias');
    }
};