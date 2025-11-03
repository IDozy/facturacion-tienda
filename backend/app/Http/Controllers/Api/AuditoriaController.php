<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AuditoriaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Auditoria::with(['usuario']);

        // Filtros
        if ($request->has('tabla')) {
            $query->porTabla($request->tabla);
        }

        if ($request->has('accion')) {
            $query->porAccion($request->accion);
        }

        if ($request->has('usuario_id')) {
            $query->porUsuario($request->usuario_id);
        }

        if ($request->has('registro_id') && $request->has('tabla')) {
            $query->porRegistro($request->tabla, $request->registro_id);
        }

        if ($request->has('dias')) {
            $query->recientes($request->dias);
        }

        if ($request->has('fecha_desde') && $request->has('fecha_hasta')) {
            $query->whereBetween('created_at', [$request->fecha_desde, $request->fecha_hasta]);
        }

        if ($request->has('search')) {
            $query->search($request->search);
        }

        // Ordenamiento
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginación
        $perPage = $request->get('per_page', 50);
        $auditorias = $query->paginate($perPage);

        return response()->json($auditorias);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tabla' => 'required|string|max:100',
            'registro_id' => 'required|integer',
            'accion' => 'required|in:create,update,delete',
            'valores_anteriores' => 'nullable|array',
            'valores_nuevos' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $auditoria = Auditoria::registrar(
                $request->tabla,
                $request->registro_id,
                $request->accion,
                $request->valores_anteriores,
                $request->valores_nuevos
            );

            return response()->json([
                'message' => 'Auditoría registrada exitosamente',
                'data' => $auditoria->load('usuario')
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al registrar la auditoría',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Auditoria $auditoria)
    {
        return response()->json([
            'data' => $auditoria->load(['usuario']),
            'cambios' => $auditoria->cambios
        ]);
    }

    /**
     * Obtener auditorías por tabla y registro
     */
    public function porRegistro(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tabla' => 'required|string',
            'registro_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $auditorias = Auditoria::porRegistro($request->tabla, $request->registro_id)
            ->with(['usuario'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => $auditorias,
            'count' => $auditorias->count()
        ]);
    }

    /**
     * Obtener auditorías por tabla
     */
    public function porTabla($tabla, Request $request)
    {
        $query = Auditoria::porTabla($tabla)->with(['usuario']);

        if ($request->has('accion')) {
            $query->porAccion($request->accion);
        }

        if ($request->has('fecha_desde') && $request->has('fecha_hasta')) {
            $query->whereBetween('created_at', [$request->fecha_desde, $request->fecha_hasta]);
        }

        $auditorias = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 50));

        return response()->json($auditorias);
    }

    /**
     * Obtener auditorías por usuario
     */
    public function porUsuario($usuarioId, Request $request)
    {
        $query = Auditoria::porUsuario($usuarioId)->with(['usuario']);

        if ($request->has('tabla')) {
            $query->porTabla($request->tabla);
        }

        if ($request->has('accion')) {
            $query->porAccion($request->accion);
        }

        if ($request->has('fecha_desde') && $request->has('fecha_hasta')) {
            $query->whereBetween('created_at', [$request->fecha_desde, $request->fecha_hasta]);
        }

        $auditorias = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 50));

        return response()->json($auditorias);
    }

    /**
     * Obtener auditorías recientes
     */
    public function recientes(Request $request)
    {
        $dias = $request->get('dias', 7);
        
        $auditorias = Auditoria::recientes($dias)
            ->with(['usuario'])
            ->orderBy('created_at', 'desc')
            ->limit($request->get('limit', 100))
            ->get();

        return response()->json([
            'data' => $auditorias,
            'dias' => $dias
        ]);
    }

    /**
     * Obtener tablas auditadas
     */
    public function tablas()
    {
        $tablas = Auditoria::select('tabla')
            ->distinct()
            ->orderBy('tabla')
            ->pluck('tabla');

        return response()->json([
            'data' => $tablas
        ]);
    }

    /**
     * Obtener acciones disponibles
     */
    public function acciones()
    {
        $acciones = [
            ['valor' => 'create', 'nombre' => 'Creación'],
            ['valor' => 'update', 'nombre' => 'Actualización'],
            ['valor' => 'delete', 'nombre' => 'Eliminación'],
        ];

        return response()->json([
            'data' => $acciones
        ]);
    }

    /**
     * Estadísticas de auditoría
     */
    public function estadisticas(Request $request)
    {
        $query = Auditoria::query();

        if ($request->has('fecha_desde') && $request->has('fecha_hasta')) {
            $query->whereBetween('created_at', [$request->fecha_desde, $request->fecha_hasta]);
        } else {
            $query->recientes(30);
        }

        $totalRegistros = (clone $query)->count();
        $porAccion = (clone $query)->select('accion')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('accion')
            ->get();

        $porTabla = (clone $query)->select('tabla')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('tabla')
            ->orderBy('total', 'desc')
            ->limit(10)
            ->get();

        $porUsuario = (clone $query)->select('usuario_id')
            ->with('usuario:id,nombre')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('usuario_id')
            ->orderBy('total', 'desc')
            ->limit(10)
            ->get();

        $ultimasAcciones = (clone $query)
            ->with(['usuario'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'total_registros' => $totalRegistros,
            'por_accion' => $porAccion,
            'por_tabla' => $porTabla,
            'por_usuario' => $porUsuario,
            'ultimas_acciones' => $ultimasAcciones
        ]);
    }

    /**
     * Actividad del usuario
     */
    public function actividadUsuario($usuarioId, Request $request)
    {
        $query = Auditoria::porUsuario($usuarioId);

        if ($request->has('fecha_desde') && $request->has('fecha_hasta')) {
            $query->whereBetween('created_at', [$request->fecha_desde, $request->fecha_hasta]);
        } else {
            $query->recientes(30);
        }

        $totalAcciones = (clone $query)->count();
        $porAccion = (clone $query)->select('accion')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('accion')
            ->get();

        $porTabla = (clone $query)->select('tabla')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('tabla')
            ->orderBy('total', 'desc')
            ->get();

        $ultimasAcciones = (clone $query)
            ->with(['usuario'])
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        return response()->json([
            'usuario_id' => $usuarioId,
            'total_acciones' => $totalAcciones,
            'por_accion' => $porAccion,
            'por_tabla' => $porTabla,
            'ultimas_acciones' => $ultimasAcciones
        ]);
    }

    /**
     * Historial de un registro
     */
    public function historial(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tabla' => 'required|string',
            'registro_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $auditorias = Auditoria::porRegistro($request->tabla, $request->registro_id)
            ->with(['usuario'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($auditoria) {
                return [
                    'id' => $auditoria->id,
                    'accion' => $auditoria->accion,
                    'descripcion_accion' => $auditoria->descripcion_accion,
                    'usuario' => $auditoria->usuario->nombre ?? 'Sistema',
                    'fecha' => $auditoria->created_at,
                    'ip' => $auditoria->ip,
                    'cambios' => $auditoria->cambios,
                ];
            });

        return response()->json([
            'data' => $auditorias,
            'tabla' => $request->tabla,
            'registro_id' => $request->registro_id
        ]);
    }

    /**
     * Comparar dos versiones
     */
    public function comparar($auditoriaId1, $auditoriaId2)
    {
        $auditoria1 = Auditoria::with('usuario')->findOrFail($auditoriaId1);
        $auditoria2 = Auditoria::with('usuario')->findOrFail($auditoriaId2);

        if ($auditoria1->tabla !== $auditoria2->tabla || 
            $auditoria1->registro_id !== $auditoria2->registro_id) {
            return response()->json([
                'message' => 'Las auditorías deben ser del mismo registro'
            ], 400);
        }

        return response()->json([
            'version_1' => [
                'fecha' => $auditoria1->created_at,
                'usuario' => $auditoria1->usuario->nombre ?? 'Sistema',
                'datos' => $auditoria1->valores_nuevos ?? $auditoria1->valores_anteriores
            ],
            'version_2' => [
                'fecha' => $auditoria2->created_at,
                'usuario' => $auditoria2->usuario->nombre ?? 'Sistema',
                'datos' => $auditoria2->valores_nuevos ?? $auditoria2->valores_anteriores
            ]
        ]);
    }

    /**
     * Limpiar auditorías antiguas
     */
    public function limpiar(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'dias' => 'required|integer|min:30',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $fecha = now()->subDays($request->dias);
            $cantidad = Auditoria::where('created_at', '<', $fecha)->delete();

            return response()->json([
                'message' => "Se eliminaron {$cantidad} registros de auditoría",
                'cantidad' => $cantidad
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al limpiar auditorías',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}