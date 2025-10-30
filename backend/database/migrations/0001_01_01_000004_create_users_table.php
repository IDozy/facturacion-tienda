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
        // === TABLA USERS (personalizada) ===
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            
            // Datos personales
            $table->enum('tipo_documento', ['DNI', 'RUC', 'CE', 'Pasaporte', 'Otro'])->nullable();
            $table->string('numero_documento', 20)->nullable();
            $table->string('telefono', 15)->nullable();

            // Multi-tenancy
            $table->foreignId('empresa_id')
                  ->constrained('empresas')
                  ->onDelete('cascade');

            $table->boolean('activo')->default(true);

            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes(); // deleted_at

            // === ÍNDICES ===
            $table->index('activo');
            $table->index('empresa_id');
            $table->index(['empresa_id', 'email']); // login por empresa

            // Único por empresa + documento
            $table->unique(['empresa_id', 'numero_documento'], 'users_empresa_documento_unique');
        });

        // === TABLA PASSWORD RESET TOKENS (mantiene original) ===
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        // === TABLA SESSIONS (mantiene original) ===
        Schema::create('password_resets', function (Blueprint $table) {
            $table->string('email')->index();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        // === TABLA SESSIONS (para web sessions) ===
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_resets');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};