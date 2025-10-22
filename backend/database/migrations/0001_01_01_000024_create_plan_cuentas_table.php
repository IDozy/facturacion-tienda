<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('plan_cuentas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id');
            $table->unsignedBigInteger('cuenta_padre_id')->nullable();
            
            $table->string('codigo')->unique();
            $table->string('nombre');
            $table->enum('tipo', ['activo', 'pasivo', 'patrimonio', 'ingreso', 'gasto', 'resultado'])->default('activo');
            $table->enum('naturaleza', ['deudora', 'acreedora'])->default('deudora');
            $table->boolean('es_subcuenta')->default(false);
            $table->text('descripcion')->nullable();
            $table->decimal('saldo_inicial', 14, 2)->default(0);
            $table->decimal('saldo_actual', 14, 2)->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
            $table->foreign('cuenta_padre_id')->references('id')->on('plan_cuentas')->onDelete('set null');
            $table->index('empresa_id');
            $table->index('tipo');
        });
    }

    public function down(): void {
        Schema::dropIfExists('plan_cuentas');
    }
};