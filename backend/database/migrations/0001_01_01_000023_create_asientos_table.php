<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration {
    public function up(): void {
        Schema::create('asientos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id');
            $table->unsignedBigInteger('usuario_id');
            
            $table->string('numero_asiento')->unique();
            $table->string('diario');
            $table->date('fecha_asiento');
            $table->text('descripcion')->nullable();
            $table->text('referencia')->nullable();
            $table->string('glosa')->nullable();
            $table->enum('estado', ['borrador', 'registrado', 'anulado'])->default('borrador');
            $table->decimal('total_debe', 14, 2)->default(0);
            $table->decimal('total_haber', 14, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
            $table->foreign('usuario_id')->references('id')->on('users')->onDelete('restrict');
            $table->index(['empresa_id', 'fecha_asiento']);
            $table->index('estado');
        });
    }

    public function down(): void {
        Schema::dropIfExists('asientos');
    }
};