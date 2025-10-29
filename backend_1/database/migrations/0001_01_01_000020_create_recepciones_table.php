<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('recepciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id');
            $table->unsignedBigInteger('compra_id');
            $table->unsignedBigInteger('almacen_id');
            $table->unsignedBigInteger('usuario_id');
            
            $table->string('numero_recepcion')->unique();
            $table->date('fecha_recepcion');
            $table->text('observaciones')->nullable();
            $table->enum('estado', ['pendiente', 'parcial', 'completa'])->default('pendiente');
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
            $table->foreign('compra_id')->references('id')->on('compras')->onDelete('restrict');
            $table->foreign('almacen_id')->references('id')->on('almacenes')->onDelete('restrict');
            $table->foreign('usuario_id')->references('id')->on('users')->onDelete('restrict');
            $table->index(['empresa_id', 'compra_id']);
            $table->index('estado');
        });
    }

    public function down(): void {
        Schema::dropIfExists('recepciones');
    }
};