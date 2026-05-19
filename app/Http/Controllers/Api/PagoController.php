<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asiento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Services\FirebaseService; // No olvides importar el servicio arriba
use App\Models\User;

class PagoController extends Controller
{

    public function notificarPago(Request $request, FirebaseService $firebase)
    {
        // 1. Validamos
        $request->validate([
            'asiento_id' => 'required|exists:asientos,id',
            'monto_bs'   => 'required|numeric',
            'referencia' => 'required|string',
            'fecha_pago' => 'required|date',
            'capture'    => 'required|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        // 2. Buscamos el asiento (Seguridad: Solo si es del usuario autenticado)
        $asiento = \App\Models\Asiento::where('id', $request->asiento_id)
                    ->where('usuario_id', auth()->id()) 
                    ->firstOrFail();

        // 3. Verificamos que no esté pagado ya
        if ($asiento->estado === 'pagado') {
            return response()->json([
                'success' => false, 
                'message' => 'Este asiento ya figura como PAGADO.'
            ], 403);
        }

        // 4. Procesamos el archivo
        if ($request->hasFile('capture')) {
            // Borrar capture viejo si existe
            if ($asiento->capture && \Illuminate\Support\Facades\Storage::disk('public')->exists($asiento->capture)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($asiento->capture);
            }

            // Guardar nuevo archivo
            $path = $request->file('capture')->store('captures', 'public');
            
            // 5. Actualizamos el modelo del Asiento
            $asiento->update([
                'monto_bs'   => $request->monto_bs,
                'referencia' => $request->referencia,
                'fecha_pago' => $request->fecha_pago,
                'capture'    => $path,
                'estado'     => 'por_validar', 
            ]);

            // --- LÓGICA DE NOTIFICACIÓN AL ADMINISTRADOR ---
            
            // Buscamos al usuario que sea administrador
            $admin = User::where('tipo_usuario', 'administrador')->first(); 

            if ($admin && $admin->fcm_token) {
                try {
                    // Obtenemos el nombre del vecino para que el admin sepa quién es
                    $nombreVecino = auth()->user()->name;

                    $firebase->sendNotification(
                        $admin->fcm_token,
                        "Nuevo Pago Registrado 💰",
                        "El vecino {$nombreVecino} ha reportado un pago. Referencia: {$request->referencia}.",
                        [
                            'tipo' => 'pago_pendiente', 
                            'asiento_id' => (string)$asiento->id // FCM prefiere strings en los datos extra
                        ]
                    );
                } catch (\Exception $e) {
                    // Si falla la notificación, solo lo logueamos para no detener el proceso principal
                    \Log::error("Error enviando notificación al admin: " . $e->getMessage());
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Pago registrado con éxito. El administrador ha sido notificado.',
                'data' => [
                    'asiento_id' => $asiento->id,
                    'nuevo_estado' => 'por_validar',
                    'ruta_archivo' => asset('storage/' . $path)
                ]
            ]);
        }

        return response()->json([
            'success' => false, 
            'message' => 'Error: El archivo comprobante no se pudo procesar.'
        ], 400);
    }
    /**
     * Notificar el pago de un asiento desde la App Móvil.
     * Sincronizado con la lógica de validación de Resicloud.
     */
   /* public function notificarPago(Request $request)
    {
        // 1. Validamos (Asegúrate que el campo de la imagen se llame 'capture')
        $request->validate([
            'asiento_id' => 'required|exists:asientos,id',
            'monto_bs'   => 'required|numeric',
            'referencia' => 'required|string',
            'fecha_pago' => 'required|date',
            'capture'    => 'required|image|mimes:jpg,jpeg,png|max:2048', // Agregamos mimes por seguridad
        ]);

        // 2. Buscamos el asiento (Seguridad: Solo si es del usuario autenticado)
        $asiento = \App\Models\Asiento::where('id', $request->asiento_id)
                        ->where('usuario_id', auth()->id()) 
                        ->firstOrFail();

        // 3. Verificamos que no esté pagado ya
        if ($asiento->estado === 'pagado') {
            return response()->json([
                'success' => false, 
                'message' => 'Este asiento ya figura como PAGADO y no puede ser modificado.'
            ], 403);
        }

        // 4. Procesamos el archivo
        if ($request->hasFile('capture')) {
            // Borrar capture viejo si existe (Limpieza de disco)
            if ($asiento->capture && \Illuminate\Support\Facades\Storage::disk('public')->exists($asiento->capture)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($asiento->capture);
            }

            // Guardar nuevo archivo
            $path = $request->file('capture')->store('captures', 'public');
            
            // 5. Actualizamos el modelo
            $asiento->update([
                'monto_bs'   => $request->monto_bs,
                'referencia' => $request->referencia,
                'fecha_pago' => $request->fecha_pago,
                'capture'    => $path,
                'estado'     => 'por_validar', 
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pago registrado con éxito. Pendiente de validación por el administrador.',
                'data' => [
                    'asiento_id' => $asiento->id,
                    'nuevo_estado' => 'por_validar',
                    'ruta_archivo' => asset('storage/' . $path) // Esto le sirve a la App para mostrar la foto
                ]
            ]);
        }

        return response()->json([
            'success' => false, 
            'message' => 'Error: El archivo comprobante no se pudo procesar.'
        ], 400);
    }

    */

   

    public function validarPago(Request $request, FirebaseService $firebase)
    {
        // 1. Verificación de Seguridad Gerencial
        if (auth()->user()->tipo_usuario !== 'administrador') {
            return response()->json([
                'success' => false,
                'message' => 'No tiene permisos para realizar esta acción.'
            ], 403);
        }

        // 2. Validación de entrada
        $request->validate([
            'asiento_id' => 'required|exists:asientos,id',
            'nuevo_estado' => 'required|in:pagado,rechazado,pendiente'
        ]);

        // 3. Proceso de actualización (Cargamos la relación 'usuario' para tener el token)
        $asiento = Asiento::with('usuario')->findOrFail($request->asiento_id);

        // Solo podemos validar pagos que estén 'por_validar'
        if ($asiento->estado !== 'por_validar') {
            return response()->json([
                'success' => false,
                'message' => 'Este asiento no está en proceso de validación.'
            ], 400);
        }

        $asiento->update([
            'estado' => $request->nuevo_estado,
            'updated_at' => now(),
        ]);

        // --- LÓGICA DE NOTIFICACIÓN AL VECINO ---

        // Verificamos que el dueño del asiento (el vecino) tenga un token
        if ($asiento->usuario && $asiento->usuario->fcm_token) {
            try {
                // Personalizamos el mensaje según el resultado
                $titulo = "";
                $mensaje = "";
                
                if ($request->nuevo_estado === 'pagado') {
                    $titulo = "¡Pago Validado! ✅";
                    $mensaje = "Hola {$asiento->usuario->name}, tu pago ha sido verificado con éxito. ¡Gracias!";
                } elseif ($request->nuevo_estado === 'rechazado') {
                    $titulo = "Pago Rechazado ❌";
                    $mensaje = "Tu reporte de pago presenta inconvenientes. Por favor, verifica los datos e intenta de nuevo.";
                }

                // Solo enviamos si hay un estado que amerite notificación (pagado o rechazado)
                if ($titulo !== "") {
                    $firebase->sendNotification(
                        $asiento->usuario->fcm_token,
                        $titulo,
                        $mensaje,
                        [
                            'tipo' => 'cambio_estado_pago',
                            'asiento_id' => (string)$asiento->id,
                            'nuevo_estado' => $request->nuevo_estado
                        ]
                    );
                }
            } catch (\Exception $e) {
                \Log::error("Fallo al enviar push al vecino: " . $e->getMessage());
            }
        }

        return response()->json([
            'success' => true,
            'message' => "El pago ha sido marcado como: " . strtoupper($request->nuevo_estado) . " y el vecino ha sido notificado.",
            'data' => [
                'asiento_id' => $asiento->id,
                'estado_final' => $asiento->estado
            ]
        ]);
    }

   /* public function validarPago(Request $request)
    {
        // 1. Verificación de Seguridad Gerencial
        if (auth()->user()->tipo_usuario !== 'administrador') {
            return response()->json([
                'success' => false,
                'message' => 'No tiene permisos para realizar esta acción.'
            ], 403);
        }

        // 2. Validación de entrada
        $request->validate([
            'asiento_id' => 'required|exists:asientos,id',
            'nuevo_estado' => 'required|in:pagado,rechazado,pendiente' // Solo permitimos estos dos
        ]);

        // 3. Proceso de actualización
        $asiento = \App\Models\Asiento::findOrFail($request->asiento_id);

        // Solo podemos validar pagos que estén 'por_validar'
        if ($asiento->estado !== 'por_validar') {
            return response()->json([
                'success' => false,
                'message' => 'Este asiento no está en proceso de validación.'
            ], 400);
        }

        $asiento->update([
            'estado' => $request->nuevo_estado,
            'updated_at' => now(), // Opcional: para auditoría
        ]);

        return response()->json([
            'success' => true,
            'message' => "El pago ha sido marcado como: " . strtoupper($request->nuevo_estado) . " exitosamente.",
            'data' => [
                'asiento_id' => $asiento->id,
                'estado_final' => $asiento->estado
            ]
        ]);
    }
    */

    public function asientos(Request $request)
    {
        $user = $request->user();
        
        // Iniciamos la consulta base
        $query = \App\Models\Asiento::query();

        if ($user->tipo_usuario === 'administrador') {
            // El ADMIN ve TODO, y cargamos el nombre del usuario relacionado (Eager Loading)
            $asientos = $query->with('usuario:id,name')->orderBy('created_at', 'desc')->get();
        } else {
            // EL VECINO solo ve sus registros
            $asientos = $query->where('usuario_id', $user->id)
                            ->orderBy('created_at', 'desc')
                            ->get();
        }

        // Estructuramos la respuesta para que sea fácil de leer en el móvil
        $resumen = $asientos->map(function ($asiento) use ($user) {
            $item = [
                'id'         => $asiento->id,
                'concepto'   => $asiento->descripcion,
                'monto_usd'  => (float) $asiento->monto_dolares,
                'monto_bs'   => (float) $asiento->monto_bs,
                'estado'     => $asiento->estado,
                'fecha'      => \Carbon\Carbon::parse($asiento->created_at)->format('d/m/Y'),
                'tiene_capture' => $asiento->capture ? true : false,
            ];

            // Si es admin, le añadimos quién es el dueño de ese asiento
            if ($user->tipo_usuario === 'administrador') {
                $item['vecino'] = $asiento->usuario->name ?? 'Sistema';
            }

            return $item;
        });

        return response()->json([
            'success' => true,
            'count'   => $asientos->count(),
            'data'    => $resumen
        ]);
    }

    public function getDetalleAsiento($id)
    {
        // 1. Verificación de seguridad
        if (auth()->user()->tipo_usuario !== 'administrador') {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        // 2. Buscar el asiento con su usuario
        $asiento = \App\Models\Asiento::with('usuario')->findOrFail($id);

        // Forzamos la IP y el puerto para que el teléfono lo encuentre en la red local
        $baseUrl = "http://192.168.1.103:8000";

        return response()->json([
            'success' => true,
            'data' => [
                'id'           => $asiento->id,
                'concepto'     => $asiento->descripcion,
                'monto_usd'    => (float) $asiento->monto_dolares,
                'monto_bs'     => (float) $asiento->monto_bs,
                'referencia'   => $asiento->referencia,
                'fecha'        => $asiento->created_at->format('d/m/Y'),
                'estado'       => $asiento->estado,
                'vecino'       => $asiento->usuario->name ?? 'Sistema',
                // Generamos la URL completa para que Android la pueda descargar
                'capture_url' => $asiento->capture ? $baseUrl . '/storage/' . $asiento->capture : null,
                // 'capture_url'  => $asiento->capture ? asset($baseUrl . 'storage/' . $asiento->capture) : null,
            ]
        ]);
    }

    // Ejemplo de lógica nueva en Laravel sin tocar el móvil LUEGO ARREGLAR *****+
    public function notificarMorosos(FirebaseService $firebase) {
        $morosos = Asiento::where('estado', 'pendiente')
                        ->where('fecha_vencimiento', '<', now()->subDays(5))
                        ->get();

        foreach ($morosos as $asiento) {
            $firebase->sendNotification(
                $asiento->usuario->fcm_token,
                "Recordatorio de Pago ⚠️",
                "Hola, notamos que tienes 5 días de retraso en tu pago. ¡Evita recargos!"
            );
        }
    }
}
