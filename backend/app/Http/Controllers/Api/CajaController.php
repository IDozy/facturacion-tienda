<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Caja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CajaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Caja::with(['usuario']);

        // Filtros
        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->has('usuario_id')) {
            $query->delUsuario($request->usuario_id);
        }

        if ($request->has('fecha')) {
            $query->delDia($request->fecha);
        }

        if ($request->has('fecha_desde') && $request->has('fecha_hasta')) {
            $query->whereBetween('apertura', [$request->fecha_desde, $request->fecha_hasta]);
        }

        // Ordenamiento
        $sortBy = $request->get('sort_by', 'apertura');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginación
        $perPage = $request->get('per_page', 15);
        $cajas = $query->paginate($perPage);

        return response()->json($cajas);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'monto_inicial' => 'required|numeric|min:0',
            'moneda' => 'required|in:PEN,USD,EUR',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        // Verificar que el usuario no tenga una caja abierta
        $cajaAbierta = Caja::where('usuario_id', Auth::id())
            ->abiertas()
            ->first();

        if ($cajaAbierta) {
            return response()->json([
                'message' => 'Ya tienes una caja abierta',
                'caja' => $cajaAbierta
            ], 400);
        }

        DB::beginTransaction();
        try {
            $caja = Caja::create([
                'usuario_id' => Auth::id(),
                'monto_inicial' => $request->monto_inicial,
                'moneda' => $request->moneda,
                'estado' => 'abierta',
                'apertura' => now(),
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Caja abierta exitosamente',
                'data' => $caja->load('usuario')
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al abrir la caja',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Caja $caja)
    {
        return response()->json([
            'data' => $caja->load(['usuario', 'pagos'])
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Caja $caja)
    {
        if ($caja->estaCerrada()) {
            return response()->json([
                'message' => 'No se puede modificar una caja cerrada'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'monto_inicial' => 'sometimes|required|numeric|min:0',
            'moneda' => 'sometimes|required|in:PEN,USD,EUR',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $caja->update($request->all());

            return response()->json([
                'message' => 'Caja actualizada exitosamente',
                'data' => $caja->fresh(['usuario'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar la caja',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Caja $caja)
    {
        if ($caja->estaAbierta()) {
            return response()->json([
                'message' => 'No se puede eliminar una caja abierta. Debe cerrarla primero.'
            ], 400);
        }

        if ($caja->pagos()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar una caja con pagos registrados'
            ], 400);
        }

        try {
            $caja->delete();

            return response()->json([
                'message' => 'Caja eliminada exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar la caja',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cerrar caja
     */
    public function cerrar(Request $request, Caja $caja)
    {
        $validator = Validator::make($request->all(), [
            'monto_final' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        if ($caja->estaCerrada()) {
            return response()->json([
                'message' => 'La caja ya está cerrada'
            ], 400);
        }

        if ($caja->usuario_id !== Auth::id()) {
            return response()->json([
                'message' => 'No puedes cerrar una caja que no abriste'
            ], 403);
        }

        DB::beginTransaction();
        try {
            $caja->cerrar($request->monto_final);

            DB::commit();

            return response()->json([
                'message' => 'Caja cerrada exitosamente',
                'data' => $caja->fresh(),
                'cuadrada' => $caja->es_cuadrada,
                'diferencia' => $caja->diferencia_cuadratura
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al cerrar la caja',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener caja abierta del usuario actual
     */
    public function cajaAbierta()
    {
        $caja = Caja::where('usuario_id', Auth::id())
            ->abiertas()
            ->with(['usuario', 'pagos'])
            ->first();

        if (!$caja) {
            return response()->json([
                'message' => 'No tienes una caja abierta',
                'data' => null
            ], 404);
        }

        return response()->json([
            'data' => $caja,
            'total_esperado' => $caja->calcularTotalEsperado()
        ]);
    }

    /**
     * Obtener resumen de caja
     */
    public function resumen(Caja $caja)
    {
        $totalPagos = $caja->pagos()->where('estado', 'confirmado')->sum('monto');
        $cantidadPagos = $caja->pagos()->where('estado', 'confirmado')->count();
        $totalEsperado = $caja->calcularTotalEsperado();

        $pagosPorMedio = $caja->pagos()
            ->where('estado', 'confirmado')
            ->with('medioPago')
            ->get()
            ->groupBy('medio_pago_id')
            ->map(function ($pagos) {
                return [
                    'medio_pago' => $pagos->first()->medioPago,
                    'total' => $pagos->sum('monto'),
                    'cantidad' => $pagos->count()
                ];
            })->values();

        return response()->json([
            'caja' => $caja,
            'monto_inicial' => $caja->monto_inicial,
            'total_pagos' => $totalPagos,
            'cantidad_pagos' => $cantidadPagos,
            'total_esperado' => $totalEsperado,
            'monto_final' => $caja->monto_final,
            'diferencia' => $caja->diferencia_cuadratura,
            'cuadrada' => $caja->es_cuadrada,
            'duracion' => $caja->duracion,
            'pagos_por_medio' => $pagosPorMedio
        ]);
    }

    /**
     * Validar cuadratura
     */
    public function validarCuadratura(Caja $caja)
    {
        if ($caja->estaAbierta()) {
            return response()->json([
                'message' => 'No se puede validar la cuadratura de una caja abierta'
            ], 400);
        }

        $tolerancia = Auth::user()->empresa->configuracion->tolerancia_cuadratura ?? 0;
        $esCuadrada = $caja->validarCuadratura($tolerancia);

        return response()->json([
            'cuadrada' => $esCuadrada,
            'diferencia' => $caja->diferencia_cuadratura,
            'tolerancia' => $tolerancia,
            'mensaje' => $esCuadrada 
                ? 'La caja está cuadrada' 
                : "La caja tiene una diferencia de {$caja->diferencia_cuadratura}"
        ]);
    }

    /**
     * Cajas del día
     */
    public function delDia(Request $request)
    {
        $fecha = $request->get('fecha', now()->toDateString());
        
        $cajas = Caja::delDia($fecha)
            ->with(['usuario'])
            ->get();

        $totalAperturas = $cajas->count();
        $totalCerradas = $cajas->where('estado', 'cerrada')->count();
        $totalAbiertas = $cajas->where('estado', 'abierta')->count();
        $totalRecaudado = $cajas->where('estado', 'cerrada')->sum('monto_final');

        return response()->json([
            'data' => $cajas,
            'resumen' => [
                'total_aperturas' => $totalAperturas,
                'total_cerradas' => $totalCerradas,
                'total_abiertas' => $totalAbiertas,
                'total_recaudado' => $totalRecaudado,
                'fecha' => $fecha
            ]
        ]);
    }

    /**
     * Cajas del usuario
     */
    public function delUsuario(Request $request, $usuarioId)
    {
        $query = Caja::delUsuario($usuarioId)->with(['usuario']);

        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->has('fecha_desde') && $request->has('fecha_hasta')) {
            $query->whereBetween('apertura', [$request->fecha_desde, $request->fecha_hasta]);
        }

        $cajas = $query->orderBy('apertura', 'desc')->get();

        return response()->json([
            'data' => $cajas,
            'count' => $cajas->count()
        ]);
    }

    /**
     * Estadísticas de cajas
     */
    public function estadisticas(Request $request)
    {
        $query = Caja::query();

        if ($request->has('fecha_desde') && $request->has('fecha_hasta')) {
            $query->whereBetween('apertura', [$request->fecha_desde, $request->fecha_hasta]);
        }

        $totalCajas = (clone $query)->count();
        $cajasAbiertas = (clone $query)->abiertas()->count();
        $cajasCerradas = (clone $query)->cerradas()->count();
        $totalRecaudado = (clone $query)->cerradas()->sum('monto_final');
        $totalDiferencias = (clone $query)->cerradas()->sum('diferencia_cuadratura');

        $cajasSinCuadrar = (clone $query)->cerradas()->get()->filter(function ($caja) {
            return !$caja->validarCuadratura();
        })->count();

        return response()->json([
            'total_cajas' => $totalCajas,
            'abiertas' => $cajasAbiertas,
            'cerradas' => $cajasCerradas,
            'total_recaudado' => $totalRecaudado,
            'total_diferencias' => $totalDiferencias,
            'cajas_sin_cuadrar' => $cajasSinCuadrar
        ]);
    }

    /**
     * Reabrir caja
     */
    public function reabrir(Caja $caja)
    {
        if ($caja->estaAbierta()) {
            return response()->json([
                'message' => 'La caja ya está abierta'
            ], 400);
        }

        if ($caja->usuario_id !== Auth::id()) {
            return response()->json([
                'message' => 'No puedes reabrir una caja que no te pertenece'
            ], 403);
        }

        try {
            $caja->update([
                'estado' => 'abierta',
                'cierre' => null,
                'monto_final' => null,
                'total_esperado' => null,
                'diferencia_cuadratura' => null,
            ]);

            return response()->json([
                'message' => 'Caja reabierta exitosamente',
                'data' => $caja->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al reabrir la caja',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}