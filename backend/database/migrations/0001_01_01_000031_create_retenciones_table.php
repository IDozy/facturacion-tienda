<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retenciones', function (Blueprint $table) {
            $table->id();

            // === COMPROBANTE ===
            $table->foreignId('comprobante_id')
                  ->constrained('comprobantes')
                  ->onDelete('cascade');

            // === TIPO ===
            $table->enum('tipo', ['retencion', 'percepcion'])
                  ->index(); // Ya está indexado aquí

            // === CÁLCULO ===
            $table->decimal('monto', 14, 2)->default(0);
            $table->decimal('porcentaje', 5, 2)->nullable(); // 0.00 - 99.99

            // === ESTADO ===
            $table->enum('estado', ['pendiente', 'aplicada', 'anulada'])
                  ->default('pendiente')
                  ->index(); // Ya está indexado aquí

            // === TIMESTAMPS ===
            $table->timestamps();

            // === ÍNDICES OPTIMIZADOS ===
            $table->index(['comprobante_id', 'tipo']);
            $table->index(['comprobante_id', 'estado']);
            $table->index(['tipo', 'estado']);
            // REMOVIDO: $table->index('estado'); <-- Duplicado
        });

        // === RESTRICCIONES ===
        DB::statement('ALTER TABLE retenciones ADD CONSTRAINT chk_monto_nonnegativo CHECK (monto >= 0)');
        DB::statement('ALTER TABLE retenciones ADD CONSTRAINT chk_porcentaje_rango CHECK (porcentaje IS NULL OR (porcentaje >= 0 AND porcentaje <= 100))');
    }

    public function down(): void
    {
        Schema::dropIfExists('retenciones');
    }
};