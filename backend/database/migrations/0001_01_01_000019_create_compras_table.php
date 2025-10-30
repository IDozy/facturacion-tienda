<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('compras', function (Blueprint $table) {
            $table->id();

            // === RELACIONES ===
            $table->foreignId('proveedor_id')
                  ->constrained('proveedores')
                  ->onDelete('restrict'); // No eliminar proveedor si tiene compras

            $table->foreignId('empresa_id')
                  ->constrained('empresas')
                  ->onDelete('cascade');

            $table->foreignId('almacen_id')
                  ->constrained('almacenes')
                  ->onDelete('restrict'); // No eliminar almacén si tiene compras

            // === DATOS DE LA COMPRA ===
            $table->date('fecha_emision');
            $table->decimal('total', 14, 2)->default(0); // Calculado desde detalles
            $table->enum('estado', ['registrada', 'anulada'])->default('registrada');

            // === AUDITORÍA ===
            $table->timestamps();

            // === ÍNDICES OPTOMIZADOS ===
            $table->index('proveedor_id');
            $table->index('empresa_id');
            $table->index('almacen_id');
            $table->index('estado');
            $table->index('fecha_emision');

            // Búsqueda por período
            $table->index(['fecha_emision', 'estado']);

            // Reportes por proveedor + período
            $table->index(['proveedor_id', 'fecha_emision']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compras');
    }
};