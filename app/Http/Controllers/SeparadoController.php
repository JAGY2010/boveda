<?php

namespace App\Http\Controllers;

use App\Models\InventarioItem;
use App\Models\Separado;
use App\Models\SeparadoAbono;
use App\Support\Ledger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Separados (apartados): el cliente aparta un artículo del inventario y lo
 * va abonando; se lo lleva cuando termina de pagarlo.
 */
class SeparadoController
{
    /** Apartar un artículo para un cliente (existente o nuevo). */
    public function store(Request $r, InventarioItem $item): RedirectResponse
    {
        $negocio = local();
        abort_if(! in_array($item->negocio_id, auth()->user()->accessibleNegocioIds()), 403);
        abort_if($item->estado !== 'disponible', 422);

        $data = $r->validate([
            'cliente_id' => 'nullable|integer',
            'nuevo_nombre' => 'nullable|string|max:255',
            'nuevo_cedula' => 'nullable|string|max:50',
            'nuevo_tel' => 'nullable|string|max:50',
            'nuevo_direccion' => 'nullable|string|max:255',
            'precio' => 'required|integer|min:1',
            'abono_inicial' => 'nullable|integer|min:0',
            'fecha' => 'nullable|date|before_or_equal:'.hoyLocal(),
        ]);

        if ($r->filled('nuevo_nombre')) {
            $cliente = $negocio->clientes()->create([
                'nombre' => $r->nuevo_nombre,
                'cedula' => $r->nuevo_cedula,
                'tel' => $r->nuevo_tel,
                'direccion' => $r->nuevo_direccion,
            ]);
            $clienteId = (int) $cliente->id;
        } else {
            $clienteId = (int) $r->cliente_id;
            if (! $negocio->clientes()->whereKey($clienteId)->exists()) {
                return back()->withInput()->with('error', 'Elige un cliente o crea uno nuevo.');
            }
        }

        $fecha = $data['fecha'] ?? null;
        $separado = Ledger::separarArticulo($item, $clienteId, (int) $data['precio'], $fecha);

        // El abono de entrada es opcional; muchos separados arrancan con algo.
        if ((int) ($data['abono_inicial'] ?? 0) > 0) {
            Ledger::abonarSeparado($separado, (int) $data['abono_inicial'], $fecha);
        }

        return redirect()->route('separados.show', $separado)
            ->with('ok', 'Artículo separado para '.$separado->cliente->nombre);
    }

    public function show(Separado $separado): View
    {
        $this->guard($separado);
        $separado->load('cliente', 'item', 'abonos', 'negocio');

        return view('separados.show', compact('separado'));
    }

    public function abonar(Request $r, Separado $separado): RedirectResponse
    {
        $this->guard($separado);
        abort_if($separado->estado !== 'activo', 422);

        $data = $r->validate([
            'monto' => 'required|integer|min:1',
            'fecha' => 'nullable|date|before_or_equal:'.hoyLocal(),
        ]);

        if ($separado->saldo() === 0) {
            return back()->with('error', 'Ya está pago. Entrega el artículo.');
        }

        Ledger::abonarSeparado($separado, (int) $data['monto'], $data['fecha'] ?? null);
        $separado->refresh();

        $abono = $separado->abonos()->latest('id')->first();
        $msg = $separado->estaPago()
            ? 'Abono registrado · quedó pago, ya puedes entregar el artículo.'
            : 'Abono registrado · falta '.cop($separado->saldo());

        return redirect()->route('separados.show', $separado)
            ->with('ok', $msg)
            ->with('recibo_id', $abono?->id);
    }

    public function entregar(Request $r, Separado $separado): RedirectResponse
    {
        $this->guard($separado);
        abort_if($separado->estado !== 'activo', 422);

        if (! $separado->estaPago()) {
            return back()->with('error', 'Todavía falta '.cop($separado->saldo()).' por abonar.');
        }

        $data = $r->validate([
            'fecha' => 'nullable|date|before_or_equal:'.hoyLocal(),
        ]);

        Ledger::entregarSeparado($separado, $data['fecha'] ?? null);

        return redirect()->route('inventario.index')
            ->with('ok', 'Artículo entregado · ganancia '.cop((int) $separado->abonado - (int) $separado->item->costo));
    }

    /** El cliente desiste: se decide cuánto se le devuelve. */
    public function cancelar(Request $r, Separado $separado): RedirectResponse
    {
        $this->guard($separado);
        abort_if($separado->estado !== 'activo', 422);
        abort_if(! auth()->user()->puedeEditar(), 403);

        $data = $r->validate([
            'devuelto' => 'required|integer|min:0|max:'.(int) $separado->abonado,
            'fecha' => 'nullable|date|before_or_equal:'.hoyLocal(),
        ]);

        Ledger::cancelarSeparado($separado, (int) $data['devuelto'], $data['fecha'] ?? null);

        $retenido = (int) $separado->abonado - (int) $data['devuelto'];

        return redirect()->route('inventario.index')
            ->with('ok', 'Separado cancelado · devuelto '.cop((int) $data['devuelto']).' · el negocio retuvo '.cop($retenido));
    }

    /** Comprobante imprimible de un abono. */
    public function recibo(SeparadoAbono $abono): View
    {
        $separado = $abono->separado;
        $this->guard($separado);
        $separado->load('cliente', 'item', 'negocio');

        /* Un recibo es del momento en que se firmo: si se reimprime despues
           de otros abonos, tiene que seguir mostrando el saldo de ESE dia,
           no el de hoy. */
        $abonadoHasta = (int) $separado->abonos()->where('id', '<=', $abono->id)->sum('monto');
        $saldoHasta = max(0, (int) $separado->precio - $abonadoHasta);

        return view('separados.recibo', compact('abono', 'separado', 'abonadoHasta', 'saldoHasta'));
    }

    private function guard(Separado $separado): void
    {
        abort_if(! in_array($separado->negocio_id, auth()->user()->accessibleNegocioIds()), 403);
    }
}
