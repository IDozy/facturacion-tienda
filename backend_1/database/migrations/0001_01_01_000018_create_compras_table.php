<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('compras', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id');
            $table->unsignedBigInteger('proveedor_id');
            $table->unsignedBigInteger('usuario_id');
            $table->string('numero_comprobante')->nullable();
            $table->date('fecha_compra');
            $table->date('fecha_vencimiento')->nullable();
            $table->enum('moneda', ['PEN', 'USD', 'EUR'])->default('PEN');
            $table->decimal('tipo_cambio', 8, 4)->default(1);
            $table->decimal('total_gravada', 12, 2)->default(0);
            $table->decimal('total_exonerada', 12, 2)->default(0);
            $table->decimal('total_igv', 12, 2)->default(0);
            $table->decimal('total_descuentos', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->enum('estado', ['pendiente', 'parcial', 'recibida', 'cancelada', 'anulada'])->default('pendiente');
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
            $table->foreign('proveedor_id')->references('id')->on('proveedores')->onDelete('restrict');
            $table->foreign('usuario_id')->references('id')->on('users')->onDelete('restrict');
            $table->index('empresa_id');
            $table->index('proveedor_id');
            $table->index('estado');
        });
    }

    public function down(): void {
        Schema::dropIfExists('compras');
    }
};