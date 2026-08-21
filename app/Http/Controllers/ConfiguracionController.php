<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ConfiguracionController
{
    /** La configuración del negocio es solo para el dueño (y el admin). */
    private function guard(): void
    {
        abort_unless(auth()->user()->puedeEditar(), 403);
    }

    public function edit(): View
    {
        $this->guard();
        $negocio = local();

        return view('configuracion.edit', compact('negocio'));
    }

    public function update(Request $r): RedirectResponse
    {
        $this->guard();
        $negocio = local();

        $data = $r->validate([
            'nombre' => 'required|string|max:255',
            'nit' => 'nullable|string|max:50',
            'ciudad' => 'nullable|string|max:255',
            'plazo_default' => 'required|integer|min:1|max:60',
            'periodo' => 'required|in:diario,semanal,quincenal,mensual',
            'pct_default' => 'required|numeric|min:0',
            'ltv_default' => 'required|integer|min:1|max:100',
            'representante' => 'nullable|string|max:255',
            'direccion' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:50',
            'consecutivo_inicial' => 'required|integer|min:1',
            'logo' => 'nullable|image|max:2048',
        ]);

        unset($data['logo']);

        if ($r->hasFile('logo')) {
            $file = $r->file('logo');
            // Guardar el logo dentro de la BD (base64) para que NO se pierda en cada despliegue.
            // getContent() lanza si no puede leer; file_get_contents devolvia
            // false y base64_encode(false) es un TypeError en PHP 8.
            $data['logo_data'] = 'data:'.$file->getMimeType().';base64,'.base64_encode($file->getContent());
        }

        $negocio->update(array_merge($data, [
            'sms_activo' => $r->boolean('sms_activo'),
        ]));

        return back()->with('ok', 'Configuración guardada');
    }
}
