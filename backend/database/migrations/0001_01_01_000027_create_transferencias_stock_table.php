<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transferencias_stock', function (Blueprint $table) {
            $table->id();

            // === ALMACENES ===
            $table->foreignId('almacen_origen_id')
                ->constrained('almacenes')
                ->onDelete('restrict');

            $table->foreignId('almacen_destino_id')
                ->constrained('almacenes')
                ->onDelete('restrict');

            // === USUARIO ===
            $table->foreignId('usuario_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');

            // === DATOS ===
            $table->text('observacion')->nullable();
            $table->timestamp('fecha_transferencia');

            // === ESTADO ===
            $table->enum('estado', ['pendiente', 'aplicada', 'anulada'])
                ->default('pendiente')
                ->index(); // Ya está indexado aquí

            // === TIMESTAMPS ===
            $table->timestamps();

            // === ÍNDICES OPTIMIZADOS ===
            $table->index(['almacen_origen_id', 'estado']);
            $table->index(['almacen_destino_id', 'estado']);
            $table->index(['usuario_id']);
            $table->index(['fecha_transferencia']);
            // REMOVIDO: $table->index('estado'); <-- Duplicado
        });

        // === RESTRICCIONES ===
        DB::statement('ALTER TABLE transferencias_stock ADD CONSTRAINT chk_almacenes_diferentes CHECK (almacen_origen_id != almacen_destino_id)');
    }

    public function down(): void
    {
        Schema::dropIfExists('transferencias_stock');
    }
};
