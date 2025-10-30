<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medios_pago', function (Blueprint $table) {
            $table->id();

            // === CÓDIGO SUNAT (Catálogo 59) ===
            $table->string('codigo_sunat', 3)
                  ->unique();

            // === NOMBRE Y DESCRIPCIÓN ===
            $table->string('nombre', 100);
            $table->text('descripcion')->nullable();

            // === ESTADO ===
            $table->boolean('activo')->default(true);

            // === TIMESTAMPS ===
            $table->timestamps();

            // === ÍNDICES ===
            $table->index('codigo_sunat');
            $table->index('activo');
            $table->index('nombre');

            // === FULLTEXT (opcional) ===
            // $table->fullText(['nombre', 'descripcion']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medios_pago');
    }
};