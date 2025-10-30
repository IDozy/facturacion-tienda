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
        Schema::create('compra_detalles', function (Blueprint $table) {
            $table->id();

            // === RELACIÓN CON COMPRA ===
            $table->foreignId('compra_id')
                  ->constrained('compras')
                  ->onDelete('cascade');

            // === RELACIÓN CON PRODUCTO ===
            $table->foreignId('producto_id')
                  ->constrained('productos')
                  ->onDelete('restrict'); // Evita eliminar producto si tiene compras

            // === CANTIDAD Y PRECIOS ===
            $table->decimal('cantidad', 12, 3);        // Hasta 9,999,999.999
            $table->decimal('precio_unitario', 12, 2); // Hasta 9,999,999.99
            $table->decimal('subtotal', 12, 2);        // Calculado: cantidad * precio

            // === AUDITORÍA ===
            $table->timestamps();

            // === ÍNDICES OPTOMIZADOS ===
            

            // Para totales por producto
            $table->index('compra_id','producto_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compra_detalles');
    }
};