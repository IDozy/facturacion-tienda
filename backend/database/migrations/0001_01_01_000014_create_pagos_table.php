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
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();

            // === RELACIONES ===
            $table->foreignId('comprobante_id')
                  ->constrained('comprobantes')
                  ->onDelete('cascade');

            $table->foreignId('medio_pago_id')
                  ->nullable()
                  ->constrained('medios_pago')
                  ->onDelete('set null');

            $table->foreignId('caja_id')
                  ->nullable()
                  ->constrained('cajas')
                  ->onDelete('set null');

            // === DATOS DEL PAGO ===
            $table->decimal('monto', 12, 2);
            $table->date('fecha_pago');
            $table->string('numero_referencia', 50)->nullable();

            // === ESTADO ===
            $table->enum('estado', ['pendiente', 'confirmado', 'anulado'])
                  ->default('pendiente');

            $table->timestamp('fecha_confirmacion')->nullable();
            $table->unsignedInteger('cuota_numero')->nullable();

            // === AUDITORÍA ===
            $table->timestamps();

            // === ÍNDICES OPTOMIZADOS ===
            $table->index('comprobante_id');
            $table->index('medio_pago_id');
            $table->index('caja_id');
            $table->index('estado');
            $table->index('fecha_pago');
            $table->index('fecha_confirmacion');

            // Búsqueda por período
            $table->index(['fecha_pago', 'estado']);

            // Para reportes: pagos confirmados por caja
            $table->index(['caja_id', 'estado']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};