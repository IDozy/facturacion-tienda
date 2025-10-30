<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asientos', function (Blueprint $table) {
            $table->id();

            // === RELACIONES ===
            $table->foreignId('diario_id')
                  ->constrained('diarios')
                  ->onDelete('restrict');

            $table->foreignId('periodo_contable_id')
                  ->nullable()
                  ->constrained('periodos_contables')
                  ->onDelete('set null');

            $table->foreignId('comprobante_id')
                  ->nullable()
                  ->constrained('comprobantes')
                  ->onDelete('set null');

            $table->foreignId('registrado_por')
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('set null');

            // === DATOS DEL ASIENTO ===
            $table->string('numero', 20); // Ej: 00001, D-2025-001
            $table->date('fecha');
            $table->text('glosa')->nullable();

            // === TOTALES ===
            $table->decimal('total_debe', 14, 2)->default(0);
            $table->decimal('total_haber', 14, 2)->default(0);

            // === ESTADO ===
            $table->enum('estado', ['borrador', 'registrado', 'anulado'])
                  ->default('borrador');

            // === AUDITORÍA ===
            $table->timestamp('registrado_en')->nullable();

            // === TIMESTAMPS ===
            $table->timestamps();

            // === ÍNDICES OPTIMIZADOS ===
            $table->index(['diario_id', 'fecha']);
            $table->index(['periodo_contable_id', 'estado']);
            $table->index(['comprobante_id']);
            $table->index(['fecha', 'estado']);
            $table->index('numero');
            $table->index('estado');

            // === RESTRICCIONES ÚNICAS ===
            $table->unique(['diario_id', 'numero'], 'asientos_diario_numero_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asientos');
    }
};