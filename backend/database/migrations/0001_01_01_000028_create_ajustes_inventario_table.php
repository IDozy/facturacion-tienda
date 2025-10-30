<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ajustes_inventario', function (Blueprint $table) {
            $table->id();

            // === ALMACÉN ===
            $table->foreignId('almacen_id')
                  ->constrained('almacenes')
                  ->onDelete('restrict');

            // === USUARIO ===
            $table->foreignId('usuario_id')
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('set null');

            // === TIPO DE AJUSTE ===
            $table->enum('tipo_ajuste', [
                'merma',
                'sobrante',
                'conteo_fisico',
                'otro'
            ])->default('otro');

            // === DATOS ===
            $table->text('observacion')->nullable();
            $table->date('fecha_ajuste');

            // === ESTADO ===
            $table->enum('estado', ['pendiente', 'aplicado', 'anulado'])
                  ->default('pendiente')
                  ->index();

            // === TIMESTAMPS ===
            $table->timestamps();

            // === ÍNDICES OPTIMIZADOS ===
            $table->index(['almacen_id', 'estado']);
            $table->index(['almacen_id', 'tipo_ajuste']);
            $table->index(['usuario_id']);
            $table->index(['fecha_ajuste']);
            $table->index(['estado', 'tipo_ajuste']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ajustes_inventario');
    }
};