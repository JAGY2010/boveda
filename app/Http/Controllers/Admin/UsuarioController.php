<?php

namespace App\Http\Controllers\Admin;

use App\Models\Negocio;
use App\Models\User;
use Illuminate\Http\Request;

class UsuarioController
{
    private function guard(): void
    {
        abort_unless(auth()->user()->isAdmin(), 403);
    }

    public function index()
    {
        $this->guard();
        $usuarios = User::with('negocios')->orderBy('name')->get();

        return view('admin.usuarios.index', compact('usuarios'));
    }

    public function create()
    {
        $this->guard();
        $locales = Negocio::orderBy('nombre')->get();

        return view('admin.usuarios.create', compact('locales'));
    }

    public function store(Request $r)
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
