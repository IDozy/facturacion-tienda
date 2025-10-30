<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
      public function up(): void
      {
            Schema::create('configuraciones_empresa', function (Blueprint $table) {
                  $table->id();

                  // === MULTI-TENANCY ===
                  $table->foreignId('empresa_id')
                        ->unique()
                        ->constrained('empresas')
                        ->onDelete('cascade');

                  // === IMPUESTOS ===
                  $table->decimal('igv_porcentaje', 5, 2)
                        ->default(18.00)
                        ->comment('IGV % (ej: 18.00)');

                  $table->decimal('retencion_porcentaje_default', 5, 2)
                        ->default(3.00)
                        ->comment('Retención % por defecto');

                  $table->decimal('percepcion_porcentaje_default', 5, 2)
                        ->default(2.00)
                        ->comment('Percepción % por defecto');

                  // === MONEDA Y TOLERANCIA ===
                  $table->string('moneda_default', 3)
                        ->default('PEN');

                  $table->decimal('tolerancia_cuadratura', 14, 2)
                        ->default(1.00)
                        ->comment('Tolerancia en S/ para cuadratura contable');

                  // === TIMESTAMPS ===
                  $table->timestamps();

                  // === ÍNDICES ===
                  // REMOVIDO: $table->index('empresa_id'); <-- Ya tiene unique() que crea índice
                  $table->index('igv_porcentaje');
                  $table->index('moneda_default');
            });

            // === RESTRICCIONES ===
            DB::statement('ALTER TABLE configuraciones_empresa ADD CONSTRAINT chk_igv_porcentaje_rango CHECK (igv_porcentaje >= 0 AND igv_porcentaje <= 100)');
            DB::statement('ALTER TABLE configuraciones_empresa ADD CONSTRAINT chk_retencion_porcentaje_rango CHECK (retencion_porcentaje_default >= 0 AND retencion_porcentaje_default <= 100)');
            DB::statement('ALTER TABLE configuraciones_empresa ADD CONSTRAINT chk_percepcion_porcentaje_rango CHECK (percepcion_porcentaje_default >= 0 AND percepcion_porcentaje_default <= 100)');
            DB::statement('ALTER TABLE configuraciones_empresa ADD CONSTRAINT chk_tolerancia_cuadratura_nonnegativo CHECK (tolerancia_cuadratura >= 0)');
      }

      public function down(): void
      {
            Schema::dropIfExists('configuraciones_empresa');
      }
};
