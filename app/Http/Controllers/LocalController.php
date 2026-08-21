<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocalController
{
    /** Cambiar el local activo (guardado en sesión). */
    public function cambiar(Request $r): RedirectResponse
    {
        $id = (int) $r->input('local_id');

        if (in_array($id, auth()->user()->accessibleNegocioIds())) {
            session(['local_id' => $id]);
        }

        return redirect()->route('dashboard');
    }

    /** Salir del local activo (el admin vuelve al panel). */
    public function salir(): RedirectResponse
    {
        session()->forget('local_id');

        return redirect()->route('admin.locales.index');
    }
}
