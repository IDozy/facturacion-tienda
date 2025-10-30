<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('libros_electronicos', function (Blueprint $table) {
            $table->id();

            // === PERIODO CONTABLE ===
            $table->foreignId('periodo_contable_id')
                ->constrained('periodos_contables')
                ->onDelete('cascade');

            // === TIPO DE LIBRO (PLE) ===
            $table->string('tipo_libro', 6)
                ->comment('050100, 080100, 140100, etc.');

            // === ARCHIVO ===
            $table->string('archivo_txt', 255)->nullable();
            $table->string('hash_archivo', 40)->nullable(); // SHA-1

            // === ESTADO ===
            $table->enum('estado', ['generado', 'enviado', 'rechazado'])
                ->default('generado')
                ->index(); // Ya está indexado aquí

            // === FECHAS ===
            $table->timestamp('fecha_generacion')->nullable();
            $table->timestamp('fecha_envio_sunat')->nullable();

            // === RECHAZO ===
            $table->text('motivo_rechazo')->nullable();

            // === TIMESTAMPS ===
            $table->timestamps();

            // === ÍNDICES OPTIMIZADOS ===
            $table->index(['periodo_contable_id', 'tipo_libro']);
            $table->index(['tipo_libro', 'estado']);
            $table->index('fecha_generacion');
            $table->index('fecha_envio_sunat');

            // === RESTRICCIONES ÚNICAS ===
            $table->unique(
                ['periodo_contable_id', 'tipo_libro'],
                'libro_periodo_tipo_unique'
            );
        });

        // === CHECKS ===
        DB::statement("ALTER TABLE libros_electronicos ADD CONSTRAINT chk_tipo_libro_formato CHECK (tipo_libro ~ '^[0-9]{6}$')");
        DB::statement("ALTER TABLE libros_electronicos ADD CONSTRAINT chk_hash_archivo_formato CHECK (hash_archivo IS NULL OR hash_archivo ~ '^[a-f0-9]{40}$')");
    }

    public function down(): void
    {
        Schema::dropIfExists('libros_electronicos');
    }
};
