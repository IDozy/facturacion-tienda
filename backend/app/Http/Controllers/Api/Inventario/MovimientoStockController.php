<?php

namespace App\Http\Controllers\Api\Inventario;

use App\Http\Controllers\Controller;
use App\Models\Inventario\MovimientoStock;
use App\Models\Inventario\AlmacenProducto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MovimientoStockController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = MovimientoStock::with(['producto', 'almacen', 'referencia']);

        // Filtros
        if ($request->has('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->has('producto_id')) {
            $query->where('producto_id', $request->producto_id);
        }

        if ($request->has('almacen_id')) {
            $query->where('almacen_id', $request->almacen_id);
        }

        if ($request->has('referencia_tipo')) {
            $query->where('referencia_tipo', $request->referencia_tipo);
        }

        if ($request->has('fecha_desde') && $request->has('fecha_hasta')) {
            $query->delPeriodo($request->fecha_desde, $request->fecha_hasta);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('producto', function ($q2) use ($search) {
                    $q2->where('codigo', 'like', "%{$search}%")
                        ->orWhere('nombre', 'like', "%{$search}%");
                });
            });
        }

        // Ordenamiento
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginación
        $perPage = $request->get('per_page', 50);
        $movimientos = $query->paginate($perPage);

        return response()->json($movimientos);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'producto_id' => 'required|exists:productos,id',
            'almacen_id' => 'required|exists:almacenes,id',
            'tipo' => 'required|in:entrada,salida,transferencia',
            'cantidad' => 'required|numeric|min:0.001',
            'costo_unitario' => 'required|numeric|min:0',
            'referencia_tipo' => 'nullable|string',
            'referencia_id' => 'nullable|integer',
            'observacion' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        // Validar stock disponible para salidas
        if ($request->tipo === 'salida') {
            $stockDisponible = AlmacenProducto::getStock($request->almacen_id, $request->producto_id);
            if ($stockDisponible < $request->cantidad) {
                return response()->json([
                    'message' => 'Stock insuficiente',
                    'stock_disponible' => $stockDisponible,
                    'cantidad_solicitada' => $request->cantidad
                ], 400);
            }
        }

        DB::beginTransaction();
        try {
            // Crear movimiento
            $movimiento = MovimientoStock::create($request->all());

            // Actualizar stock
            $almacenProducto = AlmacenProducto::firstOrCreate(
                [
                    'almacen_id' => $request->almacen_id,
                    'producto_id' => $request->producto_id
                ],
                ['stock_actual' => 0]
            );

            if ($request->tipo === 'entrada') {
                $almacenProducto->sumarStock($request->cantidad);
            } elseif ($request->tipo === 'salida') {
                $almacenProducto->restarStock($request->cantidad);
            }

            DB::commit();

            return response()->json([
                'message' => 'Movimiento registrado exitosamente',
                'data' => $movimiento->load(['producto', 'almacen'])
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al registrar el movimiento',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(MovimientoStock $movimientoStock)
    {
        return response()->json([
            'data' => $movimientoStock->load(['producto', 'almacen', 'referencia'])
        ]);
    }

    /**
     * Obtener movimientos por producto
     */
    public function porProducto($productoId, Request $request)
    {
        $query = MovimientoStock::where('producto_id', $productoId)
            ->with(['almacen', 'referencia']);

        if ($request->has('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->has('almacen_id')) {
            $query->where('almacen_id', $request->almacen_id);
        }

        if ($request->has('fecha_desde') && $request->has('fecha_hasta')) {
            $query->delPeriodo($request->fecha_desde, $request->fecha_hasta);
        }

        $movimientos = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 50));

        $totalEntradas = MovimientoStock::where('producto_id', $productoId)
            ->entradas()
            ->sum('cantidad');

        $totalSalidas = MovimientoStock::where('producto_id', $productoId)
            ->salidas()
            ->sum('cantidad');

        return response()->json([
            'data' => $movimientos,
            'resumen' => [
                'total_entradas' => $totalEntradas,
                'total_salidas' => $totalSalidas,
                'diferencia' => $totalEntradas - $totalSalidas
            ]
        ]);
    }

    /**
     * Obtener movimientos por almacén
     */
    public function porAlmacen($almacenId, Request $request)
    {
        $query = MovimientoStock::where('almacen_id', $almacenId)
            ->with(['producto', 'referencia']);

        if ($request->has('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->has('fecha_desde') && $request->has('fecha_hasta')) {
            $query->delPeriodo($request->fecha_desde, $request->fecha_hasta);
        }

        $movimientos = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 50));

        return response()->json($movimientos);
    }

    /**
     * Obtener entradas
     */
    public function entradas(Request $request)
    {
        $query = MovimientoStock::entradas()
            ->with(['producto', 'almacen', 'referencia']);

        if ($request->has('almacen_id')) {
            $query->where('almacen_id', $request->almacen_id);
        }

        if ($request->has('fecha_desde') && $request->has('fecha_hasta')) {
            $query->delPeriodo($request->fecha_desde, $request->fecha_hasta);
        }

        $movimientos = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 50));

        return response()->json($movimientos);
    }

    /**
     * Obtener salidas
     */
    public function salidas(Request $request)
    {
        $query = MovimientoStock::salidas()
            ->with(['producto', 'almacen', 'referencia']);

        if ($request->has('almacen_id')) {
            $query->where('almacen_id', $request->almacen_id);
        }

        if ($request->has('fecha_desde') && $request->has('fecha_hasta')) {
            $query->delPeriodo($request->fecha_desde, $request->fecha_hasta);
        }

        $movimientos = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 50));

        return response()->json($movimientos);
    }

    /**
     * Estadísticas de movimientos
     */
    public function estadisticas(Request $request)
    {
        $query = MovimientoStock::query();

        if ($request->has('fecha_desde') && $request->has('fecha_hasta')) {
            $query->delPeriodo($request->fecha_desde, $request->fecha_hasta);
        }

        $totalEntradas = (clone $query)->entradas()->sum('cantidad');
        $totalSalidas = (clone $query)->salidas()->sum('cantidad');
        $cantidadEntradas = (clone $query)->entradas()->count();
        $cantidadSalidas = (clone $query)->salidas()->count();

        $valorEntradas = (clone $query)->entradas()->sum(DB::raw('cantidad * costo_unitario'));
        $valorSalidas = (clone $query)->salidas()->sum(DB::raw('cantidad * costo_unitario'));

        $porTipo = MovimientoStock::select('tipo')
            ->selectRaw('COUNT(*) as cantidad')
            ->selectRaw('SUM(cantidad) as total_cantidad')
            ->selectRaw('SUM(cantidad * costo_unitario) as valor_total')
            ->groupBy('tipo')
            ->get();

        $topProductosEntradas = (clone $query)->entradas()
            ->select('producto_id')
            ->with('producto')
            ->selectRaw('SUM(cantidad) as total')
            ->groupBy('producto_id')
            ->orderBy('total', 'desc')
            ->limit(10)
            ->get();

        $topProductosSalidas = (clone $query)->salidas()
            ->select('producto_id')
            ->with('producto')
            ->selectRaw('SUM(cantidad) as total')
            ->groupBy('producto_id')
            ->orderBy('total', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'entradas' => [
                'total_cantidad' => $totalEntradas,
                'cantidad_movimientos' => $cantidadEntradas,
                'valor_total' => $valorEntradas
            ],
            'salidas' => [
                'total_cantidad' => $totalSalidas,
                'cantidad_movimientos' => $cantidadSalidas,
                'valor_total' => $valorSalidas
            ],
            'por_tipo' => $porTipo,
            'top_productos_entradas' => $topProductosEntradas,
            'top_productos_salidas' => $topProductosSalidas
        ]);
    }

    /**
     * Kardex de producto
     */
    public function kardex($productoId, Request $request)
    {
        $query = MovimientoStock::where('producto_id', $productoId)
            ->with(['almacen', 'referencia']);

        if ($request->has('almacen_id')) {
            $query->where('almacen_id', $request->almacen_id);
        }

        if ($request->has('fecha_desde') && $request->has('fecha_hasta')) {
            $query->delPeriodo($request->fecha_desde, $request->fecha_hasta);
        }

        $movimientos = $query->orderBy('created_at', 'asc')->get();

        $saldoInicial = 0;
        $kardex = [];
        $saldo = $saldoInicial;

        foreach ($movimientos as $movimiento) {
            $entrada = $movimiento->esEntrada() ? $movimiento->cantidad : 0;
            $salida = $movimiento->esSalida() ? $movimiento->cantidad : 0;
            $saldo += $entrada - $salida;

            $kardex[] = [
                'fecha' => $movimiento->created_at,
                'tipo' => $movimiento->tipo,
                'descripcion_tipo' => $movimiento->descripcion_tipo,
                'referencia' => $movimiento->referencia_tipo,
                'almacen' => $movimiento->almacen->nombre,
                'entrada' => $entrada,
                'salida' => $salida,
                'saldo' => $saldo,
                'costo_unitario' => $movimiento->costo_unitario,
                'costo_total' => $movimiento->costo_total,
            ];
        }

        return response()->json([
            'producto_id' => $productoId,
            'saldo_inicial' => $saldoInicial,
            'saldo_final' => $saldo,
            'kardex' => $kardex
        ]);
    }

    /**
     * Resumen por almacén y tipo
     */
    public function resumenPorAlmacen(Request $request)
    {
        $query = MovimientoStock::query();

        if ($request->has('fecha_desde') && $request->has('fecha_hasta')) {
            $query->delPeriodo($request->fecha_desde, $request->fecha_hasta);
        }

        $resumen = $query->select('almacen_id', 'tipo')
            ->with('almacen')
            ->selectRaw('COUNT(*) as cantidad_movimientos')
            ->selectRaw('SUM(cantidad) as total_cantidad')
            ->selectRaw('SUM(cantidad * costo_unitario) as valor_total')
            ->groupBy('almacen_id', 'tipo')
            ->get()
            ->groupBy('almacen_id')
            ->map(function ($movs, $almacenId) {
                return [
                    'almacen' => $movs->first()->almacen,
                    'movimientos' => $movs->map(function ($mov) {
                        return [
                            'tipo' => $mov->tipo,
                            'cantidad_movimientos' => $mov->cantidad_movimientos,
                            'total_cantidad' => $mov->total_cantidad,
                            'valor_total' => $mov->valor_total,
                        ];
                    })
                ];
            })->values();

        return response()->json([
            'data' => $resumen
        ]);
    }

    /**
     * Movimientos recientes
     */
    public function recientes(Request $request)
    {
        $limit = $request->get('limit', 50);

        $movimientos = MovimientoStock::with(['producto', 'almacen', 'referencia'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'data' => $movimientos
        ]);
    }

    /**
     * Valorización de inventario
     */
    public function valorizacion(Request $request)
    {
        $almacenId = $request->get('almacen_id');

        $query = AlmacenProducto::with(['producto', 'almacen']);

        if ($almacenId) {
            $query->where('almacen_id', $almacenId);
        }

        $items = $query->where('stock_actual', '>', 0)->get();

        $valorizacion = $items->map(function ($item) {
            $costoPromedio = $item->producto->precio_promedio;
            $valorTotal = $item->stock_actual * $costoPromedio;

            return [
                'producto_id' => $item->producto_id,
                'codigo' => $item->producto->codigo,
                'nombre' => $item->producto->nombre,
                'almacen' => $item->almacen->nombre,
                'stock_actual' => $item->stock_actual,
                'costo_promedio' => $costoPromedio,
                'valor_total' => $valorTotal,
            ];
        });

        $valorTotalInventario = $valorizacion->sum('valor_total');

        return response()->json([
            'data' => $valorizacion,
            'valor_total_inventario' => $valorTotalInventario
        ]);
    }
}