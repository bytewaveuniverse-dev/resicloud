<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asiento extends Model
{
    use HasFactory;

    protected $fillable = [
        'usuario_id',
        'tipo',
        'descripcion',
        'monto_dolares',
        'monto_bs',
        'estado',
        'referencia',
        'capture',
        'fecha',
        'fecha_pago',
    ];

    //protected $casts = [ 'fecha' => 'date', ]; 
    //protected $casts = [ 'fecha' => 'date:Y-m-d', ];
    protected $casts = [ 
        'fecha' => 'date:Y-m-d',
        'fecha_pago' => 'date', // <--- Agregue esto
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    // Métodos de ayuda 
    public static function totalPendiente() { 
        return static::where('estado', 'pendiente')->sum('monto_dolares'); 
        } 
    public static function totalPagado() { 
        return static::where('estado', 'pagado')->sum('monto_dolares'); 
        }

    public function getFechaAttribute($value)
    {
        return \Carbon\Carbon::parse($value)->format('Y-m-d');
    }

    // Agregue esto a su modelo Asiento.php

    /**
     * Filtra sumatorias según el rol del usuario
     */
    private static function queryPorRol() {
        $query = static::query();
        if (auth()->user()->tipo_usuario !== 'administrador') {
            $query->where('usuario_id', auth()->id());
        }
        return $query;
    }

    // INGRESOS: Sistema + Especial (Solo lo PAGADO es ingreso real)
    public static function totalIngresosReales() {
        return self::queryPorRol()
            ->whereIn('tipo', ['sistema', 'especial'])
            ->where('estado', 'pagado')
            ->selectRaw('SUM(monto_dolares) as total_usd, SUM(monto_bs) as total_bs')
            ->first();
    }

    // SUSCRIPCIÓN: Solo Admin (Separado como pidió)
    public static function totalSuscripciones() {
        return static::where('tipo', 'suscripcion')
            ->where('estado', 'pagado')
            ->selectRaw('SUM(monto_dolares) as total_usd, SUM(monto_bs) as total_bs')
            ->first();
    }

    // EGRESOS: Todo lo que salió
    public static function totalEgresos() {
        return static::where('tipo', 'egreso')
            ->selectRaw('SUM(monto_dolares) as total_usd, SUM(monto_bs) as total_bs')
            ->first();
    }

    // CUENTAS POR COBRAR: Lo que está pendiente o moroso (Cualquier tipo de ingreso)
    public static function totalCuentasPorCobrar() {
        return self::queryPorRol()
            ->whereIn('tipo', ['sistema', 'especial', 'suscripcion'])
            ->whereIn('estado', ['pendiente', 'moroso', 'por_validar'])
            ->selectRaw('SUM(monto_dolares) as total_usd, SUM(monto_bs) as total_bs')
            ->first();
    }

    
    /*public static function datosGraficaMensual($usuarioId = null, $añoActual = null)
    {
        // Si no viene un año, usamos el actual por defecto
        $añoActual = $añoActual ?? date('Y');

        // El resto del código se mantiene igual, usando $añoActual en los whereYear...
        $ingresosGlobales = self::queryPorRol()
            ->whereIn('tipo', ['sistema', 'especial'])
            ->where('estado', 'pagado')
            ->whereYear('fecha_pago', $añoActual) // <--- Ya usa la variable
            ->selectRaw('MONTH(fecha_pago) as mes, SUM(monto_dolares) as total')
            ->groupBy('mes')->pluck('total', 'mes')->all();

        // 2. Egresos Globales - Lo que gasta el condominio
        $egresosGlobales = self::where('tipo', 'egreso')
            ->whereYear('fecha', $añoActual)
            ->selectRaw('MONTH(fecha) as mes, SUM(monto_dolares) as total')
            ->groupBy('mes')->pluck('total', 'mes')->all();

        // 3. Aportes Personales (Solo si no es admin o si queremos mostrarlo)
        $misAportes = [];
        if ($usuarioId) {
            $misAportes = self::where('usuario_id', $usuarioId)
                ->where('estado', 'pagado')
                ->whereYear('fecha_pago', $añoActual)
                ->selectRaw('MONTH(fecha_pago) as mes, SUM(monto_dolares) as total')
                ->groupBy('mes')->pluck('total', 'mes')->all();
        }

        $dataIngresos = [];
        $dataEgresos = [];
        $dataPersonales = [];

        for ($i = 1; $i <= 12; $i++) {
            $dataIngresos[] = $ingresosGlobales[$i] ?? 0;
            $dataEgresos[] = $egresosGlobales[$i] ?? 0;
            $dataPersonales[] = $misAportes[$i] ?? 0;
        }

        return [
            'ingresos' => $dataIngresos,
            'egresos' => $dataEgresos,
            'personales' => $dataPersonales
        ];
    }*/

    // --- Añadir estos métodos al Modelo Asiento ---

    /**
     * BALANCE NETO: Ingresos Reales - Egresos
     */
    public static function balanceNeto() {
        $ingresos = self::totalIngresosReales()->total_usd ?? 0;
        $egresos = self::totalEgresos()->total_usd ?? 0;
        return $ingresos - $egresos;
    }

    /**
     * COMPARATIVA MENSUAL: % de variación respecto al mes anterior (Solo Ingresos)
     */
    public static function variacionMensualIngresos() {
        $mesActual = date('m');
        $añoActual = date('Y');
        $mesPasado = date('m', strtotime("-1 month"));
        $añoPasado = date('Y', strtotime("-1 month"));

        $actual = self::whereIn('tipo', ['sistema', 'especial'])
            ->where('estado', 'pagado')
            ->whereMonth('fecha_pago', $mesActual)
            ->whereYear('fecha_pago', $añoActual)
            ->sum('monto_dolares');

        $pasado = self::whereIn('tipo', ['sistema', 'especial'])
            ->where('estado', 'pagado')
            ->whereMonth('fecha_pago', $mesPasado)
            ->whereYear('fecha_pago', $añoPasado)
            ->sum('monto_dolares');

        if ($pasado == 0) return $actual > 0 ? 100 : 0;
        
        return (($actual - $pasado) / $pasado) * 100;
    }

    /**
     * TOP 5 MOROSOS: Solo para vista de Administrador
     */
    public static function topMorosos() {
        return \App\Models\User::withSum(['asientos' => function ($query) {
                $query->whereIn('estado', ['pendiente', 'moroso']);
            }], 'monto_dolares')
            ->having('asientos_sum_monto_dolares', '>', 0)
            ->orderByDesc('asientos_sum_monto_dolares')
            ->take(10)
            ->get();
    }

    public static function miEstatusSolvencia() {
        $deuda = self::where('usuario_id', auth()->id())
            ->whereIn('estado', ['pendiente', 'moroso'])
            ->sum('monto_dolares');

        return [
            'monto' => $deuda,
            'label' => $deuda > 0 ? 'Tienes Deuda' : 'Estás Solvente',
            'color' => $deuda > 0 ? 'text-red-500' : 'text-green-500',
            'bg' => $deuda > 0 ? 'bg-red-100' : 'bg-green-100'
        ];
    }

    public static function miUltimoPago() {
        return self::where('usuario_id', auth()->id())
            ->where('estado', 'pagado')
            ->latest('fecha_pago')
            ->first();
    }

    public static function datosGraficaMensual($usuarioId = null, $añoActual = null)
    {
        $añoActual = $añoActual ?? date('Y');

        // 1. Ingresos GLOBALES (Quitamos queryPorRol para que SIEMPRE sea el total de la comunidad)
        $ingresosGlobales = static::whereIn('tipo', ['sistema', 'especial'])
            ->where('estado', 'pagado')
            ->whereYear('fecha_pago', $añoActual)
            ->selectRaw('MONTH(fecha_pago) as mes, SUM(monto_dolares) as total')
            ->groupBy('mes')
            ->pluck('total', 'mes')
            ->all();

        // 2. Egresos GLOBALES - Lo que gasta el condominio (Ya estaba bien, pero aseguramos)
        $egresosGlobales = static::where('tipo', 'egreso')
            ->whereYear('fecha', $añoActual)
            ->selectRaw('MONTH(fecha) as mes, SUM(monto_dolares) as total')
            ->groupBy('mes')
            ->pluck('total', 'mes')
            ->all();

        // 3. Aportes PERSONALES - Solo para la línea verde del usuario
        $misAportes = [];
        if ($usuarioId) {
            $misAportes = static::where('usuario_id', $usuarioId)
                ->where('estado', 'pagado')
                ->whereYear('fecha_pago', $añoActual)
                ->selectRaw('MONTH(fecha_pago) as mes, SUM(monto_dolares) as total')
                ->groupBy('mes')
                ->pluck('total', 'mes')
                ->all();
        }

        $dataIngresos = [];
        $dataEgresos = [];
        $dataPersonales = [];

        for ($i = 1; $i <= 12; $i++) {
            $dataIngresos[] = $ingresosGlobales[$i] ?? 0;
            $dataEgresos[] = $egresosGlobales[$i] ?? 0;
            $dataPersonales[] = $misAportes[$i] ?? 0;
        }

        return [
            'ingresos' => $dataIngresos,
            'egresos' => $dataEgresos,
            'personales' => $dataPersonales
        ];
    }
   
}

