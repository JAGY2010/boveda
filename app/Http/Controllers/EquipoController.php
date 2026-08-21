<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EquipoController
{
    private function guard(): void
    {
        // Dueños (y admin dentro de un local) gestionan empleados; requiere un local activo.
        abort_unless(auth()->user()->puedeEditar() && local(), 403);
    }

    public function index(): View
    {
        $this->guard();
        $local = local();

        $empleados = User::where('role', 'employee')
            ->whereHas('negocios', fn ($q) => $q->where('negocios.id', $local->id))
            ->orderBy('name')
            ->get();

        return view('equipo.index', compact('empleados', 'local'));
    }

    public function create(): View
    {
        $this->guard();

        return view('equipo.create', ['local' => local()]);
    }

    public function store(Request $r): RedirectResponse
    {
        $this->guard();
        $local = local();

        $data = $r->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:6',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'], // el cast 'hashed' lo encripta
            'role' => 'employee',
            'negocio_id' => $local->id,
        ]);

        $user->forceFill(['email_verified_at' => now()])->save();
        $user->negocios()->sync([$local->id]);

        return redirect()->route('equipo.index')->with('ok', 'Empleado creado: '.$user->name);
    }
}
