<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration {
    public function up(): void {
        Schema::create('asiento_detalles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('asiento_id');
            $table->unsignedBigInteger('cuenta_id');
            
            $table->integer('item');
            $table->text('descripcion');
            $table->decimal('debe', 14, 2)->default(0);
            $table->decimal('haber', 14, 2)->default(0);
            $table->timestamps();
            
            $table->foreign('asiento_id')->references('id')->on('asientos')->onDelete('cascade');
            $table->foreign('cuenta_id')->references('id')->on('plan_cuentas')->onDelete('restrict');
            $table->index(['asiento_id', 'cuenta_id']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('asiento_detalles');
    }
};