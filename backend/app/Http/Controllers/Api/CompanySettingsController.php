<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AccountingSetting;
use App\Models\AuditLog;
use App\Models\BankAccount;
use App\Models\Cashbox;
use App\Models\CompanySetting;
use App\Models\Currency;
use App\Models\DocumentSeries;
use App\Models\ExchangeRate;
use App\Models\Integration;
use App\Models\SystemPreference;
use App\Models\TaxSetting;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CompanySettingsController extends Controller
{
    public function show(Request $request)
    {
        if (!Schema::hasTable('company_settings') || !Schema::hasTable('tax_settings')) {
            return response()->json(['message' => 'Faltan migraciones de configuración de empresa. Ejecuta php artisan migrate.'], 500);
        }

        $user = $request->user();
        $empresaId = $user->empresa_id;
        $company = CompanySetting::firstOrCreate(
            ['empresa_id' => $empresaId],
            [
                'razon_social' => $user->empresa->razon_social ?? '',
                'nombre_comercial' => $user->empresa->nombre_comercial ?? null,
                'ruc' => $user->empresa->ruc ?? '',
                'direccion_fiscal' => $user->empresa->direccion ?? '',
                'pais' => 'Perú',
                'updated_by' => $user->id,
            ]
        );

        $tax = TaxSetting::firstOrNew(['empresa_id' => $empresaId]);
        $series = DocumentSeries::where('empresa_id', $empresaId)->get();
        $currencies = Currency::with('exchangeRates')->where('empresa_id', $empresaId)->get();
        $warehouses = Warehouse::where('empresa_id', $empresaId)->get();
        $cashboxes = Cashbox::where('empresa_id', $empresaId)->get();
        $bankAccounts = BankAccount::where('empresa_id', $empresaId)->get();
        $accounting = AccountingSetting::firstOrNew(['empresa_id' => $empresaId]);
        $preferences = SystemPreference::firstOrNew(['empresa_id' => $empresaId]);
        $integrations = Integration::where('empresa_id', $empresaId)->get();
        $permissions = $user->getAllPermissions()->pluck('name')->toArray();

        $completion = [
            'general' => $company->razon_social && $company->ruc && $company->direccion_fiscal,
            'sunat' => $tax && $tax->regimen && $tax->afectacion_igv,
            'documentos' => $series->count() >= 1,
            'monedas' => $currencies->count() >= 1,
            'almacenes' => $warehouses->count() >= 1,
            'caja' => $cashboxes->count() >= 1,
            'contabilidad' => (bool)$accounting?->plan_contable,
            'seguridad' => true,
            'preferencias' => (bool)$preferences?->idioma,
            'integraciones' => $integrations->count() >= 1,
        ];

        return response()->json([
            'general' => [
                'razonSocial' => $company->razon_social,
                'nombreComercial' => $company->nombre_comercial,
                'ruc' => $company->ruc,
                'direccionFiscal' => $company->direccion_fiscal,
                'direccionComercial' => $company->direccion_comercial,
                'telefono' => $company->telefono,
                'email' => $company->email,
                'logoUrl' => $company->logo_url,
                'region' => $company->region,
                'ciudad' => $company->ciudad,
                'pais' => $company->pais,
            ],
            'sunat' => [
                'regimen' => $tax->regimen,
                'tipoContribuyente' => $tax->tipo_contribuyente,
                'afectacionIgv' => $tax->afectacion_igv,
                'certificadoUrl' => $tax->certificado_url,
                'certificadoEstado' => $tax->certificado_estado,
                'certificadoVigenciaDesde' => $tax->certificado_vigencia_desde?->toDateString(),
                'certificadoVigenciaHasta' => $tax->certificado_vigencia_hasta?->toDateString(),
                'ambiente' => $tax->ambiente ?? 'PRUEBAS',
                'hasSolCredentials' => (bool)$tax->has_sol_credentials,
                'hasCertificate' => (bool)$tax->certificate_storage_key,
                'certificateStatus' => $tax->certificate_status,
                'certificateValidFrom' => $tax->certificate_valid_from?->toDateString(),
                'certificateValidUntil' => $tax->certificate_valid_until?->toDateString(),
            ],
            'documentos' => [
                'series' => $series->map(function ($s) {
                    return [
                        'tipo' => $s->tipo,
                        'serie' => $s->serie,
                        'correlativoInicial' => $s->correlativo_inicial,
                        'correlativoActual' => $s->correlativo_actual,
                        'automatico' => (bool)$s->automatico,
                        'activo' => (bool)$s->activo,
                    ];
                })->values(),
            ],
            'monedas' => [
                'monedaBase' => $currencies->firstWhere('is_base', true) ? [
                    'code' => $currencies->firstWhere('is_base', true)->code,
                    'name' => $currencies->firstWhere('is_base', true)->name,
                    'preciosIncluyenIgv' => (bool)$currencies->firstWhere('is_base', true)->precios_incluyen_igv,
                    'igvRate' => (float)$currencies->firstWhere('is_base', true)->igv_rate,
                    'redondeo' => (bool)$currencies->firstWhere('is_base', true)->redondeo,
                    'tipoCambioAutomatico' => (bool)$currencies->firstWhere('is_base', true)->tipo_cambio_automatico,
                ] : [
                    'code' => 'PEN',
                    'name' => 'Sol Peruano',
                    'preciosIncluyenIgv' => true,
                    'igvRate' => 18,
                    'redondeo' => false,
                    'tipoCambioAutomatico' => false,
                ],
                'secundarias' => $currencies->filter(fn ($c) => !$c->is_base)->map(function ($c) {
                    return [
                        'code' => $c->code,
                        'name' => $c->name,
                        'preciosIncluyenIgv' => (bool)$c->precios_incluyen_igv,
                        'igvRate' => (float)$c->igv_rate,
                        'redondeo' => (bool)$c->redondeo,
                        'tipoCambioAutomatico' => (bool)$c->tipo_cambio_automatico,
                    ];
                })->values(),
                'exchangeRates' => $currencies->flatMap(function ($c) {
                    return $c->exchangeRates->map(function ($rate) use ($c) {
                        return [
                            'currencyCode' => $c->code,
                            'fecha' => $rate->fecha?->toDateString(),
                            'compra' => $rate->compra,
                            'venta' => $rate->venta,
                            'fuente' => $rate->fuente,
                            'automatico' => $rate->automatico,
                        ];
                    });
                })->values(),
            ],
            'almacenes' => $warehouses->map(function ($w) {
                return [
                    'nombre' => $w->nombre,
                    'principal' => (bool)$w->principal,
                    'stockNegativo' => (bool)$w->stock_negativo,
                    'manejaSeries' => (bool)$w->maneja_series,
                    'manejaLotes' => (bool)$w->maneja_lotes,
                    'codigoBarras' => $w->codigo_barras,
                    'activo' => (bool)$w->activo,
                ];
            })->values(),
            'cajaBancos' => [
                'cajas' => $cashboxes->map(function ($c) {
                    return [
                        'nombre' => $c->nombre,
                        'moneda' => $c->moneda,
                        'porDefecto' => (bool)$c->por_defecto,
                        'manejaCheques' => (bool)$c->maneja_cheques,
                        'liquidacionDiaria' => (bool)$c->liquidacion_diaria,
                        'flujoAutomatico' => (bool)$c->flujo_automatico,
                    ];
                })->values(),
                'bancos' => $bankAccounts->map(function ($b) {
                    return [
                        'banco' => $b->banco,
                        'numero' => $b->numero,
                        'moneda' => $b->moneda,
                        'esPrincipal' => (bool)$b->es_principal,
                        'manejaCheques' => (bool)$b->maneja_cheques,
                    ];
                })->values(),
            ],
            'contabilidad' => [
                'planContable' => $accounting->plan_contable,
                'cuentaVentas' => $accounting->cuenta_ventas,
                'cuentaCompras' => $accounting->cuenta_compras,
                'cuentaIgv' => $accounting->cuenta_igv,
                'cuentaCaja' => $accounting->cuenta_caja,
                'cuentaBancos' => $accounting->cuenta_bancos,
                'contabilizacionAutomatica' => (bool)$accounting->contabilizacion_automatica,
                'centrosCostoObligatorios' => (bool)$accounting->centros_costo_obligatorios,
                'periodos' => $accounting->periodos ?? [],
            ],
            'seguridad' => [
                'roles' => $user->roles->map(fn ($r) => ['nombre' => $r->name]),
                'privilegios' => [
                    'precios' => in_array('ver precios', $permissions),
                    'reportes' => in_array('ver reportes', $permissions),
                    'eliminaciones' => in_array('eliminar registros', $permissions),
                ],
            ],
            'preferencias' => [
                'idioma' => $preferences->idioma ?? 'es-PE',
                'zonaHoraria' => $preferences->zona_horaria ?? 'America/Lima',
                'formatoFecha' => $preferences->formato_fecha ?? 'dd/MM/yyyy',
                'decimales' => $preferences->decimales ?? 2,
                'alertas' => $preferences->alertas ?? [],
            ],
            'integraciones' => $integrations->map(function ($i) {
                return [
                    'tipo' => $i->tipo,
                    'params' => $i->params,
                    'activo' => (bool)$i->activo,
                ];
            })->values(),
            'completion' => $completion,
            'canEdit' => $user->hasAnyRole(['admin', 'administrador']),
            'updatedAt' => $company->updated_at?->toDateTimeString(),
        ]);
    }

    public function update(Request $request)
    {
        if (!Schema::hasTable('company_settings') || !Schema::hasTable('tax_settings')) {
            return response()->json(['message' => 'Faltan migraciones de configuración de empresa. Ejecuta php artisan migrate.'], 500);
        }

        $user = $request->user();
        if (!$user->hasAnyRole(['admin', 'administrador'])) {
            return response()->json(['message' => 'Solo los administradores pueden editar'], 403);
        }

        $empresaId = $user->empresa_id;
        $rules = [
            'general.razonSocial' => ['required', 'string'],
            'general.ruc' => ['required', 'digits:11'],
            'general.direccionFiscal' => ['required', 'string'],
            'sunat.regimen' => ['required', 'string'],
            'sunat.afectacionIgv' => ['required', 'string'],
            'sunat.ambiente' => ['required', Rule::in(['PRUEBAS', 'PRODUCCION'])],
            'documentos.series' => ['required', 'array', 'min:1'],
            'documentos.series.*.tipo' => [Rule::in(['FACTURA', 'BOLETA', 'NC', 'ND', 'GUIA'])],
            'documentos.series.*.serie' => ['required', 'string'],
            'documentos.series.*.correlativoInicial' => ['required', 'integer', 'min:1'],
            'documentos.series.*.correlativoActual' => ['required', 'integer', 'min:1'],
            'monedas.monedaBase.code' => ['required', 'string', 'size:3'],
            'monedas.monedaBase.igvRate' => ['required', 'numeric', 'between:0,100'],
            'monedas.secundarias' => ['array'],
            'almacenes' => ['array'],
            'cajaBancos.cajas' => ['array'],
            'cajaBancos.bancos' => ['array'],
            'contabilidad.contabilizacionAutomatica' => ['boolean'],
            'contabilidad.centrosCostoObligatorios' => ['boolean'],
            'preferencias.idioma' => ['required', 'string'],
            'preferencias.zonaHoraria' => ['required', 'string'],
            'integraciones' => ['array'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['message' => 'Datos inválidos', 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $general = Arr::get($data, 'general', []);
        $sunat = Arr::get($data, 'sunat', []);
        $documentos = Arr::get($data, 'documentos.series', []);
        $monedas = Arr::get($data, 'monedas', []);
        $almacenes = Arr::get($data, 'almacenes', []);
        $cajaBancos = Arr::get($data, 'cajaBancos', []);
        $contabilidad = Arr::get($data, 'contabilidad', []);
        $preferencias = Arr::get($data, 'preferencias', []);
        $integraciones = Arr::get($data, 'integraciones', []);

        $existingTax = TaxSetting::firstOrNew(['empresa_id' => $empresaId]);
        if (($sunat['ambiente'] ?? 'PRUEBAS') === 'PRODUCCION') {
            if (!$existingTax->has_sol_credentials) {
                return response()->json(['message' => 'Configura credenciales SOL antes de pasar a producción'], 422);
            }
            if (!$existingTax->certificate_storage_key || ($existingTax->certificate_status ?? 'EXPIRED') !== 'ACTIVE') {
                return response()->json(['message' => 'El certificado digital debe estar vigente antes de pasar a producción'], 422);
            }
        }

        $company = CompanySetting::updateOrCreate(
            ['empresa_id' => $empresaId],
            [
                'razon_social' => $general['razonSocial'] ?? '',
                'nombre_comercial' => $general['nombreComercial'] ?? null,
                'ruc' => $general['ruc'] ?? '',
                'direccion_fiscal' => $general['direccionFiscal'] ?? '',
                'direccion_comercial' => $general['direccionComercial'] ?? null,
                'telefono' => $general['telefono'] ?? null,
                'email' => $general['email'] ?? null,
                'logo_url' => $general['logoUrl'] ?? null,
                'region' => $general['region'] ?? null,
                'ciudad' => $general['ciudad'] ?? null,
                'pais' => $general['pais'] ?? 'Perú',
                'updated_by' => $user->id,
            ]
        );

        TaxSetting::updateOrCreate(
            ['empresa_id' => $empresaId],
            [
                'regimen' => $sunat['regimen'] ?? '',
                'tipo_contribuyente' => $sunat['tipoContribuyente'] ?? null,
                'afectacion_igv' => $sunat['afectacionIgv'] ?? '',
                'certificado_url' => $sunat['certificadoUrl'] ?? null,
                'certificado_estado' => $sunat['certificadoEstado'] ?? null,
                'certificado_vigencia_desde' => $sunat['certificadoVigenciaDesde'] ?? null,
                'certificado_vigencia_hasta' => $sunat['certificadoVigenciaHasta'] ?? null,
                'ambiente' => $sunat['ambiente'] ?? 'PRUEBAS',
                'updated_by' => $user->id,
            ]
        );

        DocumentSeries::where('empresa_id', $empresaId)->delete();
        foreach ($documentos as $serie) {
            DocumentSeries::create([
                'empresa_id' => $empresaId,
                'tipo' => $serie['tipo'],
                'serie' => $serie['serie'],
                'correlativo_inicial' => $serie['correlativoInicial'],
                'correlativo_actual' => $serie['correlativoActual'],
                'automatico' => $serie['automatico'] ?? true,
                'activo' => $serie['activo'] ?? true,
                'updated_by' => $user->id,
            ]);
        }

        Currency::where('empresa_id', $empresaId)->each(function ($currency) {
            $currency->exchangeRates()->delete();
        });
        Currency::where('empresa_id', $empresaId)->delete();

        $base = Arr::get($monedas, 'monedaBase');
        $createdBase = Currency::create([
            'empresa_id' => $empresaId,
            'code' => $base['code'],
            'name' => $base['name'] ?? $base['code'],
            'is_base' => true,
            'precios_incluyen_igv' => $base['preciosIncluyenIgv'] ?? true,
            'igv_rate' => $base['igvRate'] ?? 18,
            'redondeo' => $base['redondeo'] ?? false,
            'tipo_cambio_automatico' => $base['tipoCambioAutomatico'] ?? false,
            'updated_by' => $user->id,
        ]);

        $secundarias = Arr::get($monedas, 'secundarias', []);
        $secondaryModels = collect();
        foreach ($secundarias as $currency) {
            $secondaryModels->push(Currency::create([
                'empresa_id' => $empresaId,
                'code' => $currency['code'],
                'name' => $currency['name'] ?? $currency['code'],
                'is_base' => false,
                'precios_incluyen_igv' => $currency['preciosIncluyenIgv'] ?? false,
                'igv_rate' => $currency['igvRate'] ?? $base['igvRate'] ?? 18,
                'redondeo' => $currency['redondeo'] ?? false,
                'tipo_cambio_automatico' => $currency['tipoCambioAutomatico'] ?? false,
                'updated_by' => $user->id,
            ]));
        }

        $exchangeRates = Arr::get($monedas, 'exchangeRates', []);
        foreach ($exchangeRates as $rate) {
            $target = $rate['currencyCode'] === $base['code'] ? $createdBase : $secondaryModels->firstWhere('code', $rate['currencyCode']);
            if ($target) {
                ExchangeRate::create([
                    'currency_id' => $target->id,
                    'fecha' => $rate['fecha'],
                    'compra' => $rate['compra'] ?? null,
                    'venta' => $rate['venta'] ?? null,
                    'fuente' => $rate['fuente'] ?? null,
                    'automatico' => $rate['automatico'] ?? false,
                ]);
            }
        }

        Warehouse::where('empresa_id', $empresaId)->delete();
        foreach ($almacenes as $almacen) {
            Warehouse::create([
                'empresa_id' => $empresaId,
                'nombre' => $almacen['nombre'],
                'principal' => $almacen['principal'] ?? false,
                'stock_negativo' => $almacen['stockNegativo'] ?? false,
                'maneja_series' => $almacen['manejaSeries'] ?? false,
                'maneja_lotes' => $almacen['manejaLotes'] ?? false,
                'codigo_barras' => $almacen['codigoBarras'] ?? null,
                'activo' => $almacen['activo'] ?? true,
                'updated_by' => $user->id,
            ]);
        }

        Cashbox::where('empresa_id', $empresaId)->delete();
        foreach (Arr::get($cajaBancos, 'cajas', []) as $caja) {
            Cashbox::create([
                'empresa_id' => $empresaId,
                'nombre' => $caja['nombre'],
                'moneda' => $caja['moneda'] ?? 'PEN',
                'por_defecto' => $caja['porDefecto'] ?? false,
                'maneja_cheques' => $caja['manejaCheques'] ?? false,
                'liquidacion_diaria' => $caja['liquidacionDiaria'] ?? false,
                'flujo_automatico' => $caja['flujoAutomatico'] ?? false,
                'updated_by' => $user->id,
            ]);
        }

        BankAccount::where('empresa_id', $empresaId)->delete();
        foreach (Arr::get($cajaBancos, 'bancos', []) as $banco) {
            BankAccount::create([
                'empresa_id' => $empresaId,
                'banco' => $banco['banco'],
                'numero' => $banco['numero'],
                'moneda' => $banco['moneda'] ?? 'PEN',
                'es_principal' => $banco['esPrincipal'] ?? false,
                'maneja_cheques' => $banco['manejaCheques'] ?? false,
                'updated_by' => $user->id,
            ]);
        }

        AccountingSetting::updateOrCreate(
            ['empresa_id' => $empresaId],
            [
                'plan_contable' => $contabilidad['planContable'] ?? null,
                'cuenta_ventas' => $contabilidad['cuentaVentas'] ?? null,
                'cuenta_compras' => $contabilidad['cuentaCompras'] ?? null,
                'cuenta_igv' => $contabilidad['cuentaIgv'] ?? null,
                'cuenta_caja' => $contabilidad['cuentaCaja'] ?? null,
                'cuenta_bancos' => $contabilidad['cuentaBancos'] ?? null,
                'contabilizacion_automatica' => $contabilidad['contabilizacionAutomatica'] ?? false,
                'centros_costo_obligatorios' => $contabilidad['centrosCostoObligatorios'] ?? false,
                'periodos' => $contabilidad['periodos'] ?? [],
                'updated_by' => $user->id,
            ]
        );

        SystemPreference::updateOrCreate(
            ['empresa_id' => $empresaId],
            [
                'idioma' => $preferencias['idioma'] ?? 'es-PE',
                'zona_horaria' => $preferencias['zonaHoraria'] ?? 'America/Lima',
                'formato_fecha' => $preferencias['formatoFecha'] ?? 'dd/MM/yyyy',
                'decimales' => $preferencias['decimales'] ?? 2,
                'alertas' => $preferencias['alertas'] ?? [],
                'updated_by' => $user->id,
            ]
        );

        Integration::where('empresa_id', $empresaId)->delete();
        foreach ($integraciones as $integration) {
            $params = $integration['params'] ?? [];
            if (is_string($params)) {
                $decoded = json_decode($params, true);
                $params = $decoded ?? ['value' => $params];
            }
            Integration::create([
                'empresa_id' => $empresaId,
                'tipo' => $integration['tipo'],
                'params' => $params,
                'activo' => $integration['activo'] ?? true,
                'updated_by' => $user->id,
            ]);
        }

        AuditLog::create([
            'empresa_id' => $empresaId,
            'user_id' => $user->id,
            'action' => 'update',
            'module' => 'company-settings',
            'payload' => ['source' => 'company-settings-route'],
        ]);

        return $this->show($request);
    }
}
