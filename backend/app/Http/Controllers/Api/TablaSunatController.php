<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TablaSunat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class TablaSunatController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = TablaSunat::query();

        // Filtro por tipo de tabla
        if ($request->has('tipo_tabla')) {
            $query->porTipo($request->tipo_tabla);
        }

        // Filtro por activo
        if ($request->has('activo')) {
            $query->where('activo', $request->activo);
        }

        // Búsqueda
        if ($request->has('search')) {
            $query->search($request->search);
        }

        // Ordenamiento
        $sortBy = $request->get('sort_by', 'codigo');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginación
        $perPage = $request->get('per_page', 50);

        if ($request->get('all') === 'true') {
            $tablas = $query->get();
            return response()->json(['data' => $tablas]);
        }

        $tablas = $query->paginate($perPage);

        return response()->json($tablas);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'codigo' => [
                'required',
                'string',
                'max:10',
                Rule::unique('tablas_sunat')->where(function ($query) use ($request) {
                    return $query->where('tipo_tabla', $request->tipo_tabla);
                })
            ],
            'descripcion' => 'required|string|max:255',
            'tipo_tabla' => [
                'required',
                'string',
                Rule::in([
                    TablaSunat::TIPO_DOCUMENTO,
                    TablaSunat::TIPO_AFECTACION,
                    TablaSunat::UNIDAD_MEDIDA,
                    TablaSunat::TIPO_MONEDA,
                    TablaSunat::TIPO_PAIS,
                    TablaSunat::TIPO_COMPROBANTE,
                    TablaSunat::TIPO_OPERACION,
                    TablaSunat::TIPO_NOTA,
                ])
            ],
            'activo' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $tabla = TablaSunat::create($request->all());

            // Limpiar caché relacionado
            $this->clearCacheForTipo($request->tipo_tabla);

            return response()->json([
                'message' => 'Tabla SUNAT creada exitosamente',
                'data' => $tabla
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear la tabla SUNAT',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(TablaSunat $tablaSunat)
    {
        return response()->json([
            'data' => $tablaSunat
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    
    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\TablaSunat $tablaSunat
     */
    public function update(Request $request, TablaSunat $tablaSunat)
    {
        $validator = Validator::make($request->all(), [
            'codigo' => [
                'sometimes',
                'required',
                'string',
                'max:10',
                Rule::unique('tablas_sunat')
                    ->ignore($tablaSunat->id)
                    ->where(function ($query) use ($request) {
                        return $query->where('tipo_tabla', $request->tipo_tabla ?? $tablaSunat->tipo_tabla);
                    })
            ],
            'descripcion' => 'sometimes|required|string|max:255',
            'tipo_tabla' => [
                'sometimes',
                'required',
                'string',
                Rule::in([
                    TablaSunat::TIPO_DOCUMENTO,
                    TablaSunat::TIPO_AFECTACION,
                    TablaSunat::UNIDAD_MEDIDA,
                    TablaSunat::TIPO_MONEDA,
                    TablaSunat::TIPO_PAIS,
                    TablaSunat::TIPO_COMPROBANTE,
                    TablaSunat::TIPO_OPERACION,
                    TablaSunat::TIPO_NOTA,
                ])
            ],
            'activo' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $oldTipo = $tablaSunat->tipo_tabla;

            $tablaSunat->update($request->all());

            // Limpiar caché relacionado (tanto el anterior como el nuevo si cambió)
            $this->clearCacheForTipo($oldTipo);
            if ($request->has('tipo_tabla') && $request->tipo_tabla !== $oldTipo) {
                $this->clearCacheForTipo($request->tipo_tabla);
            }

            return response()->json([
                'message' => 'Tabla SUNAT actualizada exitosamente',
                'data' => $tablaSunat
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar la tabla SUNAT',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TablaSunat $tablaSunat)
    {
        try {
            $tipo = $tablaSunat->tipo_tabla;
            $tablaSunat->delete();

            // Limpiar caché relacionado
            $this->clearCacheForTipo($tipo);

            return response()->json([
                'message' => 'Tabla SUNAT eliminada exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar la tabla SUNAT',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener todos los tipos de tabla disponibles
     */
    public function tipos()
    {
        $tipos = [
            ['value' => TablaSunat::TIPO_DOCUMENTO, 'label' => 'Tipos de Documento'],
            ['value' => TablaSunat::TIPO_AFECTACION, 'label' => 'Tipos de Afectación IGV'],
            ['value' => TablaSunat::UNIDAD_MEDIDA, 'label' => 'Unidades de Medida'],
            ['value' => TablaSunat::TIPO_MONEDA, 'label' => 'Tipos de Moneda'],
            ['value' => TablaSunat::TIPO_PAIS, 'label' => 'Países'],
            ['value' => TablaSunat::TIPO_COMPROBANTE, 'label' => 'Tipos de Comprobante'],
            ['value' => TablaSunat::TIPO_OPERACION, 'label' => 'Tipos de Operación'],
            ['value' => TablaSunat::TIPO_NOTA, 'label' => 'Tipos de Nota'],
        ];

        return response()->json([
            'data' => $tipos
        ]);
    }

    /**
     * Obtener catálogos por tipo (con caché)
     */
    public function porTipo(Request $request, string $tipo)
    {
        // Validar que el tipo existe
        $tiposValidos = [
            TablaSunat::TIPO_DOCUMENTO,
            TablaSunat::TIPO_AFECTACION,
            TablaSunat::UNIDAD_MEDIDA,
            TablaSunat::TIPO_MONEDA,
            TablaSunat::TIPO_PAIS,
            TablaSunat::TIPO_COMPROBANTE,
            TablaSunat::TIPO_OPERACION,
            TablaSunat::TIPO_NOTA,
        ];

        if (!in_array($tipo, $tiposValidos)) {
            return response()->json([
                'message' => 'Tipo de tabla no válido'
            ], 400);
        }

        $data = TablaSunat::porTipo($tipo)
            ->activos()
            ->orderBy('codigo')
            ->get();

        return response()->json([
            'data' => $data
        ]);
    }

    /**
     * Obtener catálogos específicos con caché
     */
    public function tiposDocumento()
    {
        return response()->json([
            'data' => TablaSunat::tiposDocumento()
        ]);
    }

    public function tiposAfectacion()
    {
        return response()->json([
            'data' => TablaSunat::tiposAfectacion()
        ]);
    }

    public function unidadesMedida()
    {
        return response()->json([
            'data' => TablaSunat::unidadesMedida()
        ]);
    }

    public function tiposMoneda()
    {
        return response()->json([
            'data' => TablaSunat::tiposMoneda()
        ]);
    }

    public function tiposComprobante()
    {
        return response()->json([
            'data' => TablaSunat::tiposComprobante()
        ]);
    }

    /**
     * Buscar por código
     */
    public function buscarPorCodigo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'codigo' => 'required|string',
            'tipo_tabla' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $tabla = TablaSunat::obtenerPorCodigo(
            $request->codigo,
            $request->tipo_tabla
        );

        if (!$tabla) {
            return response()->json([
                'message' => 'Código no encontrado'
            ], 404);
        }

        return response()->json([
            'data' => $tabla
        ]);
    }

    /**
     * Validar código
     */
    public function validarCodigo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'codigo' => 'required|string',
            'tipo_tabla' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $valido = TablaSunat::validarCodigo(
            $request->codigo,
            $request->tipo_tabla
        );

        return response()->json([
            'valido' => $valido,
            'message' => $valido ? 'Código válido' : 'Código inválido'
        ]);
    }

    /**
     * Activar/Desactivar tabla
     */
    public function toggleStatus(TablaSunat $tablaSunat)
    {
        try {
            $tablaSunat->activo = !$tablaSunat->activo;
            $tablaSunat->save();

            // Limpiar caché relacionado
            $this->clearCacheForTipo($tablaSunat->tipo_tabla);

            return response()->json([
                'message' => $tablaSunat->activo ? 'Tabla activada' : 'Tabla desactivada',
                'data' => $tablaSunat
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al cambiar el estado',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Limpiar toda la caché de tablas SUNAT
     */
    public function clearCache()
    {
        try {
            Cache::forget('sunat_tipos_documento');
            Cache::forget('sunat_tipos_afectacion');
            Cache::forget('sunat_unidades_medida');
            Cache::forget('sunat_tipos_moneda');
            Cache::forget('sunat_tipos_comprobante');

            return response()->json([
                'message' => 'Caché limpiada exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al limpiar la caché',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Importar tablas masivamente
     */
    public function importar(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tablas' => 'required|array',
            'tablas.*.codigo' => 'required|string|max:10',
            'tablas.*.descripcion' => 'required|string|max:255',
            'tablas.*.tipo_tabla' => 'required|string',
            'tablas.*.activo' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $importados = 0;
            $errores = [];

            foreach ($request->tablas as $index => $tablaData) {
                try {
                    // Verificar si ya existe
                    $existe = TablaSunat::where('codigo', $tablaData['codigo'])
                        ->where('tipo_tabla', $tablaData['tipo_tabla'])
                        ->first();

                    if ($existe) {
                        // Actualizar
                        $existe->update($tablaData);
                    } else {
                        // Crear
                        TablaSunat::create($tablaData);
                    }

                    $importados++;
                } catch (\Exception $e) {
                    $errores[] = [
                        'index' => $index,
                        'codigo' => $tablaData['codigo'],
                        'error' => $e->getMessage()
                    ];
                }
            }

            // Limpiar toda la caché
            $this->clearCache();

            return response()->json([
                'message' => "Se importaron {$importados} registros",
                'importados' => $importados,
                'errores' => $errores
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al importar tablas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Limpiar caché específico por tipo
     */
    private function clearCacheForTipo(string $tipo)
    {
        $cacheKeys = [
            TablaSunat::TIPO_DOCUMENTO => 'sunat_tipos_documento',
            TablaSunat::TIPO_AFECTACION => 'sunat_tipos_afectacion',
            TablaSunat::UNIDAD_MEDIDA => 'sunat_unidades_medida',
            TablaSunat::TIPO_MONEDA => 'sunat_tipos_moneda',
            TablaSunat::TIPO_COMPROBANTE => 'sunat_tipos_comprobante',
        ];

        if (isset($cacheKeys[$tipo])) {
            Cache::forget($cacheKeys[$tipo]);
        }
    }
}
