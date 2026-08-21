<?php

namespace App\Http\Controllers;

use App\Models\InventarioItem;
use App\Support\Ledger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InventarioController
{
    public function index(): View
    {
        $negocio = local();
        $disponibles = $negocio->inventario()->where('estado', 'disponible')->orderByDesc('id')->get();
        $vendidos = $negocio->inventario()->where('estado', 'vendido')->orderByDesc('id')->get();
        $separados = $negocio->separados()->with('cliente', 'item')
            ->where('estado', 'activo')->orderByDesc('id')->get();
        // Para el selector de cliente al separar (mismo patron que el empeno).
        $clientes = $negocio->clientes()->orderBy('nombre')->get();

        return view('inventario.index', compact('negocio', 'disponibles', 'vendidos', 'separados', 'clientes'));
    }

    public function comprar(Request $r): RedirectResponse
    {
        $negocio = local();
        $data = $r->validate([
            'descripcion' => 'required|string|max:255',
            'costo' => 'required|integer|min:1',
            'fecha_compra' => 'nullable|date|before_or_equal:'.hoyLocal(),
        ]);

        if ((int) $data['costo'] > $negocio->caja) {
            return back()->with('error', 'No hay suficiente en caja.');
        }

        Ledger::comprarDirecto($negocio, $data['descripcion'], (int) $data['costo'], $data['fecha_compra'] ?? null);

        return redirect()->route('inventario.index')->with('ok', 'Comprado · pasó a inventario');
    }

    public function vender(Request $r, InventarioItem $item): RedirectResponse
    {
        abort_if(! in_array($item->negocio_id, auth()->user()->accessibleNegocioIds()), 403);
        abort_if($item->estado !== 'disponible', 422);

        $data = $r->validate([
            'valor' => 'required|integer|min:1',
            'fecha_venta' => 'nullable|date|before_or_equal:'.hoyLocal(),
        ]);

        Ledger::vender($item, (int) $data['valor'], $data['fecha_venta'] ?? null);

        return redirect()->route('inventario.index')
            ->with('ok', 'Vendido · ganancia '.cop((int) $data['valor'] - $item->costo));
    }
}
