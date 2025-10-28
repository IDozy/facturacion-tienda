<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empresas', function (Blueprint $table) {
            $table->id();
            
            // Datos básicos de la empresa
            $table->string('ruc', 11);
            $table->string('razon_social');
            $table->string('nombre_comercial')->nullable();
            
            // Dirección fiscal
            $table->string('direccion');
            $table->string('urbanizacion')->nullable();
            $table->string('distrito');
            $table->string('provincia');
            $table->string('departamento');
            $table->string('ubigeo', 6); // Código INEI
            $table->string('codigo_pais', 2)->default('PE');
            
            // Contacto
            $table->string('telefono')->nullable();
            $table->string('email')->nullable();
            $table->string('web')->nullable();
            
            // Credenciales SUNAT
            $table->string('usuario_sol')->nullable();
            $table->string('clave_sol')->nullable(); // Encriptada
            
            // Certificado digital (firma electrónica)
            $table->text('certificado_digital')->nullable(); // Ruta o contenido
            $table->string('clave_certificado')->nullable(); // Encriptada
            
            // Configuración
            $table->boolean('modo_prueba')->default(true); // true=beta, false=producción
            $table->string('logo')->nullable(); // Ruta del logo
            
            // Estado
            $table->boolean('activo')->default(true);
            
              $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empresas');
    }
};
