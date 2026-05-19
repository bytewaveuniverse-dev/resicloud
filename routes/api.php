<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\PagoController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

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

    Route::get('/admin/asientos/{id}', [PagoController::class, 'getDetalleAsiento']);

    //seria algo asi

        Route::post('/user/update-token', function (Request $request) {
            // Validamos que el token venga en la petición
            $request->validate([
                'fcm_token' => 'required|string',
            ]);

            // Actualizamos el token del usuario autenticado
            $request->user()->update([
                'fcm_token' => $request->fcm_token
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Token de notificaciones actualizado'
            ]);
        });
    //fin de este espacio
});


/*
Route::middleware('auth:sanctum')->post('/user/update-token', function (Request $request) {
    // Validamos que el token venga en la petición
    $request->validate([
        'fcm_token' => 'required|string',
    ]);

    // Actualizamos el token del usuario autenticado
    $request->user()->update([
        'fcm_token' => $request->fcm_token
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Token de notificaciones actualizado'
    ]);
});*/
