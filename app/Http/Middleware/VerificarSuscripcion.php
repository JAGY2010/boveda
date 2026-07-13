<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Bloquea las pantallas operativas de un local cuya suscripción está
 * vencida o suspendida. El admin queda exento (él renueva/suspende).
 * Corre DESPUÉS de CompartirLocales para reusar el local ya resuelto.
 */
class VerificarSuscripcion
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        if ($user && ! $user->isAdmin()) {
            $negocio = local();

            $exentas = $request->routeIs(
                'admin.*',
                'local.cambiar',
                'local.salir',
                'logout',
                'suscripcion.bloqueada',
            );

            if ($negocio && $negocio->suscripcionBloqueada() && ! $exentas) {
                return redirect()->route('suscripcion.bloqueada');
            }
        }

        return $next($request);
    }
}
