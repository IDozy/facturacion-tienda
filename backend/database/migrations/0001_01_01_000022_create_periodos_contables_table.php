<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('periodos_contables', function (Blueprint $table) {
            $table->id();

            // === MULTI-TENANCY ===
            $table->foreignId('empresa_id')
                ->constrained('empresas')
                ->onDelete('cascade');

            // === PERIODO ===
            $table->unsignedTinyInteger('mes');   // 1-12
            $table->unsignedSmallInteger('año');  // 2000-2099

            // === ESTADO ===
            $table->enum('estado', ['abierto', 'cerrado'])
                ->default('abierto')
                ->index(); // ← ESTE YA CREA EL ÍNDICE

            // === FECHAS ===
            $table->date('fecha_inicio');
            $table->date('fecha_fin');

            // === CIERRE ===
            $table->foreignId('cerrado_por')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');

            $table->timestamp('cerrado_en')->nullable();

            // === TIMESTAMPS ===
            $table->timestamps();

            // === ÍNDICES OPTIMIZADOS ===
            $table->index(['empresa_id', 'año', 'mes']);
            $table->index(['empresa_id', 'estado']);
            $table->index(['empresa_id', 'fecha_inicio']);
            $table->index(['año', 'mes']);
            $table->index(['fecha_inicio', 'fecha_fin']); // ✅ corregido

            // === RESTRICCIONES ÚNICAS ===
            $table->unique(['empresa_id', 'año', 'mes'], 'periodo_empresa_anio_mes_unique');
        });

        // === CHECKS DE INTEGRIDAD ===
        DB::statement('ALTER TABLE periodos_contables ADD CONSTRAINT chk_mes CHECK (mes BETWEEN 1 AND 12)');
        DB::statement('ALTER TABLE periodos_contables ADD CONSTRAINT chk_anio CHECK (año BETWEEN 2000 AND 2100)');
        DB::statement('ALTER TABLE periodos_contables ADD CONSTRAINT chk_fechas CHECK (fecha_fin >= fecha_inicio)');
    }

    public function down(): void
    {
        Schema::dropIfExists('periodos_contables');
    }
};
