<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LocalController
{
    /** Cambiar el local activo (guardado en sesión). */
    public function cambiar(Request $r)
    {
        $id = (int) $r->input('local_id');

        if (in_array($id, auth()->user()->accessibleNegocioIds())) {
            session(['local_id' => $id]);
        }

        return redirect()->route('dashboard');
    }
}
