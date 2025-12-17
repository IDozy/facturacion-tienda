<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->string('nombre_comercial')->nullable()->after('razon_social');
            $table->string('direccion_fiscal')->nullable()->after('direccion');
            $table->string('departamento')->nullable()->after('direccion_fiscal');
            $table->string('provincia')->nullable()->after('departamento');
            $table->string('distrito')->nullable()->after('provincia');
            $table->string('moneda', 3)->default('PEN')->after('logo');
            $table->decimal('igv_porcentaje', 5, 2)->default(18.00)->after('moneda');
            $table->boolean('incluye_igv_por_defecto')->default(true)->after('igv_porcentaje');
            $table->string('serie_factura', 10)->nullable()->after('incluye_igv_por_defecto');
            $table->string('serie_boleta', 10)->nullable()->after('serie_factura');
            $table->unsignedBigInteger('numero_factura_actual')->default(1)->after('serie_boleta');
            $table->unsignedBigInteger('numero_boleta_actual')->default(1)->after('numero_factura_actual');
            $table->string('formato_fecha', 20)->default('DD/MM/YYYY')->after('numero_boleta_actual');
            $table->unsignedTinyInteger('decimales')->default(2)->after('formato_fecha');
            $table->string('zona_horaria')->default('America/Lima')->after('decimales');
        });

        DB::table('empresas')
            ->whereNull('direccion_fiscal')
            ->update(['direccion_fiscal' => DB::raw('direccion')]);
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn([
                'nombre_comercial',
                'direccion_fiscal',
                'departamento',
                'provincia',
                'distrito',
                'moneda',
                'igv_porcentaje',
                'incluye_igv_por_defecto',
                'serie_factura',
                'serie_boleta',
                'numero_factura_actual',
                'numero_boleta_actual',
                'formato_fecha',
                'decimales',
                'zona_horaria',
            ]);
        });
    }
};
