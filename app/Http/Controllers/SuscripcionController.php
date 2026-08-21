<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class SuscripcionController
{
    /** Pantalla que ve el dueño/empleado cuando el local está vencido o suspendido. */
    public function bloqueada(): View|RedirectResponse
    {
        $negocio = local();

        // Si no hay bloqueo real, no tiene sentido esta pantalla.
        if (! $negocio || ! $negocio->suscripcionBloqueada()) {
            return redirect()->route('dashboard');
        }

        return view('suscripcion.bloqueada', ['negocio' => $negocio]);
    }
}
