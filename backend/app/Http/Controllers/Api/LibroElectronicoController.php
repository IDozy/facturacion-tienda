<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LibroElectronico;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class LibroElectronicoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = LibroElectronico::with(['periodoContable']);

        // Filtros
        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->has('tipo_libro')) {
            $query->porTipo($request->tipo_libro);
        }

        if ($request->has('periodo_contable_id')) {
            $query->where('periodo_contable_id', $request->periodo_contable_id);
        }

        if ($request->has('año')) {
            $query->whereHas('periodoContable', function ($q) use ($request) {
                $q->where('año', $request->año);
            });
        }

        if ($request->has('mes')) {
            $query->whereHas('periodoContable', function ($q) use ($request) {
                $q->where('mes', $request->mes);
            });
        }

        // Ordenamiento
        $sortBy = $request->get('sort_by', 'fecha_generacion');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginación
        $perPage = $request->get('per_page', 15);
        $libros = $query->paginate($perPage);

        return response()->json($libros);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'periodo_contable_id' => 'required|exists:periodos_contables,id',
            'tipo_libro' => 'required|in:050100,080100,080200,140100,140200',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $libro = LibroElectronico::create([
                'periodo_contable_id' => $request->periodo_contable_id,
                'tipo_libro' => $request->tipo_libro,
                'estado' => LibroElectronico::ESTADO_GENERADO,
            ]);

            return response()->json([
                'message' => 'Libro electrónico creado exitosamente',
                'data' => $libro->load('periodoContable')
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear el libro electrónico',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(LibroElectronico $libroElectronico)
    {
        return response()->json([
            'data' => $libroElectronico->load(['periodoContable'])
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, LibroElectronico $libroElectronico)
    {
        $validator = Validator::make($request->all(), [
            'periodo_contable_id' => 'sometimes|required|exists:periodos_contables,id',
            'tipo_libro' => 'sometimes|required|in:050100,080100,080200,140100,140200',
            'estado' => 'sometimes|in:generado,enviado,rechazado',
            'motivo_rechazo' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $libroElectronico->update($request->all());

            return response()->json([
                'message' => 'Libro electrónico actualizado exitosamente',
                'data' => $libroElectronico->fresh(['periodoContable'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar el libro electrónico',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(LibroElectronico $libroElectronico)
    {
        try {
            // Eliminar archivo físico si existe
            if ($libroElectronico->archivo_txt && Storage::exists($libroElectronico->archivo_txt)) {
                Storage::delete($libroElectronico->archivo_txt);
            }

            $libroElectronico->delete();

            return response()->json([
                'message' => 'Libro electrónico eliminado exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar el libro electrónico',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generar libro electrónico
     */
    public function generar(LibroElectronico $libroElectronico)
    {
        try {
            $libroElectronico->generarYGuardar();

            return response()->json([
                'message' => 'Libro electrónico generado exitosamente',
                'data' => $libroElectronico->fresh(),
                'archivo' => $libroElectronico->archivo_txt,
                'hash' => $libroElectronico->hash_archivo
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al generar el libro electrónico',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generar nuevo libro
     */
    public function generarNuevo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'periodo_contable_id' => 'required|exists:periodos_contables,id',
            'tipo_libro' => 'required|in:050100,080100,080200,140100,140200',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $libro = LibroElectronico::create([
                'periodo_contable_id' => $request->periodo_contable_id,
                'tipo_libro' => $request->tipo_libro,
                'estado' => LibroElectronico::ESTADO_GENERADO,
            ]);

            $libro->generarYGuardar();

            return response()->json([
                'message' => 'Libro electrónico generado exitosamente',
                'data' => $libro->load('periodoContable')
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al generar el libro electrónico',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Descargar archivo TXT
     */
    public function descargar(LibroElectronico $libroElectronico)
    {
        if (!$libroElectronico->archivo_txt) {
            return response()->json([
                'message' => 'El libro no tiene archivo generado'
            ], 404);
        }

        if (!Storage::exists($libroElectronico->archivo_txt)) {
            return response()->json([
                'message' => 'El archivo no existe en el sistema'
            ], 404);
        }

        try {
            $nombreArchivo = $libroElectronico->generarNombreArchivo();

            return response()->download(
                Storage::path($libroElectronico->archivo_txt),
                $nombreArchivo,
                ['Content-Type' => 'text/plain']
            );
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al descargar el archivo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Marcar como enviado a SUNAT
     */
    public function marcarEnviado(LibroElectronico $libroElectronico)
    {
        try {
            $libroElectronico->marcarComoEnviado();

            return response()->json([
                'message' => 'Libro marcado como enviado exitosamente',
                'data' => $libroElectronico->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al marcar como enviado',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Marcar como rechazado
     */
    public function marcarRechazado(Request $request, LibroElectronico $libroElectronico)
    {
        $validator = Validator::make($request->all(), [
            'motivo_rechazo' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $libroElectronico->marcarComoRechazado($request->motivo_rechazo);

            return response()->json([
                'message' => 'Libro marcado como rechazado exitosamente',
                'data' => $libroElectronico->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al marcar como rechazado',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener tipos de libro disponibles
     */
    public function tipos()
    {
        $tipos = [
            ['codigo' => LibroElectronico::LIBRO_VENTAS, 'nombre' => 'Registro de Ventas'],
            ['codigo' => LibroElectronico::LIBRO_COMPRAS, 'nombre' => 'Registro de Compras'],
            ['codigo' => LibroElectronico::LIBRO_DIARIO, 'nombre' => 'Libro Diario'],
            ['codigo' => LibroElectronico::LIBRO_MAYOR, 'nombre' => 'Libro Mayor'],
            ['codigo' => LibroElectronico::REGISTRO_CAJA_BANCOS, 'nombre' => 'Registro de Caja y Bancos'],
        ];

        return response()->json([
            'data' => $tipos
        ]);
    }

    /**
     * Libros pendientes de envío
     */
    public function pendientesEnvio()
    {
        $libros = LibroElectronico::pendientesEnvio()
            ->with(['periodoContable'])
            ->get();

        return response()->json([
            'data' => $libros,
            'count' => $libros->count()
        ]);
    }

    /**
     * Estadísticas
     */
    public function estadisticas(Request $request)
    {
        $query = LibroElectronico::query();

        if ($request->has('año')) {
            $query->whereHas('periodoContable', function ($q) use ($request) {
                $q->where('año', $request->año);
            });
        }

        $generados = (clone $query)->generados()->count();
        $enviados = (clone $query)->enviados()->count();
        $rechazados = (clone $query)->rechazados()->count();
        $total = $generados + $enviados + $rechazados;

        $porTipo = (clone $query)->select('tipo_libro')
            ->selectRaw('COUNT(*) as cantidad')
            ->groupBy('tipo_libro')
            ->get();

        return response()->json([
            'generados' => $generados,
            'enviados' => $enviados,
            'rechazados' => $rechazados,
            'total' => $total,
            'por_tipo' => $porTipo
        ]);
    }

    /**
     * Regenerar libro
     */
    public function regenerar(LibroElectronico $libroElectronico)
    {
        try {
            // Eliminar archivo anterior si existe
            if ($libroElectronico->archivo_txt && Storage::exists($libroElectronico->archivo_txt)) {
                Storage::delete($libroElectronico->archivo_txt);
            }

            // Generar nuevamente
            $libroElectronico->generarYGuardar();

            return response()->json([
                'message' => 'Libro regenerado exitosamente',
                'data' => $libroElectronico->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al regenerar el libro',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}