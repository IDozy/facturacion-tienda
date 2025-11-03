<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class EmpresaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Empresa::withCount(['usuarios', 'comprobantes', 'productos', 'clientes']);

        // Filtros
        if ($request->has('modo')) {
            $query->where('modo', $request->modo);
        }

        if ($request->has('pse_autorizado')) {
            $query->where('pse_autorizado', $request->pse_autorizado);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('razon_social', 'like', "%{$search}%")
                    ->orWhere('ruc', 'like', "%{$search}%");
            });
        }

        // Ordenamiento
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginación
        $perPage = $request->get('per_page', 15);
        $empresas = $query->paginate($perPage);

        return response()->json($empresas);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'razon_social' => 'required|string|max:255',
            'ruc' => 'required|string|size:11|unique:empresas,ruc',
            'direccion' => 'required|string|max:255',
            'telefono' => 'nullable|string|max:20',
            'email' => 'required|email|max:255',
            'certificado_digital' => 'nullable|string',
            'clave_certificado' => 'nullable|string',
            'usuario_sol' => 'nullable|string|max:100',
            'clave_sol' => 'nullable|string|max:100',
            'modo' => 'required|in:prueba,produccion',
            'fecha_expiracion_certificado' => 'nullable|date|after:today',
            'pse_autorizado' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $empresa = Empresa::create($request->all());

            // Validar RUC
            if (!$empresa->validarRuc()) {
                DB::rollBack();
                return response()->json([
                    'message' => 'El RUC ingresado no es válido'
                ], 422);
            }

            // Crear configuración por defecto
            $empresa->configuracion()->create([
                'igv_porcentaje' => 18.00,
                'moneda_default' => 'PEN',
                'decimales_cantidad' => 2,
                'decimales_precio' => 2,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Empresa creada exitosamente',
                'data' => $empresa->load('configuracion')
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al crear la empresa',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Empresa $empresa)
    {
        return response()->json([
            'data' => $empresa->load(['configuracion'])
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Empresa $empresa)
    {
        $validator = Validator::make($request->all(), [
            'razon_social' => 'sometimes|required|string|max:255',
            'ruc' => [
                'sometimes',
                'required',
                'string',
                'size:11',
                Rule::unique('empresas')->ignore($empresa->id)
            ],
            'direccion' => 'sometimes|required|string|max:255',
            'telefono' => 'nullable|string|max:20',
            'email' => 'sometimes|required|email|max:255',
            'certificado_digital' => 'nullable|string',
            'clave_certificado' => 'nullable|string',
            'usuario_sol' => 'nullable|string|max:100',
            'clave_sol' => 'nullable|string|max:100',
            'modo' => 'sometimes|required|in:prueba,produccion',
            'fecha_expiracion_certificado' => 'nullable|date|after:today',
            'pse_autorizado' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $empresa->update($request->all());

            // Validar RUC si cambió
            if ($request->has('ruc') && !$empresa->validarRuc()) {
                return response()->json([
                    'message' => 'El RUC ingresado no es válido'
                ], 422);
            }

            return response()->json([
                'message' => 'Empresa actualizada exitosamente',
                'data' => $empresa->fresh(['configuracion'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar la empresa',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Empresa $empresa)
    {
        try {
            $empresa->delete();

            return response()->json([
                'message' => 'Empresa eliminada exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar la empresa',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validar RUC
     */
    public function validarRuc(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ruc' => 'required|string|size:11',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $empresa = new Empresa(['ruc' => $request->ruc]);
        $esValido = $empresa->validarRuc();

        return response()->json([
            'valido' => $esValido,
            'message' => $esValido ? 'RUC válido' : 'RUC inválido'
        ]);
    }

    /**
     * Verificar vigencia del certificado digital
     */
    public function verificarCertificado(Empresa $empresa)
    {
        $vigente = $empresa->certificadoVigente();

        return response()->json([
            'vigente' => $vigente,
            'fecha_expiracion' => $empresa->fecha_expiracion_certificado,
            'mensaje' => $vigente 
                ? 'Certificado digital vigente' 
                : 'Certificado digital vencido o no configurado'
        ]);
    }

    /**
     * Actualizar certificado digital
     */
    public function actualizarCertificado(Request $request, Empresa $empresa)
    {
        $validator = Validator::make($request->all(), [
            'certificado_digital' => 'required|string',
            'clave_certificado' => 'required|string',
            'fecha_expiracion_certificado' => 'required|date|after:today',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $empresa->update([
                'certificado_digital' => $request->certificado_digital,
                'clave_certificado' => $request->clave_certificado,
                'fecha_expiracion_certificado' => $request->fecha_expiracion_certificado,
            ]);

            return response()->json([
                'message' => 'Certificado digital actualizado exitosamente',
                'data' => $empresa->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar el certificado',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar credenciales SOL
     */
    public function actualizarCredencialesSol(Request $request, Empresa $empresa)
    {
        $validator = Validator::make($request->all(), [
            'usuario_sol' => 'required|string|max:100',
            'clave_sol' => 'required|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $empresa->update([
                'usuario_sol' => $request->usuario_sol,
                'clave_sol' => $request->clave_sol,
            ]);

            return response()->json([
                'message' => 'Credenciales SOL actualizadas exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar las credenciales',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cambiar modo (prueba/producción)
     */
    public function cambiarModo(Request $request, Empresa $empresa)
    {
        $validator = Validator::make($request->all(), [
            'modo' => 'required|in:prueba,produccion',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $empresa->update(['modo' => $request->modo]);

            return response()->json([
                'message' => "Modo cambiado a {$request->modo} exitosamente",
                'data' => $empresa->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al cambiar el modo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle PSE autorizado
     */
    public function togglePse(Empresa $empresa)
    {
        try {
            $empresa->pse_autorizado = !$empresa->pse_autorizado;
            $empresa->save();

            return response()->json([
                'message' => $empresa->pse_autorizado 
                    ? 'PSE autorizado' 
                    : 'PSE desautorizado',
                'data' => $empresa
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al cambiar el estado PSE',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Estadísticas de la empresa
     */
    public function estadisticas(Empresa $empresa, Request $request)
    {
        $query = $empresa->comprobantes();

        if ($request->has('fecha_desde') && $request->has('fecha_hasta')) {
            $query->whereBetween('fecha_emision', [$request->fecha_desde, $request->fecha_hasta]);
        }

        $totalComprobantes = (clone $query)->count();
        $totalVentas = (clone $query)->where('estado', 'aceptado_sunat')->sum('total');
        $totalClientes = $empresa->clientes()->count();
        $totalProductos = $empresa->productos()->count();
        $totalUsuarios = $empresa->usuarios()->count();

        return response()->json([
            'comprobantes' => [
                'total' => $totalComprobantes,
                'ventas' => $totalVentas
            ],
            'clientes' => $totalClientes,
            'productos' => $totalProductos,
            'usuarios' => $totalUsuarios,
            'almacenes' => $empresa->almacenes()->count(),
            'series_activas' => $empresa->series()->where('activo', true)->count()
        ]);
    }
}