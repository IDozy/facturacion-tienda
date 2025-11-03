<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MedioPago;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class MedioPagoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = MedioPago::query();

        // Filtros
        if ($request->has('activo')) {
            $query->where('activo', $request->activo);
        }

        if ($request->has('codigo_sunat')) {
            $query->porCodigo($request->codigo_sunat);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                    ->orWhere('codigo_sunat', 'like', "%{$search}%")
                    ->orWhere('descripcion', 'like', "%{$search}%");
            });
        }

        // Ordenamiento
        $sortBy = $request->get('sort_by', 'nombre');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginación o listado completo
        if ($request->get('all') === 'true') {
            $mediosPago = $query->get();
            return response()->json(['data' => $mediosPago]);
        }

        $perPage = $request->get('per_page', 15);
        $mediosPago = $query->paginate($perPage);

        return response()->json($mediosPago);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'codigo_sunat' => 'required|string|max:10|unique:medios_pago,codigo_sunat',
            'nombre' => 'required|string|max:100',
            'descripcion' => 'nullable|string|max:255',
            'activo' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $medioPago = MedioPago::create($request->all());

            return response()->json([
                'message' => 'Medio de pago creado exitosamente',
                'data' => $medioPago
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear el medio de pago',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(MedioPago $medioPago)
    {
        return response()->json([
            'data' => $medioPago->load(['pagos' => function ($query) {
                $query->latest()->limit(10);
            }])
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, MedioPago $medioPago)
    {
        $validator = Validator::make($request->all(), [
            'codigo_sunat' => [
                'sometimes',
                'required',
                'string',
                'max:10',
                Rule::unique('medios_pago')->ignore($medioPago->id)
            ],
            'nombre' => 'sometimes|required|string|max:100',
            'descripcion' => 'nullable|string|max:255',
            'activo' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $medioPago->update($request->all());

            return response()->json([
                'message' => 'Medio de pago actualizado exitosamente',
                'data' => $medioPago->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar el medio de pago',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MedioPago $medioPago)
    {
        // Verificar si tiene pagos asociados
        if ($medioPago->pagos()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar el medio de pago porque tiene pagos asociados'
            ], 400);
        }

        try {
            $medioPago->delete();

            return response()->json([
                'message' => 'Medio de pago eliminado exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar el medio de pago',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle estado activo
     */
    public function toggleStatus(MedioPago $medioPago)
    {
        try {
            $medioPago->activo = !$medioPago->activo;
            $medioPago->save();

            return response()->json([
                'message' => $medioPago->activo ? 'Medio de pago activado' : 'Medio de pago desactivado',
                'data' => $medioPago
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al cambiar el estado',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener constantes SUNAT
     */
    public function constantes()
    {
        $constantes = [
            ['codigo' => MedioPago::EFECTIVO, 'nombre' => 'Efectivo'],
            ['codigo' => MedioPago::DEPOSITO_CUENTA, 'nombre' => 'Depósito en cuenta'],
            ['codigo' => MedioPago::TRANSFERENCIA, 'nombre' => 'Transferencia bancaria'],
            ['codigo' => MedioPago::TARJETA_CREDITO, 'nombre' => 'Tarjeta de crédito'],
            ['codigo' => MedioPago::TARJETA_DEBITO, 'nombre' => 'Tarjeta de débito'],
            ['codigo' => MedioPago::CHEQUE, 'nombre' => 'Cheque'],
            ['codigo' => MedioPago::YAPE, 'nombre' => 'Yape'],
            ['codigo' => MedioPago::PLIN, 'nombre' => 'Plin'],
        ];

        return response()->json([
            'data' => $constantes
        ]);
    }

    /**
     * Obtener medio de pago efectivo
     */
    public function efectivo()
    {
        $efectivo = MedioPago::efectivo();

        if (!$efectivo) {
            return response()->json([
                'message' => 'Medio de pago en efectivo no encontrado'
            ], 404);
        }

        return response()->json([
            'data' => $efectivo
        ]);
    }

    /**
     * Obtener medio de pago transferencia
     */
    public function transferencia()
    {
        $transferencia = MedioPago::transferencia();

        if (!$transferencia) {
            return response()->json([
                'message' => 'Medio de pago transferencia no encontrado'
            ], 404);
        }

        return response()->json([
            'data' => $transferencia
        ]);
    }

    /**
     * Verificar si requiere referencia
     */
    public function requiereReferencia(MedioPago $medioPago)
    {
        return response()->json([
            'requiere_referencia' => $medioPago->requiereReferencia(),
            'es_efectivo' => $medioPago->esEfectivo()
        ]);
    }

    /**
     * Estadísticas de uso
     */
    public function estadisticas(Request $request)
    {
        $query = MedioPago::withCount(['pagos']);

        if ($request->has('fecha_desde') && $request->has('fecha_hasta')) {
            $query->withCount(['pagos as pagos_periodo_count' => function ($q) use ($request) {
                $q->whereBetween('fecha_pago', [$request->fecha_desde, $request->fecha_hasta])
                    ->where('estado', 'confirmado');
            }]);

            $query->withSum(['pagos as monto_periodo' => function ($q) use ($request) {
                $q->whereBetween('fecha_pago', [$request->fecha_desde, $request->fecha_hasta])
                    ->where('estado', 'confirmado');
            }], 'monto');
        }

        $mediosPago = $query->get();

        return response()->json([
            'data' => $mediosPago,
            'total_medios' => $mediosPago->count(),
            'activos' => $mediosPago->where('activo', true)->count()
        ]);
    }

    /**
     * Medios de pago más usados
     */
    public function masUsados(Request $request)
    {
        $query = MedioPago::withCount(['pagos as pagos_count' => function ($q) use ($request) {
            $q->where('estado', 'confirmado');
            if ($request->has('fecha_desde') && $request->has('fecha_hasta')) {
                $q->whereBetween('fecha_pago', [$request->fecha_desde, $request->fecha_hasta]);
            }
        }])
        ->withSum(['pagos as total_monto' => function ($q) use ($request) {
            $q->where('estado', 'confirmado');
            if ($request->has('fecha_desde') && $request->has('fecha_hasta')) {
                $q->whereBetween('fecha_pago', [$request->fecha_desde, $request->fecha_hasta]);
            }
        }], 'monto')
        ->having('pagos_count', '>', 0)
        ->orderBy('pagos_count', 'desc')
        ->limit($request->get('limit', 10));

        $mediosPago = $query->get();

        return response()->json([
            'data' => $mediosPago
        ]);
    }
}