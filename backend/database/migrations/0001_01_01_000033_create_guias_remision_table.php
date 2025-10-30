<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guias_remision', function (Blueprint $table) {
            $table->id();

            // === MULTI-TENANCY ===
            $table->foreignId('empresa_id')
                ->constrained('empresas')
                ->onDelete('cascade');

            // === COMPROBANTE (opcional) ===
            $table->foreignId('comprobante_id')
                ->nullable()
                ->constrained('comprobantes')
                ->onDelete('set null');

            // === SERIE Y NÚMERO ===
            $table->string('serie', 4);        // T001, T002
            $table->unsignedInteger('numero'); // 1, 2, 3...

            // === FECHA ===
            $table->date('fecha_emision');

            // === MOTIVO TRASLADO (Catálogo 20) ===
            $table->enum('motivo_traslado', [
                'venta',
                'traslado_interno',
                'devolucion',
                'importacion',
                'exportacion',
                'otros'
            ])->default('venta');

            // === PESO Y DIRECCIONES ===
            $table->decimal('peso_total', 10, 2)->nullable();
            $table->string('punto_partida', 255);
            $table->string('punto_llegada', 255);

            // === TRANSPORTISTA ===
            $table->string('transportista_ruc', 11)->nullable();
            $table->string('transportista_razon_social', 150)->nullable();

            // === VEHÍCULO Y CONDUCTOR ===
            $table->string('placa_vehiculo', 10)->nullable();
            $table->string('conductor_dni', 8)->nullable();
            $table->string('conductor_nombre', 100)->nullable();

            // === ESTADO ===
            $table->enum('estado', ['emitida', 'anulada'])
                ->default('emitida')
                ->index(); // Ya está indexado aquí

            // === ANULACIÓN ===
            $table->text('motivo_anulacion')->nullable();

            // === TIMESTAMPS ===
            $table->timestamps();

            // === ÍNDICES OPTIMIZADOS ===
            $table->index(['empresa_id', 'serie', 'numero']);
            $table->index(['empresa_id', 'estado']);
            $table->index(['empresa_id', 'fecha_emision']);
            $table->index(['comprobante_id']);
            $table->index('motivo_traslado');

            // === RESTRICCIONES ÚNICAS ===
            $table->unique(['empresa_id', 'serie', 'numero'], 'guia_empresa_serie_numero_unique');
        });

        // === CHECKS DE INTEGRIDAD ===
        DB::statement("ALTER TABLE guias_remision ADD CONSTRAINT chk_serie_formato CHECK (serie ~ '^T\\d{3}$')");
        DB::statement('ALTER TABLE guias_remision ADD CONSTRAINT chk_numero_positivo CHECK (numero > 0)');
        DB::statement('ALTER TABLE guias_remision ADD CONSTRAINT chk_peso_total_nonnegativo CHECK (peso_total IS NULL OR peso_total >= 0)');
        DB::statement("ALTER TABLE guias_remision ADD CONSTRAINT chk_transportista_ruc_formato CHECK (transportista_ruc IS NULL OR transportista_ruc ~ '^\\d{11}$')");
        DB::statement("ALTER TABLE guias_remision ADD CONSTRAINT chk_conductor_dni_formato CHECK (conductor_dni IS NULL OR conductor_dni ~ '^\\d{8}$')");
        DB::statement("ALTER TABLE guias_remision ADD CONSTRAINT chk_placa_vehiculo_formato CHECK (placa_vehiculo IS NULL OR placa_vehiculo ~ '^[A-Z0-9-]{6,10}$')");
    }

    public function down(): void
    {
        Schema::dropIfExists('guias_remision');
    }
};
