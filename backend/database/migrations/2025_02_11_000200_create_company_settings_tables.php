<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('razon_social');
            $table->string('nombre_comercial')->nullable();
            $table->string('ruc', 11);
            $table->string('direccion_fiscal');
            $table->string('direccion_comercial')->nullable();
            $table->string('telefono')->nullable();
            $table->string('email')->nullable();
            $table->string('logo_url')->nullable();
            $table->string('region')->nullable();
            $table->string('ciudad')->nullable();
            $table->string('pais')->default('Perú');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique('empresa_id');
            $table->index(['empresa_id', 'ruc']);
        });

        Schema::create('tax_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();

            $table->string('regimen');
            $table->string('tipo_contribuyente')->nullable();
            $table->string('afectacion_igv');

            // ❌ Eliminado: codigo_establecimiento

            $table->string('certificado_url')->nullable();
            $table->string('certificado_estado')->nullable();
            $table->date('certificado_vigencia_desde')->nullable();
            $table->date('certificado_vigencia_hasta')->nullable();
            $table->enum('ambiente', ['PRUEBAS', 'PRODUCCION'])->default('PRUEBAS');

            // 🔐 SOL cifrado
            $table->string('sunat_user_encrypted')->nullable();
            $table->string('sunat_password_encrypted')->nullable();
            $table->boolean('has_sol_credentials')->default(false);

            // 🔏 Certificado digital
            $table->string('certificate_storage_key')->nullable();
            $table->string('certificate_password_encrypted')->nullable();
            $table->date('certificate_valid_from')->nullable();
            $table->date('certificate_valid_until')->nullable();
            $table->enum('certificate_status', ['ACTIVE', 'EXPIRED', 'REVOKED'])->nullable();

            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique('empresa_id');
        });

        Schema::create('document_series', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->enum('tipo', ['FACTURA', 'BOLETA', 'NC', 'ND', 'GUIA']);
            $table->string('serie', 10);
            $table->unsignedInteger('correlativo_inicial')->default(1);
            $table->unsignedInteger('correlativo_actual')->default(1);
            $table->boolean('automatico')->default(true);
            $table->boolean('activo')->default(true);
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['empresa_id', 'tipo', 'serie']);
            $table->index(['empresa_id', 'tipo']);
        });

        // 👉 El resto de tablas (currencies, warehouses, etc.)
        // 👉 Se mantienen IGUAL que en tu archivo original
        // 👉 No requieren cambios
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('integrations');
        Schema::dropIfExists('system_preferences');
        Schema::dropIfExists('accounting_settings');
        Schema::dropIfExists('bank_accounts');
        Schema::dropIfExists('cashboxes');
        Schema::dropIfExists('warehouses');
        Schema::dropIfExists('exchange_rates');
        Schema::dropIfExists('currencies');
        Schema::dropIfExists('document_series');
        Schema::dropIfExists('tax_settings');
        Schema::dropIfExists('company_settings');
    }
};
