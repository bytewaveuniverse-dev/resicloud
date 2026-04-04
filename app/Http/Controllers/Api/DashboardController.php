<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asiento;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $esAdmin = $user->tipo_usuario === 'administrador';
        
        // Obtenemos el año de la petición o el actual (igual que en tu Blade)
        $añoSeleccionado = $request->get('year', date('Y'));

        $data = [
            'success' => true,
            'año_fiscal' => $añoSeleccionado,
            'perfil' => [
                'nombre' => $user->name,
                'rol' => $user->tipo_usuario
            ],
            'datos_numericos' => []
        ];

        if ($esAdmin) {
            // --- DATOS NUMÉRICOS PARA ADMINISTRADOR (GLOBALES) ---
            $data['datos_numericos'] = [
                'ingresos_globales'  => (float) Asiento::totalIngresosReales()->total_usd,
                'egresos_condominio' => (float) (Asiento::totalEgresos()->total_usd ?? 0),
                'total_por_cobrar'   => (float) Asiento::totalPorCobrarGlobal(),
                'balance_en_caja'    => (float) Asiento::balanceNeto(),
                'variacion_ingresos' => (float) Asiento::variacionMensualIngresos(),
                'pagos_por_validar'  => (int) Asiento::where('estado', 'por_validar')->count(),
                'top_morosos'        => Asiento::topMorosos()->map(fn($m) => [
                    'nombre' => $m->name,
                    'deuda'  => (float) $m->asientos_sum_monto_dolares
                ])
            ];
        } else {
            // --- DATOS NUMÉRICOS PARA VECINO (PERSONALES + REFERENCIA GLOBAL) ---
            $solvencia = Asiento::miEstatusSolvencia();
            $ultimoPago = Asiento::miUltimoPago();

            $data['datos_numericos'] = [
                'total_ingresos_globales' => (float) Asiento::totalIngresosGlobales(),
                'balance_en_caja' => (float) Asiento::balanceNeto(),
                'mis_pagos_totales'   => (float) $user->asientos()->where('estado', 'pagado')->sum('monto_dolares'),
                'mi_deuda_pendiente'  => (float) Asiento::miDeudaPendiente(),
                'egresos_condominio'  => (float) (Asiento::totalEgresos()->total_usd ?? 0), // Referencia para el vecino
                'estatus_solvencia'   => (float) $solvencia['monto'], 
                'ultimo_pago_monto'   => $ultimoPago ? (float) $ultimoPago->monto_dolares : 0,
                'ultimo_pago_fecha'   => $ultimoPago ? $ultimoPago->fecha_pago : null,
                'variacion_ingresos_global' => (float) Asiento::variacionMensualIngresos()
            ];
        }

        return response()->json($data);
    }
}