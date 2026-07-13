<?php

namespace App\Http\Controllers;

class SuscripcionController
{
    /** Pantalla que ve el dueño/empleado cuando el local está vencido o suspendido. */
    public function bloqueada()
    {
        $negocio = local();

        // Si no hay bloqueo real, no tiene sentido esta pantalla.
        if (! $negocio || ! $negocio->suscripcionBloqueada()) {
            return redirect()->route('dashboard');
        }

        return view('suscripcion.bloqueada', ['negocio' => $negocio]);
    }
}
