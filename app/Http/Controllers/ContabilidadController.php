<?php

namespace App\Http\Controllers;

use App\Support\Ledger;
use Illuminate\Http\Request;

class ContabilidadController
{
    public function index()
    {
        // El empleado ve solo la caja y puede registrar gastos; el dueño/admin ve todo.
        $negocio = local();
        $verDinero = auth()->user()->puedeVerDinero();

        $movimientos = $verDinero ? $negocio->movimientos()->orderByDesc('id')->limit(15)->get() : collect();
        $gastos = $negocio->gastos()->orderByDesc('fecha')->orderByDesc('id')->limit(15)->get();

        return view('contabilidad.index', compact('negocio', 'movimientos', 'gastos', 'verDinero'));
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
        // El empleado también puede registrar gastos.
        $negocio = local();

        $data = $r->validate([
            'categoria' => 'required|string|max:100',
            'monto' => 'required|integer|min:1',
            'descripcion' => 'nullable|string|max:255',
            'fecha' => 'nullable|date|before_or_equal:today',
        ]);

        Ledger::registrarGasto($negocio, $data['categoria'], (int) $data['monto'], $data['descripcion'] ?? null, $data['fecha'] ?? null);

        return back()->with('ok', 'Gasto registrado');
    }
}
