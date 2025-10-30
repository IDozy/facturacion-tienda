<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asiento_detalles', function (Blueprint $table) {
            $table->id();

            // === RELACIONES ===
            $table->foreignId('asiento_id')
                  ->constrained('asientos')
                  ->onDelete('cascade');

            $table->foreignId('cuenta_id')
                  ->constrained('plan_cuentas')
                  ->onDelete('restrict'); // No eliminar cuenta si tiene movimientos

            // === DETALLE ===
            $table->text('descripcion')->nullable();
            $table->decimal('debe', 14, 2)->default(0);
            $table->decimal('haber', 14, 2)->default(0);

            // === TIMESTAMPS ===
            $table->timestamps();

            // === ÍNDICES OPTIMIZADOS ===
            $table->index(['asiento_id', 'cuenta_id']);
            $table->index(['cuenta_id']);
            $table->index('debe');
            $table->index('haber');
        });

        // === RESTRICCIONES DE INTEGRIDAD ===
        DB::statement('ALTER TABLE asiento_detalles ADD CONSTRAINT chk_debe_nonnegativo CHECK (debe >= 0)');
        DB::statement('ALTER TABLE asiento_detalles ADD CONSTRAINT chk_haber_nonnegativo CHECK (haber >= 0)');
        DB::statement('ALTER TABLE asiento_detalles ADD CONSTRAINT chk_debe_haber_exclusivo CHECK ((debe > 0 AND haber = 0) OR (haber > 0 AND debe = 0))');
    }

    public function down(): void
    {
        Schema::dropIfExists('asiento_detalles');
    }
};