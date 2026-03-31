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
    protected $casts = [ 'fecha' => 'date:Y-m-d', ];

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
}

