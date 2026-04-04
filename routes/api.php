<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\PagoController;
use Illuminate\Support\Facades\Route;

// 1. RUTA PÚBLICA (Sin token)
Route::post('/v1/login', [AuthController::class, 'login']);

// 2. RUTAS PROTEGIDAS (Requieren Token Sanctum)
Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    
    // Obtener estadísticas del dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);
    
    // Notificar un pago (Subir capture)
    Route::post('/pagos/notificar', [PagoController::class, 'notificarPago']);
    
    // Cerrar sesión
    Route::post('/logout', [AuthController::class, 'logout']);

    // El administrador aprueba el pago hecho por un vecino
    Route::post('/pagos/validar', [PagoController::class, 'validarPago']);

    //Mostrar todos los asientos
    Route::get('/pagos/asientos', [PagoController::class, 'asientos']);
});
