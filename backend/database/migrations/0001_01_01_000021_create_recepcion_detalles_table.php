<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('recepcion_detalles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('recepcion_id');
            $table->unsignedBigInteger('compra_detalle_id');
            $table->unsignedBigInteger('producto_id');
            
            $table->decimal('cantidad_recibida', 12, 4);
            $table->text('observaciones')->nullable();
            $table->timestamps();
            
            $table->foreign('recepcion_id')->references('id')->on('recepciones')->onDelete('cascade');
            $table->foreign('compra_detalle_id')->references('id')->on('compra_detalles')->onDelete('restrict');
            $table->foreign('producto_id')->references('id')->on('productos')->onDelete('restrict');
            $table->index(['recepcion_id', 'compra_detalle_id']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('recepcion_detalles');
    }
};