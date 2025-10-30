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
        Schema::create('comprobante_detalles', function (Blueprint $table) {
            $table->id();
            
            // Relaciones
            $table->foreignId('comprobante_id')
                  ->constrained('comprobantes')
                  ->onDelete('cascade');

            $table->foreignId('producto_id')
                  ->nullable()
                  ->constrained('productos')
                  ->onDelete('set null');

            // Campos principales
            $table->decimal('cantidad', 10, 3)->default(1.000);
            $table->decimal('precio_unitario', 12, 2);
            $table->string('tipo_afectacion', 20)->default('gravado'); // gravado, exonerado, inafecto
            $table->decimal('subtotal', 12, 2);
            $table->decimal('igv', 12, 2)->default(0.00);
            $table->decimal('total', 12, 2);
            $table->decimal('descuento_monto', 12, 2)->default(0.00);

            // Timestamps
            $table->timestamps();

            // Índices útiles
            $table->index(['comprobante_id', 'producto_id']);
            $table->index('tipo_afectacion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comprobante_detalles');
    }
};