<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration {
    public function up(): void {
        Schema::create('auditorias', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id');
            $table->unsignedBigInteger('usuario_id');
            
            $table->string('modelo');
            $table->unsignedBigInteger('modelo_id');
            $table->string('accion');
            $table->json('cambios')->nullable();
            $table->string('ip')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
            
            $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
            $table->foreign('usuario_id')->references('id')->on('users')->onDelete('restrict');
            $table->index(['modelo', 'modelo_id']);
            $table->index(['empresa_id', 'created_at']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('auditorias');
    }
};