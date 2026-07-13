<?php

namespace App\Http\Controllers;

class ConsolidadoController
{
    public function index()
    {
        $user = auth()->user();
        abort_unless($user->puedeVerDinero() && $user->hasMultipleLocales(), 403);

        $locales = $user->accessibleNegocios();

        return view('consolidado.index', compact('locales'));
    }
}
