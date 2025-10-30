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
        Schema::create('respuestas_sunat', function (Blueprint $table) {
            $table->id();

            // === RELACIÓN CON COMPROBANTE ===
            $table->foreignId('comprobante_id')
                  ->constrained('comprobantes')
                  ->onDelete('cascade');

            // === RESPUESTA DE SUNAT ===
            $table->string('codigo_respuesta', 10)->nullable();
            $table->text('descripcion_respuesta')->nullable();

            // === CONTROL DE REINTENTOS ===
            $table->unsignedTinyInteger('intento')->default(0);
            $table->timestamp('fecha_proximo_reintento')->nullable();

            // === ARCHIVOS ENCRIPTADOS (en el modelo) ===
            $table->longText('cdr')->nullable();     // ZIP del CDR
            $table->longText('xml')->nullable();     // XML firmado

            // === ESTADO DEL ENVÍO ===
            $table->enum('estado_envio', ['pendiente', 'aceptado', 'rechazado'])
                  ->default('pendiente');

            // === AUDITORÍA ===
            $table->timestamps();

            // === ÍNDICES OPTOMIZADOS ===
            $table->index('comprobante_id');
            $table->index('estado_envio');
            $table->index('intento');
            $table->index('fecha_proximo_reintento');

            // Clave para scopeParaReintento(): pendientes + reintentables
            $table->index(['estado_envio', 'intento', 'fecha_proximo_reintento']);

            // Único por comprobante (solo un registro activo por envío)
            $table->unique('comprobante_id', 'respuestas_sunat_comprobante_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('respuestas_sunat');
    }
};