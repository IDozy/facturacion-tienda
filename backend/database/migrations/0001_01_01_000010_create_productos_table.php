<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            
            // Identificación
            $table->string('codigo', 50); // SKU, código interno
            $table->string('codigo_barras')->nullable(); // EAN13, etc
            
            // Descripción
            $table->string('descripcion');
            $table->text('descripcion_larga')->nullable();
            
            // Unidad de medida (código SUNAT)
            $table->string('unidad_medida', 3)->default('NIU'); // NIU=Unidad, KGM=Kilogramo, etc
            
            // Precios
            $table->decimal('precio_unitario', 10, 2); // Precio sin IGV
            $table->decimal('precio_venta', 10, 2); // Precio con IGV (opcional)
            
            // Tipo de IGV (código SUNAT)
            $table->string('tipo_igv', 2)->default('10'); // 10=Gravado, 20=Exonerado, 30=Inafecto
            $table->decimal('porcentaje_igv', 5, 2)->default(18.00); // 18%
            
            // Inventario
            $table->decimal('stock', 10, 2)->default(0);
            $table->decimal('stock_minimo', 10, 2)->default(0);
            
            // Ubicación en almacén
            $table->string('ubicacion')->nullable(); // Ej: "Piso 2 - Sección A - Estante 5"
            
            // Categoría
            $table->string('categoria')->nullable();
            
            // Imagen
            $table->string('imagen')->nullable();
            
            // Estado
            $table->boolean('activo')->default(true);
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
