<?php

namespace App\Http\Controllers\Api\Inventario;

use App\Http\Controllers\Controller;
use App\Models\MovimientoStock;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MovimientoStockController extends Controller
{
    public function index(): JsonResponse
    {
        $movimientos = MovimientoStock::with('almacen', 'producto', 'usuario')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $movimientos,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'almacen_id' => 'required|exists:almacenes,id',
            'producto_id' => 'required|exists:productos,id',
            'tipo' => 'required|in:entrada,salida,ajuste,transferencia',
            'cantidad' => 'required|numeric|min:0.01',
            'descripcion' => 'nullable|string',
            'referencia' => 'nullable|string',
        ]);

        $producto = Producto::find($validated['producto_id']);

        // Validar que hay stock suficiente para salida
        if ($validated['tipo'] === 'salida' && $producto->stock < $validated['cantidad']) {
            return response()->json([
                'success' => false,
                'message' => 'Stock insuficiente'
            ], 422);
        }

        $movimiento = MovimientoStock::create([
            'almacen_id' => $validated['almacen_id'],
            'producto_id' => $validated['producto_id'],
            'usuario_id' => auth()->id(),
            'tipo' => $validated['tipo'],
            'cantidad' => $validated['cantidad'],
            'descripcion' => $validated['descripcion'],
            'referencia' => $validated['referencia'],
        ]);

        // Actualizar stock
        if ($validated['tipo'] === 'entrada' || $validated['tipo'] === 'ajuste') {
            $producto->increment('stock', $validated['cantidad']);
        } elseif ($validated['tipo'] === 'salida') {
            $producto->decrement('stock', $validated['cantidad']);
        }

        return response()->json([
            'success' => true,
            'data' => $movimiento,
            'message' => 'Movimiento registrado correctamente'
        ], 201);
    }

    public function porProducto(string $productoId): JsonResponse
    {
        $movimientos = MovimientoStock::where('producto_id', $productoId)
            ->with('almacen', 'usuario')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $movimientos,
        ]);
    }

    public function porAlmacen(string $almacenId): JsonResponse
    {
        $movimientos = MovimientoStock::where('almacen_id', $almacenId)
            ->with('producto', 'usuario')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $movimientos,
        ]);
    }
}