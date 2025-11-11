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
        Schema::create('empresas', function (Blueprint $table) {
            $table->id();

            // === DATOS PRINCIPALES ===
            $table->string('razon_social');
            $table->string('ruc', 11)->unique(); // RUC peruano
            $table->string('direccion')->nullable();
            $table->string('telefono', 15)->nullable();
            $table->string('email')->nullable();
            $table->string('logo')->nullable();

            // === CERTIFICADO DIGITAL (encriptado en el modelo) ===
            $table->text('certificado_digital')->nullable(); // TEXT para archivos .pfx grandes
            $table->string('clave_certificado')->nullable();

            // === CREDENCIALES SOL (encriptadas en modelo) ===
            $table->string('usuario_sol')->nullable();
            $table->string('clave_sol')->nullable();

            // === CONFIGURACIÓN ===
            $table->enum('modo', ['prueba', 'produccion'])->default('prueba');
            $table->date('fecha_expiracion_certificado')->nullable();
            $table->boolean('pse_autorizado')->default(false);

            // === AUDITORÍA ===
            $table->timestamps();
            $table->softDeletes(); // deleted_at

            // === ÍNDICES ===
            $table->index('ruc');
            $table->index('modo');
            $table->index('pse_autorizado');
            $table->index('fecha_expiracion_certificado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empresas');
    }
};
