<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asiento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PagoController extends Controller
{
    /**
     * Notificar el pago de un asiento desde la App Móvil.
     * Sincronizado con la lógica de validación de Resicloud.
     */
    public function notificarPago(Request $request)
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
                        ->where('usuario_id', auth()->id()) // Verifica si tu columna es 'user_id' o 'usuario_id'
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

    public function validarPago(Request $request)
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
            'nuevo_estado' => 'required|in:pagado,pendiente' // Solo permitimos estos dos
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
                'concepto'   => $asiento->concepto,
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
}
