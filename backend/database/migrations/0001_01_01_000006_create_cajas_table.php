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
        Schema::create('cajas', function (Blueprint $table) {
            $table->id();

            // === RELACIÓN CON USUARIO ===
            $table->foreignId('usuario_id')
                  ->constrained('users')
                  ->onDelete('cascade');

            // === MONTOS ===
            $table->decimal('monto_inicial', 12, 2)->default(0);
            $table->decimal('monto_final', 12, 2)->nullable();
            $table->decimal('total_esperado', 12, 2)->nullable();
            $table->decimal('diferencia_cuadratura', 12, 2)->default(0);

            // === CONFIGURACIÓN ===
            $table->enum('moneda', ['PEN', 'USD', 'EUR'])->default('PEN');
            $table->enum('estado', ['abierta', 'cerrada'])->default('abierta');

            // === FECHAS ===
            $table->timestamp('apertura')->useCurrent();
            $table->timestamp('cierre')->nullable();

            // === AUDITORÍA ===
            $table->timestamps();
            $table->softDeletes();

            // === ÍNDICES OPTOMIZADOS ===
            $table->index('usuario_id');
            $table->index('estado');
            $table->index('apertura');
            $table->index('moneda');

            // === REGLA DE NEGOCIO: Solo una caja abierta por usuario ===
            $table->unique(['usuario_id', 'estado'], 'cajas_usuario_estado_unique')
                  ->where('estado', 'abierta');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cajas');
    }
};