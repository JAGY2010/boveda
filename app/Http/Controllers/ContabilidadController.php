<?php

namespace App\Http\Controllers;

use App\Support\Ledger;
use Illuminate\Http\Request;

class ContabilidadController
{
    public function index()
    {
        abort_unless(auth()->user()->puedeVerDinero(), 403);
        $negocio = local();

        $movimientos = $negocio->movimientos()->orderByDesc('id')->limit(15)->get();
        $gastos = $negocio->gastos()->orderByDesc('id')->limit(15)->get();

        return view('contabilidad.index', compact('negocio', 'movimientos', 'gastos'));
    }

    public function capital(Request $r)
    {
        abort_unless(auth()->user()->puedeVerDinero(), 403);
        $negocio = local();

        $data = $r->validate([
            'tipo' => 'required|in:agregar,retirar',
            'monto' => 'required|integer|min:1',
        ]);

        if ($data['tipo'] === 'retirar' && $data['monto'] > $negocio->caja) {
            return back()->with('error', 'No hay suficiente en caja.');
        }

        Ledger::moverCapital($negocio, $data['tipo'], (int) $data['monto']);

        return back()->with('ok', 'Listo');
    }

    public function gasto(Request $r)
    {
        abort_unless(auth()->user()->puedeVerDinero(), 403);
        $negocio = local();

        $data = $r->validate([
            'categoria' => 'required|string|max:100',
            'monto' => 'required|integer|min:1',
            'descripcion' => 'nullable|string|max:255',
        ]);

        Ledger::registrarGasto($negocio, $data['categoria'], (int) $data['monto'], $data['descripcion'] ?? null);

        return back()->with('ok', 'Gasto registrado');
    }
}
