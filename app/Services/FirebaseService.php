<?php

namespace App\Services;

use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Contract\Messaging;
use App\Models\Asiento;

class FirebaseService
{
    protected $messaging;

    public function __construct(Messaging $messaging)
    {
        $this->messaging = $messaging;
    }

    public function sendNotification($deviceToken, $title, $body, $data = [])
    {
        // 1. Creamos la notificación visual
        $notification = Notification::create($title, $body);

        // 2. Construimos el mensaje completo
        $message = CloudMessage::withTarget('token', $deviceToken)
            ->withNotification($notification)
            ->withData($data); // Datos extra que la App puede leer en segundo plano

        // 3. ¡Fuego! Enviamos a Google
        return $this->messaging->send($message);
    }

    // Ejemplo de lógica nueva en Laravel sin tocar el móvil
    public function notificarMorosos(FirebaseService $firebase) {
        $morosos = Asiento::where('estado', 'pendiente')
                        ->where('fecha', '<', now()->subDays(5))
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