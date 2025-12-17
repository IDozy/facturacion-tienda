<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class EmpresaMeController extends Controller
{
    public function show(Request $request)
    {
        $empresa = $request->user()?->empresa;

        if (! $empresa) {
            return response()->json([
                'message' => 'No se encontró una empresa asociada al usuario autenticado.',
            ], 404);
        }

        return response()->json([
            'data' => $empresa,
        ]);
    }

    public function update(Request $request)
    {
        $empresa = $request->user()?->empresa;

        if (! $empresa) {
            return response()->json([
                'message' => 'No se encontró una empresa asociada al usuario autenticado.',
            ], 404);
        }

        $timezones = timezone_identifiers_list();

        $validator = Validator::make(
            $request->all(),
            [
                'razon_social' => ['required', 'string', 'max:255'],
                'nombre_comercial' => ['nullable', 'string', 'max:255'],
                'ruc' => [
                    'required',
                    'regex:/^\d{11}$/',
                    Rule::unique('empresas', 'ruc')->ignore($empresa->id),
                ],
                'direccion_fiscal' => ['nullable', 'string', 'max:255'],
                'departamento' => ['nullable', 'string', 'max:120'],
                'provincia' => ['nullable', 'string', 'max:120'],
                'distrito' => ['nullable', 'string', 'max:120'],
                'telefono' => ['nullable', 'string', 'max:30'],
                'email' => ['nullable', 'email', 'max:255'],
                'moneda' => ['required', Rule::in(['PEN', 'USD'])],
                'igv_porcentaje' => ['nullable', 'numeric', 'between:0,100'],
                'incluye_igv_por_defecto' => ['boolean'],
                'serie_factura' => ['nullable', 'string', 'max:10'],
                'serie_boleta' => ['nullable', 'string', 'max:10'],
                'numero_factura_actual' => ['nullable', 'integer', 'min:0'],
                'numero_boleta_actual' => ['nullable', 'integer', 'min:0'],
                'formato_fecha' => ['required', Rule::in(['DD/MM/YYYY', 'MM/DD/YYYY', 'YYYY-MM-DD'])],
                'decimales' => ['nullable', 'integer', 'min:0', 'max:6'],
                'zona_horaria' => ['nullable', Rule::in($timezones)],
            ],
            [
                'ruc.regex' => 'El RUC debe tener 11 dígitos numéricos.',
                'email.email' => 'Ingrese un correo electrónico válido.',
                'moneda.in' => 'La moneda debe ser PEN o USD.',
                'formato_fecha.in' => 'Seleccione un formato de fecha válido.',
                'zona_horaria.in' => 'Seleccione una zona horaria válida.',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Hay errores en algunos campos.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $payload = collect($validator->validated())
            ->mapWithKeys(function ($value, $key) {
                if (is_string($value)) {
                    return [$key => trim($value)];
                }

                return [$key => $value];
            })
            ->toArray();

        if (array_key_exists('moneda', $payload)) {
            $payload['moneda'] = strtoupper($payload['moneda']);
        }

        if (array_key_exists('direccion_fiscal', $payload)) {
            $payload['direccion'] = $payload['direccion_fiscal'];
        }

        $payload['igv_porcentaje'] = $payload['igv_porcentaje'] ?? $empresa->igv_porcentaje ?? 18;
        $payload['decimales'] = $payload['decimales'] ?? $empresa->decimales ?? 2;
        $payload['zona_horaria'] = $payload['zona_horaria'] ?? 'America/Lima';

        $empresa->fill($payload);
        $empresa->save();

        return response()->json([
            'message' => 'Configuración de empresa actualizada correctamente.',
            'data' => $empresa,
        ]);
    }

    public function uploadLogo(Request $request)
    {
        $empresa = $request->user()?->empresa;

        if (! $empresa) {
            return response()->json([
                'message' => 'No se encontró una empresa asociada al usuario autenticado.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'logo' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,svg,webp', 'max:2048'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Hay errores en el archivo enviado.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $logo = $request->file('logo');

        if ($empresa->logo) {
            Storage::disk('public')->delete($empresa->logo);
        }

        $path = $logo->store('empresas/logos', 'public');
        $empresa->update(['logo' => $path]);

        return response()->json([
            'message' => 'Logo actualizado correctamente.',
            'data' => [
                'logoUrl' => $empresa->logo_url,
                'empresa' => $empresa,
            ],
        ]);
    }
}
