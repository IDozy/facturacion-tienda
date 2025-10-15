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
            $table->bigInteger('empresa_id');
            $table->bigInteger('cliente_id');
            
            // Tipo de comprobante (código SUNAT)
            $table->string('tipo_comprobante', 2); // 01=Factura, 03=Boleta, 07=Nota Crédito, 08=Nota Débito
            
            // Serie y Correlativo
            $table->string('serie', 4); // F001, B001, FC01, BC01
            $table->integer('correlativo'); // 1, 2, 3, 4...
            
            // Fechas
            $table->date('fecha_emision');
            $table->date('fecha_vencimiento')->nullable();
            $table->time('hora_emision')->nullable();
            
            // Moneda
            $table->string('moneda', 3)->default('PEN'); // PEN=Soles, USD=Dólares
            $table->decimal('tipo_cambio', 10, 3)->default(1.000);
            
            // Totales
            $table->decimal('total_gravada', 10, 2)->default(0); // Base imponible
            $table->decimal('total_exonerada', 10, 2)->default(0);
            $table->decimal('total_inafecta', 10, 2)->default(0);
            $table->decimal('total_gratuita', 10, 2)->default(0);
            $table->decimal('total_igv', 10, 2)->default(0); // Monto del IGV
            $table->decimal('total_descuentos', 10, 2)->default(0);
            $table->decimal('total', 10, 2); // Total final
            
            // Observaciones
            $table->text('observaciones')->nullable();
            
            // Datos SUNAT
            $table->string('hash')->nullable(); // Resumen del XML
            $table->string('codigo_qr')->nullable(); // Código QR
            $table->text('xml')->nullable(); // XML generado
            $table->text('cdr')->nullable(); // Constancia de Recepción (respuesta SUNAT)
            
            // Estado SUNAT
            $table->string('estado_sunat', 20)->default('pendiente'); // pendiente, enviado, aceptado, rechazado, anulado
            $table->string('codigo_sunat')->nullable(); // Código de respuesta SUNAT
            $table->text('mensaje_sunat')->nullable(); // Mensaje de SUNAT
            $table->timestamp('fecha_envio_sunat')->nullable();
            
            // PDF
            $table->string('ruta_pdf')->nullable();
            
            // Documento relacionado (para notas de crédito/débito)
            $table->bigInteger('comprobante_relacionado_id')->nullable();
            $table->string('motivo_nota')->nullable(); // Motivo de la nota
            
            $table->timestamps();
            $table->softDeletes(); // Para "eliminar" sin borrar (anular)
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comprobantes');
    }
};
