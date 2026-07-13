<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CompartirLocales
{
    /**
     * Comparte con todas las vistas el local activo y los locales a los
     * que el usuario tiene acceso (para el selector y el menú).
     */
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check()) {
            view()->share('localActual', local());
            view()->share('localesAccesibles', auth()->user()->accessibleNegocios());
        }

        return $next($request);
    }
}
