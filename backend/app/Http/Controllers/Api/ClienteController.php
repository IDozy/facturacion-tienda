<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ClienteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Cliente::with(['empresa']);

        // Filtros
        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->has('tipo_documento')) {
            $query->porTipoDocumento($request->tipo_documento);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('razon_social', 'like', "%{$search}%")
                    ->orWhere('numero_documento', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->has('con_deuda')) {
            $query->whereHas('comprobantes', function ($q) {
                $q->where('estado', '!=', 'anulado')
                    ->where('saldo_pendiente', '>', 0);
            });
        }

        // Ordenamiento
        $sortBy = $request->get('sort_by', 'razon_social');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginación
        $perPage = $request->get('per_page', 15);
        
        if ($request->get('all') === 'true') {
            $clientes = $query->activos()->get();
            return response()->json(['data' => $clientes]);
        }

        $clientes = $query->paginate($perPage);

        return response()->json($clientes);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $empresaId = Auth::user()->empresa_id;

        $validator = Validator::make($request->all(), [
            'tipo_documento' => 'required|in:DNI,RUC,CE,PASAPORTE',
            'numero_documento' => [
                'required',
                'string',
                'max:20',
                Rule::unique('clientes')->where(function ($query) use ($empresaId) {
                    return $query->where('empresa_id', $empresaId);
                })
            ],
            'razon_social' => 'required|string|max:255',
            'direccion' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'telefono' => 'nullable|string|max:20',
            'estado' => 'sometimes|in:activo,inactivo',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $cliente = Cliente::create($request->all());

            return response()->json([
                'message' => 'Cliente creado exitosamente',
                'data' => $cliente->load('empresa')
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear el cliente',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Cliente $cliente)
    {
        // Verificar que pertenece a la misma empresa
        if ($cliente->empresa_id !== Auth::user()->empresa_id) {
            return response()->json([
                'message' => 'No autorizado'
            ], 403);
        }

        return response()->json([
            'data' => $cliente->load(['empresa', 'comprobantes' => function ($query) {
                $query->latest()->limit(10);
            }])
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Cliente $cliente)
    {
        // Verificar que pertenece a la misma empresa
        if ($cliente->empresa_id !== Auth::user()->empresa_id) {
            return response()->json([
                'message' => 'No autorizado'
            ], 403);
        }

        $empresaId = Auth::user()->empresa_id;

        $validator = Validator::make($request->all(), [
            'tipo_documento' => 'sometimes|required|in:DNI,RUC,CE,PASAPORTE',
            'numero_documento' => [
                'sometimes',
                'required',
                'string',
                'max:20',
                Rule::unique('clientes')->ignore($cliente->id)->where(function ($query) use ($empresaId) {
                    return $query->where('empresa_id', $empresaId);
                })
            ],
            'razon_social' => 'sometimes|required|string|max:255',
            'direccion' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'telefono' => 'nullable|string|max:20',
            'estado' => 'sometimes|in:activo,inactivo',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $cliente->update($request->all());

            return response()->json([
                'message' => 'Cliente actualizado exitosamente',
                'data' => $cliente->fresh(['empresa'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar el cliente',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Cliente $cliente)
    {
        // Verificar que pertenece a la misma empresa
        if ($cliente->empresa_id !== Auth::user()->empresa_id) {
            return response()->json([
                'message' => 'No autorizado'
            ], 403);
        }

        // Verificar si tiene comprobantes asociados
        if ($cliente->comprobantes()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar el cliente porque tiene comprobantes asociados'
            ], 400);
        }

        try {
            $cliente->delete();

            return response()->json([
                'message' => 'Cliente eliminado exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar el cliente',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle estado del cliente
     */
    public function toggleEstado(Cliente $cliente)
    {
        // Verificar que pertenece a la misma empresa
        if ($cliente->empresa_id !== Auth::user()->empresa_id) {
            return response()->json([
                'message' => 'No autorizado'
            ], 403);
        }

        try {
            $cliente->estado = $cliente->estado === 'activo' ? 'inactivo' : 'activo';
            $cliente->save();

            return response()->json([
                'message' => "Cliente {$cliente->estado}",
                'data' => $cliente
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al cambiar el estado',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Buscar cliente por documento
     */
    public function buscarPorDocumento(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'numero_documento' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $cliente = Cliente::porNumeroDocumento($request->numero_documento)
            ->where('empresa_id', Auth::user()->empresa_id)
            ->first();

        if (!$cliente) {
            return response()->json([
                'message' => 'Cliente no encontrado'
            ], 404);
        }

        return response()->json([
            'data' => $cliente
        ]);
    }

    /**
     * Obtener deuda del cliente
     */
    public function deuda(Cliente $cliente)
    {
        // Verificar que pertenece a la misma empresa
        if ($cliente->empresa_id !== Auth::user()->empresa_id) {
            return response()->json([
                'message' => 'No autorizado'
            ], 403);
        }

        $tieneDeuda = $cliente->tieneDeuda();
        $montoDeuda = $cliente->montoDeudaTotal();

        $comprobantesConDeuda = $cliente->comprobantes()
            ->where('estado', '!=', 'anulado')
            ->where('saldo_pendiente', '>', 0)
            ->get();

        return response()->json([
            'tiene_deuda' => $tieneDeuda,
            'monto_total' => $montoDeuda,
            'comprobantes' => $comprobantesConDeuda,
            'cantidad_comprobantes' => $comprobantesConDeuda->count()
        ]);
    }

    /**
     * Obtener comprobantes del cliente
     */
    public function comprobantes(Cliente $cliente, Request $request)
    {
        // Verificar que pertenece a la misma empresa
        if ($cliente->empresa_id !== Auth::user()->empresa_id) {
            return response()->json([
                'message' => 'No autorizado'
            ], 403);
        }

        $query = $cliente->comprobantes();

        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->has('fecha_desde') && $request->has('fecha_hasta')) {
            $query->whereBetween('fecha_emision', [$request->fecha_desde, $request->fecha_hasta]);
        }

        $comprobantes = $query->orderBy('fecha_emision', 'desc')->get();

        $totalFacturado = $comprobantes->sum('total');
        $totalPendiente = $comprobantes->sum('saldo_pendiente');

        return response()->json([
            'data' => $comprobantes,
            'resumen' => [
                'total_facturado' => $totalFacturado,
                'total_pendiente' => $totalPendiente,
                'cantidad_comprobantes' => $comprobantes->count()
            ]
        ]);
    }

    /**
     * Estadísticas del cliente
     */
    public function estadisticas(Cliente $cliente)
    {
        // Verificar que pertenece a la misma empresa
        if ($cliente->empresa_id !== Auth::user()->empresa_id) {
            return response()->json([
                'message' => 'No autorizado'
            ], 403);
        }

        $totalComprobantes = $cliente->comprobantes()->count();
        $totalFacturado = $cliente->comprobantes()->where('estado', '!=', 'anulado')->sum('total');
        $totalPendiente = $cliente->montoDeudaTotal();
        $ultimaCompra = $cliente->comprobantes()->latest('fecha_emision')->first();

        return response()->json([
            'total_comprobantes' => $totalComprobantes,
            'total_facturado' => $totalFacturado,
            'total_pendiente' => $totalPendiente,
            'ultima_compra' => $ultimaCompra?->fecha_emision,
            'tiene_deuda' => $cliente->tieneDeuda()
        ]);
    }

    /**
     * Clientes con mayor facturación
     */
    public function topClientes(Request $request)
    {
        $limit = $request->get('limit', 10);
        $empresaId = Auth::user()->empresa_id;

        $clientes = Cliente::where('empresa_id', $empresaId)
            ->withSum(['comprobantes as total_facturado' => function ($query) {
                $query->where('estado', '!=', 'anulado');
            }], 'total')
            ->having('total_facturado', '>', 0)
            ->orderBy('total_facturado', 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'data' => $clientes
        ]);
    }

    /**
     * Clientes con deuda
     */
    public function conDeuda()
    {
        $clientes = Cliente::where('empresa_id', Auth::user()->empresa_id)
            ->whereHas('comprobantes', function ($query) {
                $query->where('estado', '!=', 'anulado')
                    ->where('saldo_pendiente', '>', 0);
            })
            ->withSum(['comprobantes as deuda_total' => function ($query) {
                $query->where('estado', '!=', 'anulado');
            }], 'saldo_pendiente')
            ->orderBy('deuda_total', 'desc')
            ->get();

        $deudaTotal = $clientes->sum('deuda_total');

        return response()->json([
            'data' => $clientes,
            'resumen' => [
                'cantidad_clientes' => $clientes->count(),
                'deuda_total' => $deudaTotal
            ]
        ]);
    }

    /**
     * Importar clientes
     */
    public function importar(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'clientes' => 'required|array',
            'clientes.*.tipo_documento' => 'required|in:DNI,RUC,CE,PASAPORTE',
            'clientes.*.numero_documento' => 'required|string|max:20',
            'clientes.*.razon_social' => 'required|string|max:255',
            'clientes.*.direccion' => 'nullable|string|max:255',
            'clientes.*.email' => 'nullable|email|max:255',
            'clientes.*.telefono' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $importados = 0;
            $errores = [];
            $empresaId = Auth::user()->empresa_id;

            foreach ($request->clientes as $index => $clienteData) {
                try {
                    // Verificar si ya existe
                    $existe = Cliente::where('numero_documento', $clienteData['numero_documento'])
                        ->where('empresa_id', $empresaId)
                        ->exists();

                    if ($existe) {
                        $errores[] = [
                            'index' => $index,
                            'documento' => $clienteData['numero_documento'],
                            'error' => 'El cliente ya existe'
                        ];
                        continue;
                    }

                    Cliente::create(array_merge($clienteData, [
                        'empresa_id' => $empresaId,
                        'estado' => 'activo'
                    ]));

                    $importados++;
                } catch (\Exception $e) {
                    $errores[] = [
                        'index' => $index,
                        'documento' => $clienteData['numero_documento'] ?? 'N/A',
                        'error' => $e->getMessage()
                    ];
                }
            }

            DB::commit();

            return response()->json([
                'message' => "Se importaron {$importados} clientes",
                'importados' => $importados,
                'errores' => $errores
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al importar clientes',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}