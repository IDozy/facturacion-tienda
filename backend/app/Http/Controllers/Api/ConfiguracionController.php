<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Configuracion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ConfiguracionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Configuracion::with(['empresa']);

        // Filtros
        if ($request->has('empresa_id')) {
            $query->where('empresa_id', $request->empresa_id);
        }

        if ($request->has('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->has('clave')) {
            $query->where('clave', 'like', "%{$request->clave}%");
        }

        // Ordenamiento
        $sortBy = $request->get('sort_by', 'clave');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginación
        $perPage = $request->get('per_page', 50);
        
        if ($request->get('all') === 'true') {
            $configuraciones = $query->get();
            return response()->json(['data' => $configuraciones]);
        }

        $configuraciones = $query->paginate($perPage);

        return response()->json($configuraciones);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'clave' => 'required|string|max:255',
            'valor' => 'required',
            'tipo' => 'required|in:texto,numero,booleano,json,array',
            'empresa_id' => 'nullable|exists:empresas,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        // Verificar que no exista la misma clave para la misma empresa
        $existe = Configuracion::where('clave', $request->clave)
            ->where('empresa_id', $request->empresa_id)
            ->exists();

        if ($existe) {
            return response()->json([
                'message' => 'Ya existe una configuración con esta clave para esta empresa'
            ], 422);
        }

        try {
            $configuracion = Configuracion::create($request->all());

            return response()->json([
                'message' => 'Configuración creada exitosamente',
                'data' => $configuracion->load('empresa')
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear la configuración',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Configuracion $configuracion)
    {
        return response()->json([
            'data' => $configuracion->load(['empresa'])
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Configuracion $configuracion)
    {
        $validator = Validator::make($request->all(), [
            'clave' => 'sometimes|required|string|max:255',
            'valor' => 'sometimes|required',
            'tipo' => 'sometimes|required|in:texto,numero,booleano,json,array',
            'empresa_id' => 'nullable|exists:empresas,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $configuracion->update($request->all());

            return response()->json([
                'message' => 'Configuración actualizada exitosamente',
                'data' => $configuracion->fresh(['empresa'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar la configuración',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Configuracion $configuracion)
    {
        try {
            $configuracion->delete();

            return response()->json([
                'message' => 'Configuración eliminada exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar la configuración',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener configuración por clave
     */
    public function obtenerPorClave(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'clave' => 'required|string',
            'empresa_id' => 'nullable|integer',
            'por_defecto' => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $valor = Configuracion::obtener(
            $request->clave,
            $request->empresa_id,
            $request->por_defecto
        );

        return response()->json([
            'clave' => $request->clave,
            'valor' => $valor
        ]);
    }

    /**
     * Establecer configuración
     */
    public function establecer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'clave' => 'required|string|max:255',
            'valor' => 'required',
            'empresa_id' => 'nullable|exists:empresas,id',
            'tipo' => 'required|in:texto,numero,booleano,json,array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $configuracion = Configuracion::establecer(
                $request->clave,
                $request->valor,
                $request->empresa_id,
                $request->tipo
            );

            return response()->json([
                'message' => 'Configuración establecida exitosamente',
                'data' => $configuracion
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al establecer la configuración',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener todas las configuraciones de una empresa
     */
    public function porEmpresa($empresaId)
    {
        $configuraciones = Configuracion::where('empresa_id', $empresaId)
            ->orderBy('clave')
            ->get();

        return response()->json([
            'data' => $configuraciones,
            'count' => $configuraciones->count()
        ]);
    }

    /**
     * Obtener configuraciones globales (sin empresa)
     */
    public function globales()
    {
        $configuraciones = Configuracion::whereNull('empresa_id')
            ->orderBy('clave')
            ->get();

        return response()->json([
            'data' => $configuraciones
        ]);
    }

    /**
     * Obtener configuraciones por tipo
     */
    public function porTipo($tipo)
    {
        if (!in_array($tipo, ['texto', 'numero', 'booleano', 'json', 'array'])) {
            return response()->json([
                'message' => 'Tipo de configuración no válido'
            ], 400);
        }

        $configuraciones = Configuracion::where('tipo', $tipo)
            ->with(['empresa'])
            ->get();

        return response()->json([
            'data' => $configuraciones
        ]);
    }

    /**
     * Actualizar múltiples configuraciones
     */
    public function actualizarMultiples(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'configuraciones' => 'required|array',
            'configuraciones.*.clave' => 'required|string',
            'configuraciones.*.valor' => 'required',
            'configuraciones.*.tipo' => 'required|in:texto,numero,booleano,json,array',
            'empresa_id' => 'nullable|exists:empresas,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $actualizadas = [];
            
            foreach ($request->configuraciones as $config) {
                $configuracion = Configuracion::establecer(
                    $config['clave'],
                    $config['valor'],
                    $request->empresa_id,
                    $config['tipo']
                );
                $actualizadas[] = $configuracion;
            }

            return response()->json([
                'message' => 'Configuraciones actualizadas exitosamente',
                'data' => $actualizadas,
                'count' => count($actualizadas)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar las configuraciones',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar todas las configuraciones de una empresa
     */
    public function eliminarPorEmpresa($empresaId)
    {
        try {
            $count = Configuracion::where('empresa_id', $empresaId)->delete();

            return response()->json([
                'message' => "Se eliminaron {$count} configuraciones",
                'count' => $count
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar las configuraciones',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Buscar configuraciones
     */
    public function buscar(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'search' => 'required|string|min:2',
            'empresa_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $query = Configuracion::where('clave', 'like', "%{$request->search}%");

        if ($request->has('empresa_id')) {
            $query->where('empresa_id', $request->empresa_id);
        }

        $configuraciones = $query->with(['empresa'])->get();

        return response()->json([
            'data' => $configuraciones
        ]);
    }

    /**
     * Exportar configuraciones
     */
    public function exportar(Request $request)
    {
        $query = Configuracion::query();

        if ($request->has('empresa_id')) {
            $query->where('empresa_id', $request->empresa_id);
        }

        $configuraciones = $query->get()->map(function ($config) {
            return [
                'clave' => $config->clave,
                'valor' => $config->valor,
                'tipo' => $config->tipo,
            ];
        });

        return response()->json([
            'data' => $configuraciones,
            'count' => $configuraciones->count()
        ]);
    }

    /**
     * Importar configuraciones
     */
    public function importar(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'configuraciones' => 'required|array',
            'configuraciones.*.clave' => 'required|string',
            'configuraciones.*.valor' => 'required',
            'configuraciones.*.tipo' => 'required|in:texto,numero,booleano,json,array',
            'empresa_id' => 'nullable|exists:empresas,id',
            'sobrescribir' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $importadas = 0;
            $omitidas = 0;
            $sobrescribir = $request->get('sobrescribir', false);

            foreach ($request->configuraciones as $config) {
                $existe = Configuracion::where('clave', $config['clave'])
                    ->where('empresa_id', $request->empresa_id)
                    ->exists();

                if ($existe && !$sobrescribir) {
                    $omitidas++;
                    continue;
                }

                Configuracion::establecer(
                    $config['clave'],
                    $config['valor'],
                    $request->empresa_id,
                    $config['tipo']
                );
                $importadas++;
            }

            return response()->json([
                'message' => "Importación completada: {$importadas} importadas, {$omitidas} omitidas",
                'importadas' => $importadas,
                'omitidas' => $omitidas
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al importar las configuraciones',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}