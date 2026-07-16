<?php

namespace App\Http\Controllers;

use App\Models\Empeno;
use App\Support\Ledger;
use Illuminate\Http\Request;

class EmpenoController
{
    public function index(Request $r)
    {
        $negocio = local();
        $q = trim((string) $r->query('q', ''));

        $empenos = $negocio->empenos()
            ->with('cliente')
            ->orderByDesc('id')
            ->when($q !== '', function ($query) use ($q) {
                $dq = preg_replace('/\D/', '', $q);
                $query->where(function ($w) use ($q, $dq) {
                    $w->whereHas('cliente', function ($c) use ($q, $dq) {
                        $c->where('nombre', 'like', "%{$q}%");
                        if ($dq !== '') {
                            $c->orWhereRaw("REPLACE(REPLACE(cedula,'.',''),' ','') LIKE ?", ["%{$dq}%"]);
                        }
                    })->orWhere('articulo', 'like', "%{$q}%");
                });
            })
            ->get();

        return view('empenos.index', compact('empenos', 'q'));
    }

    public function create()
    {
        $negocio = local();
        $clientes = $negocio->clientes()->orderBy('nombre')->get();

        return view('empenos.create', compact('negocio', 'clientes'));
    }

    public function store(Request $r)
    {
        $negocio = local();

        $data = $r->validate([
            'cliente_id' => 'nullable|integer',
            'nuevo_nombre' => 'nullable|string|max:255',
            'nuevo_cedula' => 'nullable|string|max:50',
            'nuevo_tel' => 'nullable|string|max:50',
            'nuevo_direccion' => 'nullable|string|max:255',
            'categoria' => 'required|string|max:50',
            'articulo' => 'nullable|string|max:255',
            'serial' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:100',
            'observaciones' => 'nullable|string|max:255',
            'nuevo_contacto2' => 'nullable|string|max:50',
            'principal' => 'required|integer|min:1',
            'pct' => 'required|numeric|min:0',
            'plazo' => 'required|integer|min:1',
            'inicio' => 'nullable|date|before_or_equal:today',
            'atributos' => 'nullable|array',
        ]);

        if ($r->filled('nuevo_nombre')) {
            $cliente = $negocio->clientes()->create([
                'nombre' => $r->nuevo_nombre,
                'cedula' => $r->nuevo_cedula,
                'tel' => $r->nuevo_tel,
                'direccion' => $r->nuevo_direccion,
                'contacto2' => $r->nuevo_contacto2,
            ]);
            $clienteId = $cliente->id;
        } else {
            $clienteId = (int) $r->cliente_id;
            if (! $negocio->clientes()->whereKey($clienteId)->exists()) {
                return back()->withInput()->with('error', 'Elige un cliente o crea uno nuevo.');
            }
        }

        if ((int) $data['principal'] > $negocio->caja) {
            return back()->withInput()->with('error', 'No hay suficiente en caja ('.cop($negocio->caja).').');
        }

        $atributos = array_filter($data['atributos'] ?? [], fn ($v) => $v !== null && $v !== '');
        // El nombre del artículo no repite año/placa/imei (esos tienen su propio campo).
        $nombreAttrs = collect($atributos)->except(['anio', 'placa', 'imei'])->all();
        $articulo = ($data['articulo'] ?? null) ?: trim($data['categoria'].' '.implode(' ', $nombreAttrs));

        $empeno = Ledger::crearEmpeno($negocio, $clienteId, [
            'articulo' => $articulo,
            'categoria' => $data['categoria'],
            'atributos' => $atributos,
            'serial' => $data['serial'] ?? null,
            'color' => $data['color'] ?? null,
            'observaciones' => $data['observaciones'] ?? null,
            'principal' => (int) $data['principal'],
            'pct' => $data['pct'],
            'plazo' => (int) $data['plazo'],
            'inicio' => $data['inicio'] ?? null,
        ]);

        return redirect()->route('empenos.show', $empeno)->with('ok', 'Empeño registrado');
    }

    public function show(Empeno $empeno)
    {
        $this->guard($empeno);
        $empeno->load('cliente', 'pagos');

        return view('empenos.show', compact('empeno'));
    }

    public function pago(Request $r, Empeno $empeno)
    {
        $this->guard($empeno);
        abort_if($empeno->estado !== 'activo', 422);

        $abono = (int) $r->input('abono', 0);
        $interesRecibido = $r->filled('interes_recibido') ? (int) $r->input('interes_recibido') : null;

        if ($abono > 0) {
            Ledger::abonar($empeno, $abono, $interesRecibido);
        } else {
            Ledger::pagarInteres($empeno, $interesRecibido);
        }

        $empeno->refresh();

        return redirect()->route('empenos.show', $empeno)
            ->with('ok', 'Pago registrado · nuevo vencimiento '.$empeno->vencimiento()->format('d/m/Y'));
    }

    public function retirar(Request $r, Empeno $empeno)
    {
        $this->guard($empeno);
        abort_if($empeno->estado !== 'activo', 422);

        $valorRecibido = $r->filled('valor_recibido') ? (int) $r->input('valor_recibido') : null;
        Ledger::retirar($empeno, $valorRecibido);

        return redirect()->route('empenos.index')->with('ok', 'Artículo entregado · el capital volvió a caja');
    }

    public function perder(Empeno $empeno)
    {
        $this->guard($empeno);
        abort_if($empeno->estado !== 'activo', 422);
        Ledger::pasarAInventario($empeno);

        return redirect()->route('inventario.index')->with('ok', 'El artículo pasó a inventario para vender');
    }

    /** Eliminar un empeño creado por error: solo el dueño; queda en el historial. */
    public function destroy(Request $r, Empeno $empeno)
    {
        $this->guard($empeno);
        abort_unless(auth()->user()->puedeEditar(), 403);

        if ($empeno->estado !== 'activo' || $empeno->meses_pagados > 0 || $empeno->pagos()->exists()) {
            return back()->with('error', 'Solo se pueden eliminar empeños activos y sin pagos registrados.');
        }

        $empeno->negocio->eliminaciones()->create([
            'user_id' => auth()->id(),
            'user_name' => auth()->user()->name,
            'numero' => $empeno->numero,
            'cliente_nombre' => optional($empeno->cliente)->nombre,
            'articulo' => $empeno->articulo,
            'principal' => (int) $empeno->principal,
            'inicio' => $empeno->inicio,
            'motivo' => $r->input('motivo'),
        ]);

        Ledger::anularEmpeno($empeno);

        return redirect()->route('empenos.index')->with('ok', 'Empeño eliminado · el capital volvió a caja.');
    }

    public function contrato(Empeno $empeno)
    {
        $this->guard($empeno);
        $empeno->load('cliente', 'negocio', 'pagos');

        return view('empenos.contrato', compact('empeno'));
    }

    private function guard(Empeno $empeno): void
    {
        abort_if(! in_array($empeno->negocio_id, auth()->user()->accessibleNegocioIds()), 403);
    }
}
