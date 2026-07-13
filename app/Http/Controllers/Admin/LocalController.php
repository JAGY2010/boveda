<?php

namespace App\Http\Controllers\Admin;

use App\Models\Negocio;
use App\Models\User;
use Illuminate\Http\Request;

class LocalController
{
    private function guard(): void
    {
        abort_unless(auth()->user()->isAdmin(), 403);
    }

    public function index()
    {
        $this->guard();
        $locales = Negocio::withCount(['clientes', 'empenos'])->orderBy('nombre')->get();

        return view('admin.locales.index', compact('locales'));
    }

    public function create()
    {
        $this->guard();
        $duenos = User::where('role', 'owner')->orderBy('name')->get();

        return view('admin.locales.create', compact('duenos'));
    }

    public function store(Request $r)
    {
        $this->guard();

        $data = $r->validate([
            'nombre' => 'required|string|max:255',
            'ciudad' => 'nullable|string|max:255',
            'nit' => 'nullable|string|max:50',
            'plazo_default' => 'required|integer|min:1|max:60',
            'pct_default' => 'required|numeric|min:0',
            'ltv_default' => 'required|integer|min:1|max:100',
            'caja_inicial' => 'required|integer|min:0',
            'consecutivo_inicial' => 'required|integer|min:1',
            'owner_id' => 'nullable|integer|exists:users,id',
        ]);

        $negocio = Negocio::create([
            'nombre' => $data['nombre'],
            'ciudad' => $data['ciudad'] ?? null,
            'nit' => $data['nit'] ?? null,
            'plazo_default' => $data['plazo_default'],
            'pct_default' => $data['pct_default'],
            'ltv_default' => $data['ltv_default'],
            'sms_activo' => true,
            'caja' => (int) $data['caja_inicial'],
            'consecutivo_inicial' => (int) $data['consecutivo_inicial'],
        ]);

        if (! empty($data['owner_id'])) {
            User::find($data['owner_id'])->negocios()->syncWithoutDetaching([$negocio->id]);
        }

        return redirect()->route('admin.locales.index')->with('ok', 'Local creado: '.$negocio->nombre);
    }
}
