<?php

namespace App\Http\Controllers;

use App\Models\InventarioItem;
use App\Support\Ledger;
use Illuminate\Http\Request;

class InventarioController
{
    public function index()
    {
        $negocio = local();
        $disponibles = $negocio->inventario()->where('estado', 'disponible')->orderByDesc('id')->get();
        $vendidos = $negocio->inventario()->where('estado', 'vendido')->orderByDesc('id')->get();

        return view('inventario.index', compact('negocio', 'disponibles', 'vendidos'));
    }

    public function comprar(Request $r)
    {
        $negocio = local();
        $data = $r->validate([
            'descripcion' => 'required|string|max:255',
            'costo' => 'required|integer|min:1',
        ]);

        if ((int) $data['costo'] > $negocio->caja) {
            return back()->with('error', 'No hay suficiente en caja.');
        }

        Ledger::comprarDirecto($negocio, $data['descripcion'], (int) $data['costo']);

        return redirect()->route('inventario.index')->with('ok', 'Comprado · pasó a inventario');
    }

    public function vender(Request $r, InventarioItem $item)
    {
        abort_if(! in_array($item->negocio_id, auth()->user()->accessibleNegocioIds()), 403);
        abort_if($item->estado !== 'disponible', 422);

        $data = $r->validate(['valor' => 'required|integer|min:1']);

        Ledger::vender($item, (int) $data['valor']);

        return redirect()->route('inventario.index')
            ->with('ok', 'Vendido · ganancia '.cop((int) $data['valor'] - $item->costo));
    }
}
