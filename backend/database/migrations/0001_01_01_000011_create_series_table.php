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
            
            // Relación con empresa
            $table->bigInteger('empresa_id');
            
            // Tipo de comprobante (código SUNAT)
            $table->string('tipo_comprobante', 2); // 01=Factura, 03=Boleta, 07=NC, 08=ND
            
            // Serie
            $table->string('serie', 4); // F001, B001, FC01, BC01
            
            // Correlativo actual
            $table->integer('correlativo_actual')->default(0); // Se incrementa automáticamente
            
            // Descripción
            $table->string('descripcion')->nullable(); // Ej: "Facturas - Sede Principal"
            
            // Estado
            $table->boolean('activo')->default(true);
            $table->boolean('por_defecto')->default(false); // Serie predeterminada para ese tipo
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('series');
    }
};
