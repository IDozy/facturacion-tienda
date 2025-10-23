<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AsientoDetalle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use DB;

class ReporteController extends Controller
{
    public function balanceGeneral(Request $request): JsonResponse
    {
        $hasta = $request->input('hasta', now()->endOfMonth());

        $data = AsientoDetalle::select(
                'plan_cuentas.tipo',
                'plan_cuentas.codigo',
                'plan_cuentas.nombre',
                DB::raw('SUM(asiento_detalles.debe - asiento_detalles.haber) as saldo')
            )
            ->join('plan_cuentas', 'plan_cuentas.id', '=', 'asiento_detalles.cuenta_id')
            ->join('asientos', 'asientos.id', '=', 'asiento_detalles.asiento_id')
            ->where('asientos.fecha_asiento', '<=', $hasta)
            ->groupBy('plan_cuentas.id', 'plan_cuentas.tipo', 'plan_cuentas.codigo', 'plan_cuentas.nombre')
            ->orderBy('plan_cuentas.codigo')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => 'Balance general generado correctamente'
        ]);
    }

    public function estadoResultados(Request $request): JsonResponse
    {
        $desde = $request->input('desde', now()->startOfYear());
        $hasta = $request->input('hasta', now()->endOfYear());

        $data = AsientoDetalle::select(
                'plan_cuentas.tipo',
                'plan_cuentas.codigo',
                'plan_cuentas.nombre',
                DB::raw('SUM(asiento_detalles.haber - asiento_detalles.debe) as resultado')
            )
            ->join('plan_cuentas', 'plan_cuentas.id', '=', 'asiento_detalles.cuenta_id')
            ->join('asientos', 'asientos.id', '=', 'asiento_detalles.asiento_id')
            ->whereBetween('asientos.fecha_asiento', [$desde, $hasta])
            ->whereIn('plan_cuentas.tipo', ['ingreso', 'gasto'])
            ->groupBy('plan_cuentas.id', 'plan_cuentas.tipo', 'plan_cuentas.codigo', 'plan_cuentas.nombre')
            ->orderBy('plan_cuentas.codigo')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => 'Estado de resultados generado correctamente'
        ]);
    }
}
