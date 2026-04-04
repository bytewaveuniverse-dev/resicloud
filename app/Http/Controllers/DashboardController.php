<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    //
    public function getDatosGrafica()
    {
        // Obtener ingresos (Sistema + Especial) pagados por mes
        $ingresosMensuales = \App\Models\Asiento::whereIn('tipo', ['sistema', 'especial'])
            ->where('estado', 'pagado')
            ->whereYear('fecha_pago', date('Y'))
            ->selectRaw('MONTH(fecha_pago) as mes, SUM(monto_dolares) as total')
            ->groupBy('mes')
            ->pluck('total', 'mes')
            ->all();

        // Obtener egresos por mes
        $egresosMensuales = \App\Models\Asiento::where('tipo', 'egreso')
            ->whereYear('fecha', date('Y'))
            ->selectRaw('MONTH(fecha) as mes, SUM(monto_dolares) as total')
            ->groupBy('mes')
            ->pluck('total', 'mes')
            ->all();

        // Rellenar los 12 meses para que la gráfica no se rompa
        $dataIngresos = [];
        $dataEgresos = [];
        for ($i = 1; $i <= 12; $i++) {
            $dataIngresos[] = $ingresosMensuales[$i] ?? 0;
            $dataEgresos[] = $egresosMensuales[$i] ?? 0;
        }

        return [
            'ingresos' => $dataIngresos,
            'egresos' => $dataEgresos
        ];
    }
}
