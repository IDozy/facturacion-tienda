<?php

namespace App\Http\Controllers\Api\Compras;

use App\Http\Controllers\Controller;
use App\Models\Compras\Compra;
use App\Models\Compras\CompraDetalle;
use App\Models\Compras\Proveedor;
use App\Models\Inventario\Producto;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class CompraController extends Controller
{
    /**
     * Listar compras con filtros y paginación
     * GET /api/compras
     */
    public function index(Request $request): JsonResponse
    {
        $query = Compra::with(['proveedor', 'usuario', 'detalles'])
            ->orderBy('fecha_compra', 'desc')
            ->orderBy('numero_comprobante', 'desc');

        if ($request->filled('proveedor_id')) {
            $query->where('proveedor_id', $request->proveedor_id);
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('fecha_desde')) {
            $query->where('fecha_compra', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->where('fecha_compra', '<=', $request->fecha_hasta);
        }

        $compras = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $compras,
            'message' => 'Compras obtenidas correctamente'
        ]);
    }

    /**
     * Ver una compra específica
     * GET /api/compras/{id}
     */
    public function show(string $id): JsonResponse
    {
        $compra = Compra::with(['proveedor', 'usuario', 'detalles.producto'])->find($id);

        if (!$compra) {
            return response()->json([
                'success' => false,
                'message' => 'Compra no encontrada'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $compra,
            'message' => 'Compra obtenida correctamente'
        ]);
    }

    /**
     * Crear una compra
     * POST /api/compras
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'proveedor_id' => 'required|exists:proveedores,id',
            'numero_comprobante' => 'required|string|unique:compras,numero_comprobante',
            'fecha_compra' => 'required|date',
            'fecha_vencimiento' => 'nullable|date',
            'moneda' => 'required|in:PEN,USD',
            'tipo_cambio' => 'nullable|numeric|min:0',
            'observaciones' => 'nullable|string',
            'detalles' => 'required|array|min:1',
            'detalles.*.producto_id' => 'required|exists:productos,id',
            'detalles.*.cantidad' => 'required|numeric|min:0.01',
            'detalles.*.precio_unitario' => 'required|numeric|min:0',
            'detalles.*.descuento' => 'nullable|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $compra = Compra::create([
                'proveedor_id' => $validated['proveedor_id'],
                'usuario_id' => auth()->id(),
                'numero_comprobante' => $validated['numero_comprobante'],
                'fecha_compra' => $validated['fecha_compra'],
                'fecha_vencimiento' => $validated['fecha_vencimiento'] ?? null,
                'moneda' => $validated['moneda'],
                'tipo_cambio' => $validated['tipo_cambio'] ?? 1.0,
                'estado' => 'pendiente',
                'observaciones' => $validated['observaciones'] ?? null,
                'total_gravada' => 0,
                'total_exonerada' => 0,
                'total_igv' => 0,
                'total_descuentos' => 0,
                'total' => 0,
            ]);

            $totales = [
                'total_gravada' => 0,
                'total_igv' => 0,
                'total_descuentos' => 0,
                'total' => 0,
            ];

            foreach ($validated['detalles'] as $index => $detalle) {
                $producto = Producto::findOrFail($detalle['producto_id']);
                $cantidad = $detalle['cantidad'];
                $precio_unitario = $detalle['precio_unitario'];
                $descuento = $detalle['descuento'] ?? 0;

                $subtotal = ($cantidad * $precio_unitario) - $descuento;
                $igv = $subtotal * 0.18; // asumimos IGV general
                $total = $subtotal + $igv;

                CompraDetalle::create([
                    'compra_id' => $compra->id,
                    'producto_id' => $producto->id,
                    'item' => $index + 1,
                    'cantidad' => $cantidad,
                    'precio_unitario' => $precio_unitario,
                    'descuento' => $descuento,
                    'subtotal' => $subtotal,
                    'igv' => $igv,
                    'total' => $total,
                ]);

                $totales['total_gravada'] += $subtotal;
                $totales['total_igv'] += $igv;
                $totales['total_descuentos'] += $descuento;
                $totales['total'] += $total;

                $producto->increment('stock', $cantidad);
            }

            $compra->update($totales);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $compra->load(['proveedor', 'usuario', 'detalles.producto']),
                'message' => 'Compra creada correctamente'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la compra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Anular una compra
     * POST /api/compras/{id}/anular
     */
    public function anular(string $id): JsonResponse
    {
        $compra = Compra::find($id);

        if (!$compra) {
            return response()->json([
                'success' => false,
                'message' => 'Compra no encontrada'
            ], 404);
        }

        if ($compra->estado === 'anulado') {
            return response()->json([
                'success' => false,
                'message' => 'La compra ya está anulada'
            ], 422);
        }

        $compra->update(['estado' => 'anulado']);

        return response()->json([
            'success' => true,
            'data' => $compra,
            'message' => 'Compra anulada correctamente'
        ]);
    }
}
