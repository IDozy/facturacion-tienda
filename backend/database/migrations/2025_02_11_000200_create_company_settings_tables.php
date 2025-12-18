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
            $table->string('codigo_establecimiento')->nullable();
            $table->string('certificado_url')->nullable();
            $table->string('certificado_estado')->nullable();
            $table->date('certificado_vigencia_desde')->nullable();
            $table->date('certificado_vigencia_hasta')->nullable();
            $table->enum('ambiente', ['PRUEBAS', 'PRODUCCION'])->default('PRUEBAS');
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

        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('code', 3);
            $table->string('name', 50);
            $table->boolean('is_base')->default(false);
            $table->boolean('precios_incluyen_igv')->default(true);
            $table->decimal('igv_rate', 5, 2)->default(18);
            $table->boolean('redondeo')->default(false);
            $table->boolean('tipo_cambio_automatico')->default(false);
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['empresa_id', 'code']);
        });

        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('currency_id')->constrained('currencies')->cascadeOnDelete();
            $table->date('fecha');
            $table->decimal('compra', 12, 6)->nullable();
            $table->decimal('venta', 12, 6)->nullable();
            $table->string('fuente')->nullable();
            $table->boolean('automatico')->default(false);
            $table->timestamps();

            $table->unique(['currency_id', 'fecha']);
        });

        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('nombre');
            $table->boolean('principal')->default(false);
            $table->boolean('stock_negativo')->default(false);
            $table->boolean('maneja_series')->default(false);
            $table->boolean('maneja_lotes')->default(false);
            $table->string('codigo_barras')->nullable();
            $table->boolean('activo')->default(true);
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('cashboxes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('nombre');
            $table->string('moneda', 3)->default('PEN');
            $table->boolean('por_defecto')->default(false);
            $table->boolean('maneja_cheques')->default(false);
            $table->boolean('liquidacion_diaria')->default(false);
            $table->boolean('flujo_automatico')->default(false);
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('banco');
            $table->string('numero');
            $table->string('moneda', 3)->default('PEN');
            $table->boolean('es_principal')->default(false);
            $table->boolean('maneja_cheques')->default(false);
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('accounting_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('plan_contable')->nullable();
            $table->string('cuenta_ventas')->nullable();
            $table->string('cuenta_compras')->nullable();
            $table->string('cuenta_igv')->nullable();
            $table->string('cuenta_caja')->nullable();
            $table->string('cuenta_bancos')->nullable();
            $table->boolean('contabilizacion_automatica')->default(false);
            $table->boolean('centros_costo_obligatorios')->default(false);
            $table->json('periodos')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('system_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('idioma')->default('es-PE');
            $table->string('zona_horaria')->default('America/Lima');
            $table->string('formato_fecha')->default('dd/MM/yyyy');
            $table->unsignedTinyInteger('decimales')->default(2);
            $table->json('alertas')->nullable();
            $table->json('preferencias')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('integrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('tipo');
            $table->json('params')->nullable();
            $table->boolean('activo')->default(true);
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('action');
            $table->string('module');
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['empresa_id', 'module']);
        });
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
