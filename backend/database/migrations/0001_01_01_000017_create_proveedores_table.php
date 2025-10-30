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
        Schema::create('proveedores', function (Blueprint $table) {
            $table->id();
            
            // === DATOS DEL PROVEEDOR ===
            $table->string('tipo_documento', 10); // RUC, DNI, etc.
            $table->string('numero_documento', 20);
            $table->string('razon_social');
            $table->string('direccion')->nullable();
            $table->string('telefono', 15)->nullable();
            $table->string('email')->nullable();

            // === MULTI-TENANCY ===
            $table->foreignId('empresa_id')
                  ->constrained('empresas')
                  ->onDelete('cascade');

            // === ESTADO ===
            $table->enum('estado', ['activo', 'inactivo'])
                  ->default('activo');

            // === SOFT DELETES & TIMESTAMPS ===
            $table->softDeletes();
            $table->timestamps();

            // === ÍNDICES ===
            $table->index(['empresa_id', 'estado']);
            $table->index('numero_documento');
            $table->index('razon_social');
            $table->index('email');

            // === RESTRICCIONES ÚNICAS POR EMPRESA ===
            $table->unique(['empresa_id', 'numero_documento']);
            $table->unique(['empresa_id', 'email'], 'proveedores_empresa_email_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proveedores');
    }
};