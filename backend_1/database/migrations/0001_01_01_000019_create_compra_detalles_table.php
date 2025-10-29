<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('compra_detalles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('compra_id');
            $table->unsignedBigInteger('producto_id');
            $table->unsignedBigInteger('almacen_id')->nullable();
            
            $table->integer('item')->default(1); // Orden de línea
            $table->decimal('cantidad', 12, 4)->default(0); // Con decimales
            $table->decimal('cantidad_recibida', 12, 4)->default(0); // Para recepción parcial
            $table->decimal('precio_unitario', 12, 2)->default(0);
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('descuento', 12, 2)->default(0);
            $table->enum('tipo_igv', ['10', '20', '30'])->default('10');
            $table->decimal('porcentaje_igv', 5, 2)->default(18);
            $table->decimal('igv', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->string('unidad_medida', 10)->default('UND');
            $table->text('descripcion')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('compra_id')->references('id')->on('compras')->onDelete('cascade');
            $table->foreign('producto_id')->references('id')->on('productos')->onDelete('restrict');
            $table->foreign('almacen_id')->references('id')->on('almacenes')->onDelete('set null');
            $table->index(['compra_id', 'producto_id']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('compra_detalles');
    }
};