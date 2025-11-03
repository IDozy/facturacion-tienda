<?php

namespace App\Http\Controllers\Api\Inventario;

use App\Http\Controllers\Controller;
use App\Models\Inventario\AjusteInventario;
use App\Models\Inventario\MovimientoStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AjusteInventarioController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = AjusteInventario::with(['almacen', 'usuario']);

        // Filtros
        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->has('tipo_ajuste')) {
            $query->where('tipo_ajuste', $request->tipo_ajuste);
        }

        if ($request->has('almacen_id')) {
            $query->where('almacen_id', $request->almacen_id);
        }

        if ($request->has('usuario_id')) {
            $query->where('usuario_id', $request->usuario_id);
        }

        if ($request->has('año') && $request->has('mes')) {
            $query->delMes($request->año, $request->mes);
        }

        if ($request->has('fecha_desde') && $request->has('fecha_hasta')) {
            $query->whereBetween('fecha_ajuste', [$request->fecha_desde, $request->fecha_hasta]);
        }

        // Ordenamiento
        $sortBy = $request->get('sort_by', 'fecha_ajuste');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginación
        $perPage = $request->get('per_page', 15);
        $ajustes = $query->paginate($perPage);

        return response()->json($ajustes);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'almacen_id' => 'required|exists:almacenes,id',
            'tipo_ajuste' => 'required|in:merma,sobrante,conteo_fisico,otro',
            'observacion' => 'nullable|string',
            'fecha_ajuste' => 'nullable|date',
            'detalles' => 'required|array|min:1',
            'detalles.*.producto_id' => 'required|exists:productos,id',
            'detalles.*.cantidad' => 'required|numeric|min:0.001',
            'detalles.*.tipo' => 'required|in:entrada,salida',
            'detalles.*.costo_unitario' => 'nullable|numeric|min:0',
            'detalles.*.observacion' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Crear ajuste
            $ajuste = AjusteInventario::create([
                'almacen_id' => $request->almacen_id,
                'tipo_ajuste' => $request->tipo_ajuste,
                'observacion' => $request->observacion,
                'fecha_ajuste' => $request->fecha_ajuste ?? now(),
                'usuario_id' => Auth::id(),
            ]);

            // Crear movimientos
            foreach ($request->detalles as $detalle) {
                $producto = \App\Models\Inventario\Producto::find($detalle['producto_id']);
                
                $ajuste->movimientosStock()->create([
                    'producto_id' => $detalle['producto_id'],
                    'almacen_id' => $request->almacen_id,
                    'tipo' => $detalle['tipo'],
                    'cantidad' => $detalle['cantidad'],
                    'costo_unitario' => $detalle['costo_unitario'] ?? $producto->precio_compra,
                    'referencia_tipo' => AjusteInventario::class,
                    'referencia_id' => $ajuste->id,
                    'observacion' => $detalle['observacion'] ?? $request->observacion,
                ]);
            }

            // Aplicar automáticamente si se solicita
            if ($request->get('aplicar', false)) {
                $ajuste->aplicar();
            }

            DB::commit();

            return response()->json([
                'message' => 'Ajuste de inventario creado exitosamente',
                'data' => $ajuste->load(['almacen', 'usuario', 'movimientosStock.producto'])
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al crear el ajuste de inventario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(AjusteInventario $ajusteInventario)
    {
        return response()->json([
            'data' => $ajusteInventario->load([
                'almacen',
                'usuario',
                'movimientosStock.producto'
            ]),
            'cantidad_productos' => $ajusteInventario->cantidad_productos,
            'descripcion_tipo' => $ajusteInventario->descripcion_tipo
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, AjusteInventario $ajusteInventario)
    {
        if (!$ajusteInventario->esPendiente()) {
            return response()->json([
                'message' => 'Solo se pueden editar ajustes pendientes'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'tipo_ajuste' => 'sometimes|required|in:merma,sobrante,conteo_fisico,otro',
            'observacion' => 'nullable|string',
            'fecha_ajuste' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $ajusteInventario->update($request->only(['tipo_ajuste', 'observacion', 'fecha_ajuste']));

            return response()->json([
                'message' => 'Ajuste actualizado exitosamente',
                'data' => $ajusteInventario->fresh(['almacen', 'usuario'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar el ajuste',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AjusteInventario $ajusteInventario)
    {
        if (!$ajusteInventario->esPendiente()) {
            return response()->json([
                'message' => 'Solo se pueden eliminar ajustes pendientes'
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Eliminar movimientos asociados
            $ajusteInventario->movimientosStock()->delete();
            $ajusteInventario->delete();

            DB::commit();

            return response()->json([
                'message' => 'Ajuste eliminado exitosamente'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al eliminar el ajuste',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Aplicar ajuste
     */
    public function aplicar(AjusteInventario $ajusteInventario)
    {
        if ($ajusteInventario->esAplicado()) {
            return response()->json([
                'message' => 'El ajuste ya está aplicado'
            ], 400);
        }

        if ($ajusteInventario->esAnulado()) {
            return response()->json([
                'message' => 'No se puede aplicar un ajuste anulado'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $ajusteInventario->aplicar();

            DB::commit();

            return response()->json([
                'message' => 'Ajuste aplicado exitosamente',
                'data' => $ajusteInventario->fresh()
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al aplicar el ajuste',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Anular ajuste
     */
    public function anular(AjusteInventario $ajusteInventario)
    {
        if (!$ajusteInventario->esAplicado()) {
            return response()->json([
                'message' => 'Solo se pueden anular ajustes aplicados'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $ajusteInventario->anular();

            DB::commit();

            return response()->json([
                'message' => 'Ajuste anulado exitosamente',
                'data' => $ajusteInventario->fresh()
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al anular el ajuste',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener movimientos del ajuste
     */
    public function movimientos(AjusteInventario $ajusteInventario)
    {
        $movimientos = $ajusteInventario->movimientosStock()
            ->with(['producto'])
            ->get()
            ->map(function ($mov) {
                return [
                    'id' => $mov->id,
                    'producto' => [
                        'id' => $mov->producto_id,
                        'codigo' => $mov->producto->codigo,
                        'nombre' => $mov->producto->nombre,
                    ],
                    'tipo' => $mov->tipo,
                    'cantidad' => $mov->cantidad,
                    'costo_unitario' => $mov->costo_unitario,
                    'costo_total' => $mov->costo_total,
                    'observacion' => $mov->observacion,
                ];
            });

        $totalEntradas = $movimientos->where('tipo', 'entrada')->sum('cantidad');
        $totalSalidas = $movimientos->where('tipo', 'salida')->sum('cantidad');
        $valorTotal = $movimientos->sum('costo_total');

        return response()->json([
            'data' => $movimientos,
            'resumen' => [
                'total_entradas' => $totalEntradas,
                'total_salidas' => $totalSalidas,
                'valor_total' => $valorTotal,
                'cantidad_productos' => $movimientos->count()
            ]
        ]);
    }

    /**
     * Estadísticas de ajustes
     */
    public function estadisticas(Request $request)
    {
        $query = AjusteInventario::query();

        if ($request->has('fecha_desde') && $request->has('fecha_hasta')) {
            $query->whereBetween('fecha_ajuste', [$request->fecha_desde, $request->fecha_hasta]);
        }

        if ($request->has('almacen_id')) {
            $query->where('almacen_id', $request->almacen_id);
        }

        $total = (clone $query)->count();
        $pendientes = (clone $query)->pendientes()->count();
        $aplicados = (clone $query)->aplicados()->count();
        $anulados = (clone $query)->where('estado', 'anulado')->count();

        $porTipo = (clone $query)->select('tipo_ajuste')
            ->selectRaw('COUNT(*) as cantidad')
            ->groupBy('tipo_ajuste')
            ->get();

        $porAlmacen = (clone $query)->select('almacen_id')
            ->with('almacen')
            ->selectRaw('COUNT(*) as cantidad')
            ->groupBy('almacen_id')
            ->orderBy('cantidad', 'desc')
            ->get();

        return response()->json([
            'total' => $total,
            'pendientes' => $pendientes,
            'aplicados' => $aplicados,
            'anulados' => $anulados,
            'por_tipo' => $porTipo,
            'por_almacen' => $porAlmacen
        ]);
    }

    /**
     * Ajustes del mes
     */
    public function delMes(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'año' => 'required|integer|min:2000|max:2100',
            'mes' => 'required|integer|min:1|max:12',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $ajustes = AjusteInventario::delMes($request->año, $request->mes)
            ->with(['almacen', 'usuario'])
            ->orderBy('fecha_ajuste', 'desc')
            ->get();

        return response()->json([
            'data' => $ajustes,
            'año' => $request->año,
            'mes' => $request->mes,
            'count' => $ajustes->count()
        ]);
    }

    /**
     * Ajustes por almacén
     */
    public function porAlmacen($almacenId, Request $request)
    {
        $query = AjusteInventario::where('almacen_id', $almacenId)
            ->with(['usuario', 'movimientosStock']);

        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->has('fecha_desde') && $request->has('fecha_hasta')) {
            $query->whereBetween('fecha_ajuste', [$request->fecha_desde, $request->fecha_hasta]);
        }

        $ajustes = $query->orderBy('fecha_ajuste', 'desc')->get();

        return response()->json([
            'data' => $ajustes,
            'almacen_id' => $almacenId,
            'count' => $ajustes->count()
        ]);
    }

    /**
     * Tipos de ajuste disponibles
     */
    public function tipos()
    {
        $tipos = [
            ['value' => 'merma', 'label' => 'Ajuste por Merma'],
            ['value' => 'sobrante', 'label' => 'Ajuste por Sobrante'],
            ['value' => 'conteo_fisico', 'label' => 'Conteo Físico'],
            ['value' => 'otro', 'label' => 'Otro Ajuste'],
        ];

        return response()->json([
            'data' => $tipos
        ]);
    }

    /**
     * Resumen de ajustes
     */
    public function resumen(Request $request)
    {
        $query = AjusteInventario::with(['almacen', 'movimientosStock']);

        if ($request->has('fecha_desde') && $request->has('fecha_hasta')) {
            $query->whereBetween('fecha_ajuste', [$request->fecha_desde, $request->fecha_hasta]);
        }

        $ajustes = $query->get();

        $totalEntradas = 0;
        $totalSalidas = 0;
        $valorTotalEntradas = 0;
        $valorTotalSalidas = 0;

        foreach ($ajustes as $ajuste) {
            foreach ($ajuste->movimientosStock as $mov) {
                if ($mov->tipo === 'entrada') {
                    $totalEntradas += $mov->cantidad;
                    $valorTotalEntradas += $mov->costo_total;
                } else {
                    $totalSalidas += $mov->cantidad;
                    $valorTotalSalidas += $mov->costo_total;
                }
            }
        }

        return response()->json([
            'total_ajustes' => $ajustes->count(),
            'entradas' => [
                'cantidad' => $totalEntradas,
                'valor' => $valorTotalEntradas
            ],
            'salidas' => [
                'cantidad' => $totalSalidas,
                'valor' => $valorTotalSalidas
            ],
            'diferencia' => [
                'cantidad' => $totalEntradas - $totalSalidas,
                'valor' => $valorTotalEntradas - $valorTotalSalidas
            ]
        ]);
    }
}