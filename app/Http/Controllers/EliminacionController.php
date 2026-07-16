<?php

namespace App\Http\Controllers;

class EliminacionController
{
    /** Historial de empeños eliminados: solo el dueño (y admin). */
    public function index()
    {
        abort_unless(auth()->user()->puedeEditar(), 403);

        $eliminaciones = local()->eliminaciones()->latest()->paginate(50);

        return view('eliminaciones.index', compact('eliminaciones'));
    }
}
