<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class ConsolidadoController
{
    public function index(): View
    {
        $user = auth()->user();
        abort_unless($user->puedeVerDinero() && $user->hasMultipleLocales(), 403);

        $locales = $user->accessibleNegocios();

        return view('consolidado.index', compact('locales'));
    }
}
