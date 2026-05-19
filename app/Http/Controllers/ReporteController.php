<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Asiento;
use Barryvdh\DomPDF\Facade\Pdf;

class ReporteController extends Controller
{
    // Muestra el formulario con los inputs
    public function menu()
    {
        if (auth()->user()->tipo_usuario !== 'administrador') {
            abort(403);
        }
        return view('admin.reportes.index');
    }

    // Procesa los filtros y descarga el PDF
   /* public function descargarPdf(Request $request)
    {
        $nombre = $request->get('nombre');
        $estado = $request->get('estado');
        $f_inicio = $request->get('fecha_inicio');
        $f_fin = $request->get('fecha_fin');

        // Pasamos los 4 parámetros al scope
        $asientos = Asiento::reporte($nombre, $estado, $f_inicio, $f_fin)->get();
        
        $totalUsd = $asientos->sum('monto_dolares');

        // Enviamos las fechas a la vista del PDF para que aparezcan en el encabezado
        $pdf = Pdf::loadView('reportes.pdf', compact('asientos', 'nombre', 'estado', 'totalUsd', 'f_inicio', 'f_fin'));
        
        return $pdf->download('reporte_resicloud_' . date('d_m_Y') . '.pdf');
    }
    */

    // Procesa los filtros y genera el PDF detallado y agrupado
   /* public function descargarPdf(Request $request)
    {
        // 1. Captura de parámetros del formulario
        $nombre = $request->get('nombre');
        $estado = $request->get('estado');
        $f_inicio = $request->get('fecha_inicio');
        $f_fin = $request->get('fecha_fin');

        // 2. Consulta a la base de datos usando el ScopeReporte
        $asientos = Asiento::reporte($nombre, $estado, $f_inicio, $f_fin)->get();
        
        // 3. Agrupamos los registros por el nombre del vecino
        $asientosAgrupados = $asientos->groupBy(function($item) {
            return $item->usuario->name;
        });

        // 4. Calculamos el total general del reporte
        $totalUsd = $asientos->sum('monto_dolares');

        // 5. Carga de la vista PDF con todos los datos necesarios
        $pdf = Pdf::loadView('reportes.pdf', compact(
            'asientosAgrupados', 
            'nombre', 
            'estado', 
            'totalUsd', 
            'f_inicio', 
            'f_fin'
        ));

        // 6. Configuración de papel y descarga
        return $pdf->setPaper('letter', 'portrait')
                   ->download('reporte_resicloud_' . date('d_m_Y') . '.pdf');
    }*/

    public function descargarPdf(Request $request)
    {
        // 1. Captura los parámetros (Añadimos 'tipo')
        $nombre = $request->get('nombre');
        $estado = $request->get('estado');
        $tipo = $request->get('tipo'); // <--- CAPTURAR ESTO
        $f_inicio = $request->get('fecha_inicio');
        $f_fin = $request->get('fecha_fin');

        // 2. IMPORTANTE: El orden debe coincidir con el Scope del Modelo
        // Según tu modelo, el orden es: $usuarioNombre, $estado, $tipo, $fechaInicio, $fechaFin
        $asientos = Asiento::reporte($nombre, $estado, $tipo, $f_inicio, $f_fin)->get();
        
        // 3. Agrupamos (Añadimos una validación por si es Egreso y no tiene usuario)
        $asientosAgrupados = $asientos->groupBy(function($item) {
            return $item->usuario->name ?? 'ADMINISTRACIÓN / EGRESOS';
        });

        $totalUsd = $asientos->sum('monto_dolares');

        $pdf = Pdf::loadView('reportes.pdf', compact(
            'asientosAgrupados', 
            'nombre', 
            'estado', 
            'tipo', // <--- PASAR A LA VISTA
            'totalUsd', 
            'f_inicio', 
            'f_fin'
        ));

        return $pdf->setPaper('letter', 'portrait')
                ->download('reporte_resicloud_' . date('d_m_Y') . '.pdf');
    }
}