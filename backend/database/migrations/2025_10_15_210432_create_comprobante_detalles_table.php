<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comprobante_detalles', function (Blueprint $table) {
            $table->id();
            
            // Relación con comprobante
            $table->bigInteger('comprobante_id');
            
            // Relación con producto (opcional si es servicio u otro)
            $table->bigInteger('producto_id')->nullable();
            
            // Orden de la línea
            $table->integer('item')->default(1); // 1, 2, 3...
            
            // Descripción del producto/servicio
            $table->string('codigo_producto')->nullable();
            $table->string('descripcion');
            $table->string('unidad_medida', 3)->default('NIU');
            
            // Cantidades
            $table->decimal('cantidad', 10, 2);
            
            // Precios unitarios (sin IGV)
            $table->decimal('precio_unitario', 10, 2);
            $table->decimal('precio_venta', 10, 2); // Con IGV incluido
            
            // Descuento
            $table->decimal('descuento', 10, 2)->default(0);
            $table->decimal('porcentaje_descuento', 5, 2)->default(0);
            
            // IGV
            $table->string('tipo_igv', 2)->default('10'); // 10=Gravado, 20=Exonerado, 30=Inafecto
            $table->decimal('porcentaje_igv', 5, 2)->default(18.00);
            $table->decimal('igv', 10, 2)->default(0); // Monto del IGV
            
            // Totales de la línea
            $table->decimal('subtotal', 10, 2); // cantidad * precio_unitario - descuento
            $table->decimal('total', 10, 2); // subtotal + igv
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comprobante_detalles');
    }
};
