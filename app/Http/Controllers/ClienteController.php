<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ClienteController
{
    public function index(Request $r)
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

    public function create()
    {
        return view('clientes.create');
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'nombre' => 'required|string|max:255',
            'cedula' => 'nullable|string|max:50',
            'tel' => 'nullable|string|max:50',
            'direccion' => 'nullable|string|max:255',
        ]);

        local()->clientes()->create($data);

        return redirect()->route('clientes.index')->with('ok', 'Cliente guardado');
    }
}
