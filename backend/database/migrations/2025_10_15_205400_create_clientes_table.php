<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            
            // Identificación
            $table->string('tipo_documento', 1); // 1=DNI, 6=RUC, 4=Carnet Extranjería, 7=Pasaporte
            $table->string('numero_documento', 15);
            
            // Datos personales/empresariales
            $table->string('nombre_razon_social');
            $table->string('nombre_comercial')->nullable();
            
            // Dirección
            $table->string('direccion')->nullable();
            $table->string('distrito')->nullable();
            $table->string('provincia')->nullable();
            $table->string('departamento')->nullable();
            $table->string('ubigeo', 6)->nullable();
            
            // Contacto
            $table->string('telefono')->nullable();
            $table->string('email')->nullable();
            
            // Estado
            $table->boolean('activo')->default(true);
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
