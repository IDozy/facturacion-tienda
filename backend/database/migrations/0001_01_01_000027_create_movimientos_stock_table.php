<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;



return new class extends Migration {
    public function up(): void {
        Schema::create('movimientos_stock', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id');
            $table->unsignedBigInteger('almacen_id');
            $table->unsignedBigInteger('producto_id');
            $table->unsignedBigInteger('usuario_id');
            
            $table->enum('tipo', ['entrada', 'salida', 'ajuste', 'transferencia']);
            $table->integer('cantidad');
            $table->text('descripcion')->nullable();
            $table->string('referencia')->nullable();
            $table->timestamps();
            
            $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
            $table->foreign('almacen_id')->references('id')->on('almacenes')->onDelete('restrict');
            $table->foreign('producto_id')->references('id')->on('productos')->onDelete('restrict');
            $table->foreign('usuario_id')->references('id')->on('users')->onDelete('restrict');
            $table->index(['producto_id', 'almacen_id']);
            $table->index('tipo');
            $table->index(['empresa_id', 'created_at']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('movimientos_stock');
    }
};
