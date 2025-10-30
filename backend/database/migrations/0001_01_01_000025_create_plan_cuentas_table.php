<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_cuentas', function (Blueprint $table) {
            $table->id();

            // === CÓDIGO Y NOMBRE ===
            $table->string('codigo', 20); // 1, 10, 101, 10101, 1010101 (PCGE)
            $table->string('nombre', 150);

            // === TIPO DE CUENTA (PCGE) ===
            $table->enum('tipo', ['activo', 'pasivo', 'patrimonio', 'ingreso', 'gasto'])
                  ->index(); // Ya está indexado aquí

            // === JERARQUÍA ===
            $table->foreignId('padre_id')
                  ->nullable()
                  ->constrained('plan_cuentas')
                  ->onDelete('cascade');

            $table->unsignedTinyInteger('nivel')->default(1); // 1 a 7 (PCGE)

            // === AUXILIAR (nivel >= 4) ===
            $table->boolean('es_auxiliar')->default(false);

            // === MULTI-TENANCY ===
            $table->foreignId('empresa_id')
                  ->constrained('empresas')
                  ->onDelete('cascade');

            // === TIMESTAMPS ===
            $table->timestamps();

            // === ÍNDICES OPTIMIZADOS ===
            $table->index(['empresa_id', 'codigo']);
            $table->index(['empresa_id', 'tipo']);
            $table->index(['empresa_id', 'nivel']);
            $table->index(['empresa_id', 'es_auxiliar']);
            $table->index(['padre_id']);
            $table->index('nivel');
            // REMOVIDO: $table->index('tipo'); <-- Duplicado

            // === RESTRICCIONES ÚNICAS ===
            $table->unique(['empresa_id', 'codigo'], 'plan_cuentas_empresa_codigo_unique');
            $table->unique(['empresa_id', 'padre_id', 'codigo'], 'plan_cuentas_empresa_padre_codigo_unique');
        });

        // === CHECKS DE INTEGRIDAD ===
        DB::statement('ALTER TABLE plan_cuentas ADD CONSTRAINT chk_nivel_rango CHECK (nivel >= 1 AND nivel <= 7)');
        DB::statement('ALTER TABLE plan_cuentas ADD CONSTRAINT chk_es_auxiliar_nivel CHECK (es_auxiliar = (nivel >= 4))');
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_cuentas');
    }
};