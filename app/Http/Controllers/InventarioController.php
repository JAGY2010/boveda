<?php

namespace App\Http\Controllers;

use App\Models\Arreglo;
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
        $disponibles = $negocio->inventario()->with('arreglos')->where('estado', 'disponible')->orderByDesc('id')->get();
        $vendidos = $negocio->inventario()->with('arreglos')->where('estado', 'vendido')->orderByDesc('id')->get();
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

    /**
     * Anotar un arreglo (repuesto, mano de obra) a un artículo que está para
     * vender o separado. Suma a su costo en vez de ir a gastos.
     */
    public function arreglo(Request $r, InventarioItem $item): RedirectResponse
    {
        abort_if(! in_array($item->negocio_id, auth()->user()->accessibleNegocioIds()), 403);
        // Ya vendido no tiene sentido: la ganancia de esa venta ya se contó.
        abort_if(! in_array($item->estado, ['disponible', 'separado'], true), 422);

        $data = $r->validate([
            'descripcion' => 'required|string|max:255',
            'monto' => 'required|integer|min:1',
            'fecha' => 'nullable|date|before_or_equal:'.hoyLocal(),
        ]);

        $negocio = $item->negocio;
        if ((int) $data['monto'] > $negocio->caja) {
            return back()->with('error', 'No hay suficiente en caja.');
        }

        Ledger::agregarArreglo($item, $data['descripcion'], (int) $data['monto'], $data['fecha'] ?? null);

        return back()->with('ok', 'Arreglo anotado · '.$item->descripcion.' ahora cuesta '.cop($item->fresh()->costo));
    }

    /** Quitar un arreglo anotado por error. Solo dueño/admin. */
    public function quitarArreglo(Arreglo $arreglo): RedirectResponse
    {
        $item = $arreglo->item;
        abort_if(! in_array($item->negocio_id, auth()->user()->accessibleNegocioIds()), 403);
        abort_if(! auth()->user()->puedeEditar(), 403);
        abort_if(! in_array($item->estado, ['disponible', 'separado'], true), 422);

        Ledger::quitarArreglo($arreglo);

        return back()->with('ok', 'Arreglo quitado · la plata volvió a caja');
    }
}
