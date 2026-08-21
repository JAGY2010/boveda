<?php

namespace App\Http\Controllers\Admin;

use App\Models\Negocio;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UsuarioController
{
    private function guard(): void
    {
        abort_unless(auth()->user()->isAdmin(), 403);
    }

    public function index(Request $r): View
    {
        $this->guard();

        $q = trim((string) $r->query('q', ''));
        $role = $r->query('role');
        $localId = $r->query('local');

        $usuarios = User::with('negocios')
            ->when($q !== '', fn ($query) => $query->where(
                fn ($w) => $w->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%")
            ))
            ->when(in_array($role, ['owner', 'employee', 'admin'], true), fn ($query) => $query->where('role', $role))
            ->when($localId, fn ($query) => $query->whereHas('negocios', fn ($n) => $n->where('negocios.id', $localId)))
            ->orderBy('name')
            ->paginate(50)
            ->withQueryString();

        $locales = Negocio::orderBy('nombre')->get();

        return view('admin.usuarios.index', compact('usuarios', 'locales', 'q', 'role', 'localId'));
    }

    /** El admin restablece (coloca una nueva) contraseña de cualquier usuario. */
    public function resetPassword(Request $r, User $usuario): RedirectResponse
    {
        $this->guard();

        $data = $r->validate([
            'password' => 'required|string|min:6',
        ]);

        $usuario->update(['password' => $data['password']]); // el cast 'hashed' lo encripta

        return back()->with('ok', 'Contraseña de '.$usuario->name.' actualizada.');
    }

    public function create(): View
    {
        $this->guard();
        $locales = Negocio::orderBy('nombre')->get();

        return view('admin.usuarios.create', compact('locales'));
    }

    public function store(Request $r): RedirectResponse
    {
        $this->guard();

        $data = $r->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'required|in:owner,employee',
            'locales' => 'required|array|min:1',
            'locales.*' => 'integer|exists:negocios,id',
        ]);

        $home = (int) $data['locales'][0];

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'], // el cast 'hashed' lo encripta
            'role' => $data['role'],
            'negocio_id' => $home,
        ]);

        // Marcar verificado para que pueda entrar de una vez
        $user->forceFill(['email_verified_at' => now()])->save();

        $user->negocios()->sync($data['locales']);

        return redirect()->route('admin.usuarios.index')->with('ok', 'Usuario creado: '.$user->name);
    }
}
