<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CompartirLocales
{
    /**
     * Comparte con todas las vistas el local activo y los locales a los
     * que el usuario tiene acceso (para el selector y el menú).
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            $negocio = local();

            view()->share('localActual', $negocio);
            view()->share('localesAccesibles', auth()->user()->accessibleNegocios());

            // Sin local asignado, las pantallas operativas no aplican:
            // el admin va al panel a crear el primero; a los demás se les avisa.
            if (! $negocio && ! $request->routeIs('admin.*', 'local.cambiar')) {
                return auth()->user()->isAdmin()
                    ? redirect()->route('admin.locales.index')
                    : abort(403, 'Aún no tienes un local asignado. Pídele al administrador que te asigne uno.');
            }
        }

        return $next($request);
    }
}
