<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('almacen_productos', function (Blueprint $table) {
            $table->id();

            // === RELACIONES ===
            $table->foreignId('almacen_id')
                ->constrained('almacenes')
                ->onDelete('cascade');

            $table->foreignId('producto_id')
                ->constrained('productos')
                ->onDelete('cascade');

            // === STOCK ===
            $table->decimal('stock_actual', 12, 3)
                ->default(0)
                ->comment('Stock físico actual');

            // === TIMESTAMPS ===
            $table->timestamps();

            // === RESTRICCIONES ===
            $table->unique(['almacen_id', 'producto_id'], 'almacen_productos_unique');

            // === ÍNDICES OPTIMIZADOS ===
            $table->index(['producto_id', 'stock_actual']);
            $table->index(['almacen_id', 'stock_actual']);
            $table->index('stock_actual');
        });

        // === CHECK CONSTRAINT ===
        DB::statement('ALTER TABLE almacen_productos ADD CONSTRAINT chk_stock_actual_nonnegativo CHECK (stock_actual >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('almacen_productos');
    }
};
