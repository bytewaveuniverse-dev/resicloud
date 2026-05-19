<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

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

    protected $casts = [ 
        'fecha' => 'date:Y-m-d',
        'fecha_pago' => 'date', 
    ];

    // --- Relaciones ---
    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    // --- Accessors ---
    public function getFechaAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d');
    }

    // --- Lógica de Filtro por Rol (Privada para uso interno) ---
    private static function queryPorRol() {
        $query = static::query();
        if (Auth::user()->tipo_usuario !== 'administrador') {
            $query->where('usuario_id', Auth::id());
        }
        return $query;
    }

    // --- Métodos de Sumatoria Gerencial ---

    /**
     * INGRESOS REALES: Solo lo PAGADO (Sistema + Especial)
     * Si es Admin ve todo, si es Usuario ve lo suyo.
     */
    public static function totalIngresosReales() {
        return self::queryPorRol()
            ->whereIn('tipo', ['sistema', 'especial'])
            ->where('estado', 'pagado')
            ->selectRaw('SUM(monto_dolares) as total_usd, SUM(monto_bs) as total_bs')
            ->first();
    }

    /**
     * EGRESOS: Salidas de dinero del condominio (Siempre Global para transparencia)
     */
    public static function totalEgresos() {
        return static::where('tipo', 'egreso')
            ->selectRaw('SUM(monto_dolares) as total_usd, SUM(monto_bs) as total_bs')
            ->first();
    }

    /*
    public static function totalPorCobrarGlobal() {
        return static::whereIn('tipo', ['sistema', 'especial', 'suscripcion'])
            ->whereIn('estado', ['pendiente', 'moroso', 'por_validar'])
            ->sum('monto_dolares');
    } */

    /*
    public static function miDeudaPendiente() {
        return static::where('usuario_id', Auth::id())
            ->whereIn('estado', ['pendiente', 'moroso', 'por_validar'])
            ->sum('monto_dolares');
    } */

    /**
     * BALANCE NETO: Ingresos Reales Globales - Egresos
     */
    /*public static function balanceNeto() {
        $ingresos = static::whereIn('tipo', ['sistema', 'especial'])
            ->where('estado', 'pagado')
            ->sum('monto_dolares');
        
        $egresos = self::totalEgresos()->total_usd ?? 0;
        return $ingresos - $egresos;
    }
    */

    /**
     * BALANCE NETO GLOBAL: (Ingresos Reales - Egresos) en USD y BS
     */
    public static function balanceNeto() {
        // Suma de Ingresos Reales (Pagados)
        $ingresos = static::whereIn('tipo', ['sistema', 'especial'])
            ->where('estado', 'pagado')
            ->selectRaw('SUM(monto_dolares) as usd, SUM(monto_bs) as bs')
            ->first();
        
        // Suma de Egresos
        $egresos = static::where('tipo', 'egreso')
            ->selectRaw('SUM(monto_dolares) as usd, SUM(monto_bs) as bs')
            ->first();

        return [
            'usd' => ($ingresos->usd ?? 0) - ($egresos->usd ?? 0),
            'bs'  => ($ingresos->bs ?? 0) - ($egresos->bs ?? 0)
        ];
    }

    // --- Métodos para Gráficas y Reportes ---

    public static function datosGraficaMensual($usuarioId = null, $añoActual = null)
    {
        $añoActual = $añoActual ?? date('Y');

        // 1. Ingresos GLOBALES del condominio
        $ingresosGlobales = static::whereIn('tipo', ['sistema', 'especial'])
            ->where('estado', 'pagado')
            ->whereYear('fecha_pago', $añoActual)
            ->selectRaw('MONTH(fecha_pago) as mes, SUM(monto_dolares) as total')
            ->groupBy('mes')->pluck('total', 'mes')->all();

        // 2. Egresos GLOBALES del condominio
        $egresosGlobales = static::where('tipo', 'egreso')
            ->whereYear('fecha', $añoActual)
            ->selectRaw('MONTH(fecha) as mes, SUM(monto_dolares) as total')
            ->groupBy('mes')->pluck('total', 'mes')->all();

        // 3. Aportes PERSONALES del usuario específico
        $misAportes = [];
        if ($usuarioId) {
            $misAportes = static::where('usuario_id', $usuarioId)
                ->where('estado', 'pagado')
                ->whereYear('fecha_pago', $añoActual)
                ->selectRaw('MONTH(fecha_pago) as mes, SUM(monto_dolares) as total')
                ->groupBy('mes')->pluck('total', 'mes')->all();
        }

        $dataIngresos = []; $dataEgresos = []; $dataPersonales = [];

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

    public static function variacionMensualIngresos() {
        $mesActual = date('m'); $añoActual = date('Y');
        $mesPasado = date('m', strtotime("-1 month")); $añoPasado = date('Y', strtotime("-1 month"));

        $actual = static::whereIn('tipo', ['sistema', 'especial'])
            ->where('estado', 'pagado')
            ->whereMonth('fecha_pago', $mesActual)->whereYear('fecha_pago', $añoActual)
            ->sum('monto_dolares');

        $pasado = static::whereIn('tipo', ['sistema', 'especial'])
            ->where('estado', 'pagado')
            ->whereMonth('fecha_pago', $mesPasado)->whereYear('fecha_pago', $añoPasado)
            ->sum('monto_dolares');

        if ($pasado == 0) return $actual > 0 ? 100 : 0;
        return (($actual - $pasado) / $pasado) * 100;
    }

    // --- Métodos de Estatus y Listados ---

    public static function topMorosos() {
        return User::withSum(['asientos' => function ($query) {
                $query->whereIn('estado', ['pendiente', 'moroso']);
            }], 'monto_dolares')
            ->having('asientos_sum_monto_dolares', '>', 0)
            ->orderByDesc('asientos_sum_monto_dolares')
            ->take(10)->get();
    }

    public static function miEstatusSolvencia() {
        $deuda = self::miDeudaPendiente();
        return [
            'monto' => $deuda,
            'label' => $deuda > 0 ? 'Tienes Deuda' : 'Estás Solvente',
            'color' => $deuda > 0 ? 'text-red-500' : 'text-green-500',
            'bg' => $deuda > 0 ? 'bg-red-100' : 'bg-green-100'
        ];
    }

    public static function miUltimoPago() {
        return self::where('usuario_id', Auth::id())
            ->where('estado', 'pagado')
            ->latest('fecha_pago')->first();
    }

  
    // =================================================================
    // NUEVOS MÉTODOS PARA TRANSPARENCIA GLOBAL (AGREGAR AL FINAL)
    // =================================================================

    /**
     * Calcula el ingreso total de TODOS los usuarios (PAGADOS)
     * Se usa en el Card 1 para que el vecino vea el total recaudado.
     */
    public static function totalIngresosGlobales() {
        return static::whereIn('tipo', ['sistema', 'especial'])
            ->where('estado', 'pagado')
            ->sum('monto_dolares');
    }

    /**
     * Calcula el dinero real disponible (Ingresos - Egresos)
     * Se usa en el Card 3 para mostrar la "Caja" a todos.
     */
    public static function balanceEnCajaGlobal() {
        $ingresos = static::whereIn('tipo', ['sistema', 'especial'])
            ->where('estado', 'pagado')
            ->sum('monto_dolares');
        
        $egresos = static::where('tipo', 'egreso')
            ->sum('monto_dolares');
            
        return $ingresos - $egresos;
    }

    /**
     * Suma de deudas de TODA la comunidad
     * Se usa en el Card 4 solo para la vista del Administrador.
     */
    public static function totalPorCobrarGlobal() {
        return static::whereIn('tipo', ['sistema', 'especial', 'suscripcion'])
            ->whereIn('estado', ['pendiente', 'moroso', 'por_validar'])
            ->sum('monto_dolares');
    }

    /**
     * Suma de deuda específica del usuario logueado
     * Se usa en el Card 4 para la vista del Vecino.
     */
    public static function miDeudaPendiente() {
        return static::where('usuario_id', auth()->id())
            ->whereIn('estado', ['pendiente', 'moroso', 'por_validar'])
            ->sum('monto_dolares');
    }

    /**
     * Scope para filtrar reportes de forma dinámica
     */
    // En App\Models\Asiento.php

    public function scopeReporte($query, $usuarioNombre = null, $estado = null, $tipo = null, $fechaInicio = null, $fechaFin = null)
    {
        return $query->with('usuario')
            ->when($usuarioNombre, function ($q) use ($usuarioNombre) {
                $q->whereHas('usuario', function ($relacion) use ($usuarioNombre) {
                    $relacion->where('name', 'like', '%' . $usuarioNombre . '%');
                });
            })
            ->when($estado, function ($q) use ($estado) {
                $q->where('estado', $estado);
            })
            ->when($tipo, function ($q) use ($tipo) {
                $q->where('tipo', $tipo);
            })
            // Nuevo filtro de rango de fechas
            ->when($fechaInicio && $fechaFin, function ($q) use ($fechaInicio, $fechaFin) {
                $q->whereBetween('fecha', [$fechaInicio, $fechaFin]);
            })
            ->orderBy('fecha', 'desc');
    }

}