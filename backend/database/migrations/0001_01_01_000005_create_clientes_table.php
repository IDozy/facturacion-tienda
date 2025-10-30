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
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            
            // === DATOS DEL CLIENTE ===
            $table->enum('tipo_documento', ['DNI', 'RUC', 'CE', 'Pasaporte', 'Otro'])
                  ->nullable();
            
            $table->string('numero_documento', 20)
                  ->nullable();
            
            $table->string('razon_social');
            $table->string('direccion')->nullable();
            $table->string('email')->nullable();
            $table->string('telefono', 15)->nullable();

            // === MULTI-TENANCY ===
            $table->foreignId('empresa_id')
                  ->constrained('empresas')
                  ->onDelete('cascade');

            // === ESTADO ===
            $table->enum('estado', ['activo', 'inactivo'])
                  ->default('activo');

            // === AUDITORÍA ===
            $table->timestamps();
            $table->softDeletes(); // deleted_at

            // === ÍNDICES OPTOMIZADOS ===
            $table->index('estado');
            $table->index('empresa_id');
            $table->index('tipo_documento');
            $table->index('numero_documento');

            // Único por empresa + documento (evita duplicados dentro de la misma empresa)
            $table->unique(
                ['empresa_id', 'numero_documento'],
                'clientes_empresa_documento_unique'
            );

            // Para búsquedas rápidas por email + empresa (opcional)
            $table->index(['empresa_id', 'email']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};