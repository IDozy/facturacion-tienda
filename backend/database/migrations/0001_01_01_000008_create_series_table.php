<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('series', function (Blueprint $table) {
            $table->id();

            // === RELACIÓN CON EMPRESA ===
            $table->foreignId('empresa_id')
                  ->constrained('empresas')
                  ->onDelete('cascade');

            // === TIPO DE COMPROBANTE ===
            $table->enum('tipo_comprobante', [
                'factura',
                'boleta',
                'nota_credito',
                'nota_debito'
            ])->index();

            // === SERIE (F001, B001, etc.) ===
            $table->string('serie', 4); // F001, B002, etc.

            // === CORRELATIVO ACTUAL ===
            $table->unsignedInteger('correlativo_actual')->default(0);

            // === ESTADO ===
            $table->boolean('activo')->default(true);

            // === TIMESTAMPS ===
            $table->timestamps();

            // === RESTRICCIONES ÚNICAS ===
            // Una serie por tipo y empresa (ej: F001 solo una vez por empresa)
            $table->unique(['empresa_id', 'tipo_comprobante', 'serie'], 'series_empresa_tipo_serie_unique');

            // === ÍNDICES PARA CONSULTAS COMUNES ===
            $table->index(['empresa_id', 'tipo_comprobante', 'activo']);
            $table->index(['empresa_id', 'activo']);
            $table->index('serie');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('series');
    }
};