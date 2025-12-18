<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tax_settings', function (Blueprint $table) {
            if (Schema::hasColumn('tax_settings', 'codigo_establecimiento')) {
                $table->dropColumn('codigo_establecimiento');
            }
            if (!Schema::hasColumn('tax_settings', 'sunat_user_encrypted')) {
                $table->string('sunat_user_encrypted')->nullable();
            }
            if (!Schema::hasColumn('tax_settings', 'sunat_password_encrypted')) {
                $table->string('sunat_password_encrypted')->nullable();
            }
            if (!Schema::hasColumn('tax_settings', 'has_sol_credentials')) {
                $table->boolean('has_sol_credentials')->default(false);
            }
            if (!Schema::hasColumn('tax_settings', 'certificate_storage_key')) {
                $table->string('certificate_storage_key')->nullable();
            }
            if (!Schema::hasColumn('tax_settings', 'certificate_password_encrypted')) {
                $table->string('certificate_password_encrypted')->nullable();
            }
            if (!Schema::hasColumn('tax_settings', 'certificate_valid_from')) {
                $table->date('certificate_valid_from')->nullable();
            }
            if (!Schema::hasColumn('tax_settings', 'certificate_valid_until')) {
                $table->date('certificate_valid_until')->nullable();
            }
            if (!Schema::hasColumn('tax_settings', 'certificate_status')) {
                $table->enum('certificate_status', ['ACTIVE', 'EXPIRED', 'REVOKED'])->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('tax_settings', function (Blueprint $table) {
            $table->string('codigo_establecimiento')->nullable();
            $table->dropColumn([
                'sunat_user_encrypted',
                'sunat_password_encrypted',
                'has_sol_credentials',
                'certificate_storage_key',
                'certificate_password_encrypted',
                'certificate_valid_from',
                'certificate_valid_until',
                'certificate_status',
            ]);
        });
    }
};
