<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comprobantes', function (Blueprint $table) {
            $table->id();
            
            // Relaciones
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->onDelete('set null');
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
            $table->foreignId('serie_id')->nullable()->constrained('series')->onDelete('set null');
            $table->foreignId('usuario_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('comprobante_referencia_id')
                  ->nullable()
                  ->constrained('comprobantes')
                  ->onDelete('set null');

            // Datos del comprobante
            $table->string('tipo_comprobante'); // factura, boleta, nota_credito, nota_debito
            $table->string('numero', 20);
            $table->date('fecha_emision');
            $table->decimal('total', 12, 2);
            $table->decimal('descuento_total', 12, 2)->default(0);
            $table->decimal('igv_total', 12, 2)->default(0);
            $table->decimal('total_neto', 12, 2);
            $table->decimal('subtotal_gravado', 12, 2)->default(0);
            $table->decimal('subtotal_exonerado', 12, 2)->default(0);
            $table->decimal('subtotal_inafecto', 12, 2)->default(0);
            $table->decimal('saldo_pendiente', 12, 2);

            // Estado y anulación
            $table->enum('estado', ['borrador', 'emitido', 'aceptado_sunat', 'rechazado_sunat', 'anulado'])
                  ->default('borrador');
            $table->text('motivo_anulacion')->nullable();

            // Datos SUNAT
            $table->string('hash_cpe', 64)->nullable();

            // Datos del cliente (copia al momento de emisión)
            $table->string('tipo_documento_cliente', 10)->nullable();
            $table->string('numero_documento_cliente', 20)->nullable();
            $table->string('razon_social_cliente')->nullable();

            // Forma de pago
            $table->string('forma_pago')->default('contado'); // contado, credito
            $table->integer('plazo_pago_dias')->nullable();

            // Exportación y moneda
            $table->boolean('es_exportacion')->default(false);
            $table->string('codigo_moneda', 3)->default('PEN'); // ISO 4217
            $table->decimal('tipo_cambio', 8, 3)->nullable();

            // Observaciones
            $table->text('observaciones')->nullable();

            // Timestamps
            $table->timestamps();

            // Índices útiles
            $table->index(['empresa_id', 'tipo_comprobante', 'fecha_emision']);
            $table->index(['cliente_id', 'estado']);
            $table->index('estado');
            $table->index('saldo_pendiente');
            $table->index('numero');
            $table->unique(['serie_id', 'numero']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comprobantes');
    }
};