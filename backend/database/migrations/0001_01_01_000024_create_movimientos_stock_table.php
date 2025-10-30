<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos_stock', function (Blueprint $table) {
            $table->id();

            // === RELACIONES ===
            $table->foreignId('producto_id')
                  ->constrained('productos')
                  ->onDelete('cascade');

            $table->foreignId('almacen_id')
                  ->constrained('almacenes')
                  ->onDelete('cascade');

            // === TIPO DE MOVIMIENTO ===
            $table->enum('tipo', ['entrada', 'salida', 'transferencia'])
                  ->index(); // Ya está indexado aquí

            // === CANTIDAD Y COSTO ===
            $table->decimal('cantidad', 12, 3)->default(0);
            $table->decimal('costo_unitario', 14, 2)->default(0);

            // === RELACIÓN POLIMÓRFICA ===
            $table->morphs('referencia'); // crea referencia_id, referencia_type y su índice

            // === AUDITORÍA ===
            $table->timestamps();

            // === ÍNDICES OPTIMIZADOS ===
            $table->index(['producto_id', 'almacen_id', 'created_at']);
            $table->index(['almacen_id', 'tipo']);
            $table->index('created_at');
            // REMOVIDO: $table->index('tipo'); <-- Esta línea duplicaba el índice
        });

        // === CHECKS DE INTEGRIDAD ===
        DB::statement('ALTER TABLE movimientos_stock ADD CONSTRAINT chk_costo_unitario_nonnegativo CHECK (costo_unitario >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_stock');
    }
};