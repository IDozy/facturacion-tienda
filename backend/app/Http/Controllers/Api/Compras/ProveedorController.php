<?php

namespace App\Http\Controllers\Api\Compras;

use App\Http\Controllers\Controller;
use App\Models\Compras\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ProveedorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Proveedor::with(['empresa']);

        // Filtros
        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->has('tipo_documento')) {
            $query->where('tipo_documento', $request->tipo_documento);
        }

        if ($request->has('search')) {
            $query->buscar($request->search);
        }

        // Incluir eliminados
        if ($request->get('con_eliminados') === 'true') {
            $query->withTrashed();
        }

        // Ordenamiento
        $sortBy = $request->get('sort_by', 'razon_social');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginación
        $perPage = $request->get('per_page', 15);
        
        if ($request->get('all') === 'true') {
            $proveedores = $query->activos()->get();
            return response()->json(['data' => $proveedores]);
        }

        $proveedores = $query->paginate($perPage);

        return response()->json($proveedores);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $empresaId = Auth::user()->empresa_id;

        $validator = Validator::make($request->all(), [
            'tipo_documento' => 'required|in:RUC,DNI,CE,PASAPORTE',
            'numero_documento' => [
                'required',
                'string',
                Rule::unique('proveedores')->where(function ($query) use ($empresaId) {
                    return $query->where('empresa_id', $empresaId)
                                 ->whereNull('deleted_at');
                })
            ],
            'razon_social' => 'required|string|max:255',
            'direccion' => 'nullable|string|max:500',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'estado' => 'nullable|in:activo,inactivo',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        // Validar formato de documento según tipo
        $error = $this->validarFormatoDocumento($request->tipo_documento, $request->numero_documento);
        if ($error) {
            return response()->json([
                'message' => $error
            ], 400);
        }

        try {
            $proveedor = Proveedor::create(array_merge(
                $request->all(),
                ['empresa_id' => $empresaId]
            ));

            return response()->json([
                'message' => 'Proveedor creado exitosamente',
                'data' => $proveedor->load(['empresa'])
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear el proveedor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Proveedor $proveedor)
    {
        return response()->json([
            'data' => $proveedor->load(['empresa']),
            'documento_completo' => $proveedor->documento_completo,
            'nombre_corto' => $proveedor->nombre_corto,
            'total_comprado' => $proveedor->totalComprado(),
            'ultima_compra' => $proveedor->ultimaCompra()
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Proveedor $proveedor)
    {
        $empresaId = Auth::user()->empresa_id;

        $validator = Validator::make($request->all(), [
            'tipo_documento' => 'sometimes|required|in:RUC,DNI,CE,PASAPORTE',
            'numero_documento' => [
                'sometimes',
                'required',
                'string',
                Rule::unique('proveedores')->ignore($proveedor->id)->where(function ($query) use ($empresaId) {
                    return $query->where('empresa_id', $empresaId)
                                 ->whereNull('deleted_at');
                })
            ],
            'razon_social' => 'sometimes|required|string|max:255',
            'direccion' => 'nullable|string|max:500',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'estado' => 'nullable|in:activo,inactivo',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        // Validar formato si cambió el documento
        if ($request->has('tipo_documento') || $request->has('numero_documento')) {
            $tipoDoc = $request->tipo_documento ?? $proveedor->tipo_documento;
            $numeroDoc = $request->numero_documento ?? $proveedor->numero_documento;
            
            $error = $this->validarFormatoDocumento($tipoDoc, $numeroDoc);
            if ($error) {
                return response()->json([
                    'message' => $error
                ], 400);
            }
        }

        try {
            $proveedor->update($request->all());

            return response()->json([
                'message' => 'Proveedor actualizado exitosamente',
                'data' => $proveedor->fresh(['empresa'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar el proveedor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage (soft delete).
     */
    public function destroy(Proveedor $proveedor)
    {
        // Verificar si tiene compras
        if ($proveedor->compras()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar un proveedor con compras registradas',
                'cantidad_compras' => $proveedor->compras()->count()
            ], 400);
        }

        try {
            $proveedor->delete();

            return response()->json([
                'message' => 'Proveedor eliminado exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar el proveedor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restaurar proveedor eliminado
     */
    public function restore($id)
    {
        $proveedor = Proveedor::withTrashed()->findOrFail($id);

        if (!$proveedor->trashed()) {
            return response()->json([
                'message' => 'El proveedor no está eliminado'
            ], 400);
        }

        try {
            $proveedor->restore();

            return response()->json([
                'message' => 'Proveedor restaurado exitosamente',
                'data' => $proveedor
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al restaurar el proveedor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle estado del proveedor
     */
    public function toggleEstado(Proveedor $proveedor)
    {
        try {
            if ($proveedor->esActivo()) {
                $proveedor->inactivar();
            } else {
                $proveedor->activar();
            }

            return response()->json([
                'message' => "Proveedor " . ($proveedor->estado === 'activo' ? 'activado' : 'inactivado'),
                'data' => $proveedor
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al cambiar el estado',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener compras del proveedor
     */
    public function compras(Proveedor $proveedor, Request $request)
    {
        $query = $proveedor->compras()->with(['usuario']);

        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->has('fecha_desde') && $request->has('fecha_hasta')) {
            $query->whereBetween('fecha_emision', [$request->fecha_desde, $request->fecha_hasta]);
        }

        $compras = $query->orderBy('fecha_emision', 'desc')
            ->paginate($request->get('per_page', 50));

        return response()->json($compras);
    }

    /**
     * Top proveedores por compras
     */
    public function topProveedores(Request $request)
    {
        $query = Proveedor::where('empresa_id', Auth::user()->empresa_id)
            ->activos()
            ->withCount('compras')
            ->with('compras');

        $proveedores = $query->get()->map(function ($proveedor) {
            return [
                'id' => $proveedor->id,
                'razon_social' => $proveedor->razon_social,
                'numero_documento' => $proveedor->numero_documento,
                'cantidad_compras' => $proveedor->compras_count,
                'total_comprado' => $proveedor->totalComprado(),
            ];
        })->sortByDesc('total_comprado')
          ->take($request->get('limit', 10))
          ->values();

        return response()->json([
            'data' => $proveedores
        ]);
    }

    /**
     * Estadísticas de proveedores
     */
    public function estadisticas()
    {
        $empresaId = Auth::user()->empresa_id;

        $total = Proveedor::where('empresa_id', $empresaId)->count();
        $activos = Proveedor::where('empresa_id', $empresaId)->activos()->count();
        $inactivos = Proveedor::where('empresa_id', $empresaId)->inactivos()->count();

        $porTipoDocumento = Proveedor::where('empresa_id', $empresaId)
            ->select('tipo_documento')
            ->selectRaw('COUNT(*) as cantidad')
            ->groupBy('tipo_documento')
            ->get();

        $conCompras = Proveedor::where('empresa_id', $empresaId)
            ->has('compras')
            ->count();

        $sinCompras = $total - $conCompras;

        return response()->json([
            'total_proveedores' => $total,
            'activos' => $activos,
            'inactivos' => $inactivos,
            'por_tipo_documento' => $porTipoDocumento,
            'con_compras' => $conCompras,
            'sin_compras' => $sinCompras
        ]);
    }

    /**
     * Proveedores por tipo de documento
     */
    public function porTipoDocumento($tipoDocumento)
    {
        if (!in_array($tipoDocumento, ['RUC', 'DNI', 'CE', 'PASAPORTE'])) {
            return response()->json([
                'message' => 'Tipo de documento no válido'
            ], 400);
        }

        $proveedores = Proveedor::where('empresa_id', Auth::user()->empresa_id)
            ->where('tipo_documento', $tipoDocumento)
            ->activos()
            ->orderBy('razon_social')
            ->get();

        return response()->json([
            'data' => $proveedores,
            'tipo_documento' => $tipoDocumento,
            'count' => $proveedores->count()
        ]);
    }

    /**
     * Buscar proveedor por documento
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

        $proveedor = Proveedor::where('empresa_id', Auth::user()->empresa_id)
            ->where('numero_documento', $request->numero_documento)
            ->first();

        if (!$proveedor) {
            return response()->json([
                'message' => 'Proveedor no encontrado'
            ], 404);
        }

        return response()->json([
            'data' => $proveedor
        ]);
    }

    /**
     * Importar proveedores
     */
    public function importar(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'proveedores' => 'required|array',
            'proveedores.*.tipo_documento' => 'required|in:RUC,DNI,CE,PASAPORTE',
            'proveedores.*.numero_documento' => 'required|string',
            'proveedores.*.razon_social' => 'required|string|max:255',
            'proveedores.*.direccion' => 'nullable|string|max:500',
            'proveedores.*.telefono' => 'nullable|string|max:20',
            'proveedores.*.email' => 'nullable|email|max:100',
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

            foreach ($request->proveedores as $index => $proveedorData) {
                try {
                    // Verificar si ya existe
                    $existe = Proveedor::where('empresa_id', $empresaId)
                        ->where('numero_documento', $proveedorData['numero_documento'])
                        ->exists();

                    if ($existe) {
                        $errores[] = [
                            'index' => $index,
                            'numero_documento' => $proveedorData['numero_documento'],
                            'error' => 'El proveedor ya existe'
                        ];
                        continue;
                    }

                    // Validar formato
                    $error = $this->validarFormatoDocumento(
                        $proveedorData['tipo_documento'],
                        $proveedorData['numero_documento']
                    );

                    if ($error) {
                        $errores[] = [
                            'index' => $index,
                            'numero_documento' => $proveedorData['numero_documento'],
                            'error' => $error
                        ];
                        continue;
                    }

                    Proveedor::create(array_merge($proveedorData, [
                        'empresa_id' => $empresaId,
                        'estado' => 'activo'
                    ]));

                    $importados++;
                } catch (\Exception $e) {
                    $errores[] = [
                        'index' => $index,
                        'numero_documento' => $proveedorData['numero_documento'] ?? 'N/A',
                        'error' => $e->getMessage()
                    ];
                }
            }

            DB::commit();

            return response()->json([
                'message' => "Se importaron {$importados} proveedor(es)",
                'importados' => $importados,
                'errores' => $errores
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al importar proveedores',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exportar proveedores
     */
    public function exportar(Request $request)
    {
        $query = Proveedor::where('empresa_id', Auth::user()->empresa_id);

        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->has('tipo_documento')) {
            $query->where('tipo_documento', $request->tipo_documento);
        }

        $proveedores = $query->orderBy('razon_social')->get()->map(function ($proveedor) {
            return [
                'tipo_documento' => $proveedor->tipo_documento,
                'numero_documento' => $proveedor->numero_documento,
                'razon_social' => $proveedor->razon_social,
                'direccion' => $proveedor->direccion,
                'telefono' => $proveedor->telefono,
                'email' => $proveedor->email,
                'estado' => $proveedor->estado,
                'total_comprado' => $proveedor->totalComprado(),
            ];
        });

        return response()->json([
            'data' => $proveedores,
            'count' => $proveedores->count()
        ]);
    }

    /**
     * Validar formato de documento según tipo
     */
    private function validarFormatoDocumento($tipo, $numero): ?string
    {
        switch ($tipo) {
            case 'RUC':
                if (!preg_match('/^\d{11}$/', $numero)) {
                    return 'El RUC debe tener 11 dígitos';
                }
                break;
            case 'DNI':
                if (!preg_match('/^\d{8}$/', $numero)) {
                    return 'El DNI debe tener 8 dígitos';
                }
                break;
            case 'CE':
                if (!preg_match('/^\d{9}$/', $numero)) {
                    return 'El Carnet de Extranjería debe tener 9 dígitos';
                }
                break;
            case 'PASAPORTE':
                if (strlen($numero) < 6 || strlen($numero) > 12) {
                    return 'El Pasaporte debe tener entre 6 y 12 caracteres';
                }
                break;
        }

        return null;
    }

    /**
     * Tipos de documento disponibles
     */
    public function tiposDocumento()
    {
        $tipos = [
            ['value' => 'RUC', 'label' => 'RUC (11 dígitos)'],
            ['value' => 'DNI', 'label' => 'DNI (8 dígitos)'],
            ['value' => 'CE', 'label' => 'Carnet de Extranjería (9 dígitos)'],
            ['value' => 'PASAPORTE', 'label' => 'Pasaporte (6-12 caracteres)'],
        ];

        return response()->json([
            'data' => $tipos
        ]);
    }

    /**
     * Proveedores eliminados
     */
    public function eliminados(Request $request)
    {
        $proveedores = Proveedor::onlyTrashed()
            ->where('empresa_id', Auth::user()->empresa_id)
            ->orderBy('deleted_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json($proveedores);
    }
}