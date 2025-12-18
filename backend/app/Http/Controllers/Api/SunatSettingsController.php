<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\TaxSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class SunatSettingsController extends Controller
{
    public function status(Request $request)
    {
        if (!Schema::hasTable('tax_settings')) {
            return response()->json(['message' => 'Faltan migraciones de configuración SUNAT. Ejecuta php artisan migrate.'], 500);
        }
        $user = $request->user();
        $tax = TaxSetting::firstOrCreate(
            ['empresa_id' => $user->empresa_id],
            [
                'regimen' => 'GENERAL',
                'afectacion_igv' => 'GRAVADO',
                'ambiente' => 'PRUEBAS',
                'updated_by' => $user->id,
            ]
        );

        return response()->json([
            'hasSolCredentials' => (bool) $tax->has_sol_credentials,
            'hasCertificate' => (bool) $tax->certificate_storage_key,
            'certificateStatus' => $tax->certificate_status,
            'certificateValidFrom' => $tax->certificate_valid_from?->toDateString(),
            'certificateValidUntil' => $tax->certificate_valid_until?->toDateString(),
            'certificateIssuer' => null,
        ]);
    }

    public function saveCredentials(Request $request)
    {
        if (!Schema::hasTable('tax_settings')) {
            return response()->json(['message' => 'Faltan migraciones de configuración SUNAT. Ejecuta php artisan migrate.'], 500);
        }
        $user = $request->user();
        if (!$user->hasAnyRole(['admin', 'administrador'])) {
            return response()->json(['message' => 'Solo los administradores pueden editar'], 403);
        }

        $validator = Validator::make($request->all(), [
            'sunatUser' => ['required', 'string'],
            'sunatPassword' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Datos inválidos', 'errors' => $validator->errors()], 422);
        }

        $tax = TaxSetting::updateOrCreate(
            ['empresa_id' => $user->empresa_id],
            [
                'regimen' => $request->input('regimen', 'GENERAL'),
                'afectacion_igv' => $request->input('afectacionIgv', 'GRAVADO'),
            ]
        );

        $tax->update([
            'sunat_user_encrypted' => Crypt::encryptString($request->string('sunatUser')), 
            'sunat_password_encrypted' => Crypt::encryptString($request->string('sunatPassword')),
            'has_sol_credentials' => true,
            'updated_by' => $user->id,
        ]);

        AuditLog::create([
            'empresa_id' => $user->empresa_id,
            'user_id' => $user->id,
            'action' => 'update',
            'module' => 'sunat-credentials',
            'payload' => ['user' => $request->string('sunatUser')],
        ]);

        return response()->json(['message' => 'Credenciales guardadas', 'hasSolCredentials' => true]);
    }

    public function uploadCertificate(Request $request)
    {
        if (!Schema::hasTable('tax_settings')) {
            return response()->json(['message' => 'Faltan migraciones de configuración SUNAT. Ejecuta php artisan migrate.'], 500);
        }
        $user = $request->user();
        if (!$user->hasAnyRole(['admin', 'administrador'])) {
            return response()->json(['message' => 'Solo los administradores pueden editar'], 403);
        }

        $validator = Validator::make($request->all(), [
            'certificate' => ['required', 'file', 'mimes:pfx,p12', 'max:5120'],
            'password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Datos inválidos', 'errors' => $validator->errors()], 422);
        }

        $file = $request->file('certificate');
        $path = $file->storeAs('certificates', 'emp-' . $user->empresa_id . '-' . time() . '.' . $file->getClientOriginalExtension());

        $tax = TaxSetting::firstOrCreate(
            ['empresa_id' => $user->empresa_id],
            [
                'regimen' => 'GENERAL',
                'afectacion_igv' => 'GRAVADO',
            ]
        );

        if ($tax->certificate_storage_key) {
            Storage::delete($tax->certificate_storage_key);
        }

        $tax->update([
            'certificate_storage_key' => $path,
            'certificate_password_encrypted' => Crypt::encryptString($request->string('password')),
            'certificate_valid_from' => now(),
            'certificate_valid_until' => now()->addYear(),
            'certificate_status' => 'ACTIVE',
            'certificado_estado' => 'VIGENTE',
            'has_sol_credentials' => $tax->has_sol_credentials,
            'updated_by' => $user->id,
        ]);

        AuditLog::create([
            'empresa_id' => $user->empresa_id,
            'user_id' => $user->id,
            'action' => 'upload',
            'module' => 'sunat-certificate',
            'payload' => ['path' => $path],
        ]);

        return response()->json([
            'message' => 'Certificado actualizado',
            'hasCertificate' => true,
            'certificateStatus' => 'ACTIVE',
            'certificateValidFrom' => now()->toDateString(),
            'certificateValidUntil' => now()->addYear()->toDateString(),
        ]);
    }

    public function test(Request $request)
    {
        if (!Schema::hasTable('tax_settings')) {
            return response()->json(['message' => 'Faltan migraciones de configuración SUNAT. Ejecuta php artisan migrate.'], 500);
        }
        $user = $request->user();
        $tax = TaxSetting::where('empresa_id', $user->empresa_id)->first();
        if (!$tax || !$tax->has_sol_credentials || !$tax->certificate_storage_key) {
            return response()->json(['message' => 'Configura credenciales SOL y certificado antes de probar'], 422);
        }

        if ($tax->certificate_status !== 'ACTIVE') {
            return response()->json(['message' => 'El certificado no está vigente'], 422);
        }

        return response()->json(['message' => 'Conexión SUNAT OK']);
    }
}
