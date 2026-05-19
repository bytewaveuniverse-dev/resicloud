<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReporteController;


Route::get('/', function () {
    return view('welcome');
})->name('home');


Route::livewire('/post/create', 'pages::post.create');

Route::livewire('/asientos', 'pages::asientos.index')->name('asientos');
Route::livewire('/usuarios', 'pages::usuarios.index')->name('usuarios');


Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');


Route::middleware(['auth'])->group(function () {
    // Vista del menú de reportes (donde están los inputs)
    Route::get('/admin/reportes', [ReporteController::class, 'menu'])->name('reportes.menu');
    
    // Acción que genera el PDF
    Route::get('/admin/reportes/generar', [ReporteController::class, 'descargarPdf'])->name('reportes.generar');
});

// Solo para probar en routes/web.php
/*Route::get('/test-push', function(\App\Services\FirebaseService $firebase) {
    $asiento = \App\Models\Asiento::with('usuario')->first(); // Tome el primero para probar
    
    if ($asiento && $asiento->usuario->fcm_token) {
        $firebase->sendNotification(
            $asiento->usuario->fcm_token,
            "¡Prueba de Resicloud! ",
            "Hola {$asiento->usuario->name}, si ves esto, el sistema de notificaciones está ACTIVO."
        );
        return "Notificación enviada con éxito a " . $asiento->usuario->name;
    }
    
    return "Error: El usuario no tiene token de Firebase.";
});
*/


require __DIR__.'/settings.php';

//para mi servidor en produccion, donde separo el contenido en mis carpetas de laravel
Route::get('/storage-link', function() {
    // 1. Definimos dónde está la carpeta real de las fotos
    // Según tu contexto: simba/full/storage/app/public
    $target = base_path('storage/app/public'); 

    // 2. Definimos dónde queremos el "acceso directo"
    // Según tu estructura: simba/storage (la raíz donde moviste el contenido de public)
    $shortcut = public_path('storage'); 

    // 3. Verificamos si ya existe algo para no romper nada
    if (file_exists($shortcut)) {
        return "El enlace o carpeta ya existe en: $shortcut. Por seguridad, renómbralo por FTP/Administrador de archivos antes de reintentar.";
    }

    // 4. Creamos el enlace simbólico manualmente
    try {
        app()->make('files')->link($target, $shortcut);
        return "¡Éxito! Enlace creado: de [$shortcut] apuntando a [$target]";
    } catch (\Exception $e) {
        return "Error al crear el enlace: " . $e->getMessage();
    }
});