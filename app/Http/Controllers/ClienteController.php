<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ClienteController
{
    private function guard(Cliente $cliente): void
    {
        abort_unless(in_array($cliente->negocio_id, auth()->user()->accessibleNegocioIds()), 403);
    }

    public function index(Request $r): View
    {
        $negocio = local();
        $q = trim((string) $r->query('q', ''));

        $clientes = $negocio->clientes()
            ->withCount('empenos')
            ->orderByDesc('id')
            ->when($q !== '', function ($query) use ($q) {
                $dq = preg_replace('/\D/', '', $q);
                $query->where(function ($w) use ($q, $dq) {
                    $w->where('nombre', 'like', "%{$q}%");
                    if ($dq !== '') {
                        $w->orWhereRaw("REPLACE(REPLACE(cedula,'.',''),' ','') LIKE ?", ["%{$dq}%"]);
                    }
                });
            })
            ->get();

        return view('clientes.index', compact('clientes', 'q'));
    }

    public function create(): View
    {
        return view('clientes.create');
    }

    public function store(Request $r): RedirectResponse
    {
        $data = $r->validate([
            'nombre' => 'required|string|max:255',
            'cedula' => 'nullable|string|max:50',
            'tel' => 'nullable|string|max:50',
            'direccion' => 'nullable|string|max:255',
            'contacto2' => 'nullable|string|max:50',
        ]);

        local()->clientes()->create($data);

        return redirect()->route('clientes.index')->with('ok', 'Cliente guardado');
    }

    public function edit(Cliente $cliente): View
    {
        $this->guard($cliente);

        return view('clientes.edit', compact('cliente'));
    }

    public function update(Request $r, Cliente $cliente): RedirectResponse
    {
        $this->guard($cliente);

        $data = $r->validate([
            'nombre' => 'required|string|max:255',
            'cedula' => 'nullable|string|max:50',
            'tel' => 'nullable|string|max:50',
            'direccion' => 'nullable|string|max:255',
            'contacto2' => 'nullable|string|max:50',
        ]);

        $cliente->update($data);

        return redirect()->route('clientes.index')->with('ok', 'Cliente actualizado');
    }
}
