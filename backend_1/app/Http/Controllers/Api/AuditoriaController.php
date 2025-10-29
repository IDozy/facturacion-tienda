<?php
// app/Http/Controllers/Api/Auditoria/AuditoriaController.php
namespace App\Http\Controllers\Api\Auditoria;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AuditoriaController extends Controller
{
    /**
     * Listar todas las auditorías
     * GET /api/auditorias
     */
    public function index(Request $request): JsonResponse
    {
        $query = Auditoria::query();

        // Filtrar por modelo
        if ($request->has('modelo')) {
            $query->where('modelo', $request->input('modelo'));
        }

        // Filtrar por acción
        if ($request->has('accion')) {
            $query->where('accion', $request->input('accion'));
        }

        // Filtrar por usuario
        if ($request->has('usuario_id')) {
            $query->where('usuario_id', $request->input('usuario_id'));
        }

        // Filtrar por rango de fechas
        if ($request->has('fecha_desde')) {
            $query->whereDate('created_at', '>=', $request->input('fecha_desde'));
        }

        if ($request->has('fecha_hasta')) {
            $query->whereDate('created_at', '<=', $request->input('fecha_hasta'));
        }

        $auditorias = $query->with('usuario')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $auditorias,
        ]);
    }

    /**
     * Ver detalle de una auditoría
     * GET /api/auditorias/{id}
     */
    public function show(string $id): JsonResponse
    {
        $auditoria = Auditoria::with('usuario')->find($id);

        if (!$auditoria) {
            return response()->json([
                'success' => false,
                'message' => 'Auditoría no encontrada'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $auditoria,
        ]);
    }

    /**
     * Historial de cambios de un modelo específico
     * GET /api/auditorias/historial/{modelo}/{id}
     */
    public function historialModelo(string $modelo, string $modeloId): JsonResponse
    {
        $auditorias = Auditoria::where('modelo', $modelo)
            ->where('modelo_id', $modeloId)
            ->with('usuario')
            ->orderBy('created_at', 'desc')
            ->get();

        if ($auditorias->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No hay registros de auditoría para este modelo'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $auditorias,
            'message' => 'Historial obtenido correctamente'
        ]);
    }

    /**
     * Auditorías por usuario
     * GET /api/auditorias/usuario/{usuario_id}
     */
    public function porUsuario(string $usuarioId): JsonResponse
    {
        $auditorias = Auditoria::where('usuario_id', $usuarioId)
            ->with('usuario')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $auditorias,
        ]);
    }

    /**
     * Auditorías por tipo de acción
     * GET /api/auditorias/accion/{accion}
     */
    public function porAccion(string $accion): JsonResponse
    {
        $acciones = ['created', 'updated', 'deleted'];

        if (!in_array($accion, $acciones)) {
            return response()->json([
                'success' => false,
                'message' => 'Acción inválida. Debe ser: created, updated o deleted'
            ], 400);
        }

        $auditorias = Auditoria::where('accion', $accion)
            ->with('usuario')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $auditorias,
        ]);
    }

    /**
     * Estadísticas de auditoría
     * GET /api/auditorias/estadisticas
     */
    public function estadisticas(): JsonResponse
    {
        $total = Auditoria::count();
        $hoy = Auditoria::whereDate('created_at', today())->count();
        $estaSemana = Auditoria::whereDate('created_at', '>=', today()->subDays(7))->count();
        
        $porAccion = Auditoria::groupBy('accion')
            ->selectRaw('accion, count(*) as total')
            ->get()
            ->pluck('total', 'accion');

        $porModelo = Auditoria::groupBy('modelo')
            ->selectRaw('modelo, count(*) as total')
            ->get()
            ->pluck('total', 'modelo');

        $usuariosMasActivos = Auditoria::selectRaw('usuario_id, count(*) as total')
            ->with('usuario')
            ->groupBy('usuario_id')
            ->orderBy('total', 'desc')
            ->limit(5)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $total,
                'hoy' => $hoy,
                'esta_semana' => $estaSemana,
                'por_accion' => $porAccion,
                'por_modelo' => $porModelo,
                'usuarios_mas_activos' => $usuariosMasActivos,
            ],
        ]);
    }

    /**
     * Exportar auditorías a CSV
     * GET /api/auditorias/exportar/csv
     */
    public function exportarCsv(Request $request)
    {
        $auditorias = Auditoria::with('usuario')
            ->orderBy('created_at', 'desc')
            ->get();

        $csv = fopen('php://output', 'w');
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="auditorias.csv"');

        // Encabezados
        fputcsv($csv, ['ID', 'Usuario', 'Modelo', 'Modelo ID', 'Acción', 'IP', 'User Agent', 'Cambios', 'Fecha']);

        // Datos
        foreach ($auditorias as $auditoria) {
            fputcsv($csv, [
                $auditoria->id,
                $auditoria->usuario?->nombre ?? 'N/A',
                $auditoria->modelo,
                $auditoria->modelo_id,
                $auditoria->accion,
                $auditoria->ip,
                $auditoria->user_agent,
                json_encode($auditoria->cambios),
                $auditoria->created_at->format('Y-m-d H:i:s'),
            ]);
        }

        fclose($csv);
        exit;
    }

    /**
     * Limpiar auditorías antiguas
     * DELETE /api/auditorias/limpiar
     */
    public function limpiar(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'dias' => 'required|integer|min:1',
        ]);

        $fecha = now()->subDays($validated['dias']);
        $eliminadas = Auditoria::where('created_at', '<', $fecha)->delete();

        return response()->json([
            'success' => true,
            'message' => "Se eliminaron $eliminadas registros de auditoría anteriores a $validated[dias] días",
            'registros_eliminados' => $eliminadas,
        ]);
    }
}