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
        $locales = Negocio::withCount([
            'clientes',
            'empenos',
            'usuarios',
            'usuarios as duenos_count' => fn ($q) => $q->where('role', 'owner'),
            'usuarios as empleados_count' => fn ($q) => $q->where('role', 'employee'),
        ])->orderBy('nombre')->get();

        return view('admin.locales.index', compact('locales'));
    }

    public function show(Negocio $negocio)
    {
        $this->guard();
        $usuarios = $negocio->usuarios()
            ->orderByRaw("CASE WHEN role = 'owner' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->get();

        return view('admin.locales.show', compact('negocio', 'usuarios'));
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
            'suscripcion_hasta' => now()->addDays(30), // 30 días de prueba
            'suspendido' => false,
        ]);

        if (! empty($data['owner_id'])) {
            User::find($data['owner_id'])->negocios()->syncWithoutDetaching([$negocio->id]);
        }

        return redirect()->route('admin.locales.index')->with('ok', 'Local creado: '.$negocio->nombre);
    }

    /** Fija la fecha de vencimiento (el admin la elige en el calendario) y reactiva el acceso. */
    public function renovar(Request $r, Negocio $negocio)
    {
        $this->guard();

        $data = $r->validate(
            ['fecha' => 'required|date|after_or_equal:today'],
            [
                'fecha.required' => 'Elige una fecha de vencimiento.',
                'fecha.after_or_equal' => 'La fecha de vencimiento no puede ser en el pasado.',
            ]
        );

        $negocio->update([
            'suscripcion_hasta' => $data['fecha'],
            'suspendido' => false,
        ]);

        return back()->with('ok', 'Suscripción de '.$negocio->nombre.' al día · vence '.$negocio->suscripcion_hasta->format('d/m/Y'));
    }

    public function suspender(Negocio $negocio)
    {
        $this->guard();
        $negocio->update(['suspendido' => true]);

        return back()->with('ok', $negocio->nombre.' quedó suspendido.');
    }

    public function reactivar(Negocio $negocio)
    {
        $this->guard();
        $negocio->update(['suspendido' => false]);

        return back()->with('ok', $negocio->nombre.' fue reactivado.');
    }
}
