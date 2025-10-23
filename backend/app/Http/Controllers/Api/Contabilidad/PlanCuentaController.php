<?php
// app/Http/Controllers/Api/PlanCuentaController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PlanCuenta;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PlanCuentaController extends Controller
{
    public function index(): JsonResponse
    {
        $cuentas = PlanCuenta::where('activo', true)
            ->orderBy('codigo')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $cuentas,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'codigo' => 'required|string|unique:plan_cuentas,codigo',
            'nombre' => 'required|string|max:255',
            'tipo' => 'required|in:activo,pasivo,patrimonio,ingreso,gasto,resultado',
            'naturaleza' => 'required|in:deudora,acreedora',
            'cuenta_padre_id' => 'nullable|exists:plan_cuentas,id',
            'descripcion' => 'nullable|string',
            'saldo_inicial' => 'nullable|numeric|min:0',
        ]);

        $validated['es_subcuenta'] = isset($validated['cuenta_padre_id']) ? true : false;
        $cuenta = PlanCuenta::create($validated);

        return response()->json([
            'success' => true,
            'data' => $cuenta,
            'message' => 'Cuenta creada correctamente'
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $cuenta = PlanCuenta::with('cuentaPadre', 'subcuentas')->find($id);

        if (!$cuenta) {
            return response()->json([
                'success' => false,
                'message' => 'Cuenta no encontrada'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $cuenta,
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $cuenta = PlanCuenta::find($id);

        if (!$cuenta) {
            return response()->json([
                'success' => false,
                'message' => 'Cuenta no encontrada'
            ], 404);
        }

        $validated = $request->validate([
            'nombre' => 'sometimes|string|max:255',
            'tipo' => 'sometimes|in:activo,pasivo,patrimonio,ingreso,gasto,resultado',
            'naturaleza' => 'sometimes|in:deudora,acreedora',
            'descripcion' => 'nullable|string',
            'saldo_inicial' => 'sometimes|numeric|min:0',
            'activo' => 'sometimes|boolean',
        ]);

        $cuenta->update($validated);

        return response()->json([
            'success' => true,
            'data' => $cuenta,
            'message' => 'Cuenta actualizada correctamente'
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $cuenta = PlanCuenta::find($id);

        if (!$cuenta) {
            return response()->json([
                'success' => false,
                'message' => 'Cuenta no encontrada'
            ], 404);
        }

        $cuenta->update(['activo' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Cuenta eliminada correctamente'
        ]);
    }

    public function porTipo(string $tipo): JsonResponse
    {
        $cuentas = PlanCuenta::where('tipo', $tipo)
            ->where('activo', true)
            ->orderBy('codigo')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $cuentas,
        ]);
    }
}