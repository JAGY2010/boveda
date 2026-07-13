<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ConfiguracionController
{
    public function edit()
    {
        $negocio = local();

        return view('configuracion.edit', compact('negocio'));
    }

    public function update(Request $r)
    {
        $negocio = local();

        $data = $r->validate([
            'nombre' => 'required|string|max:255',
            'nit' => 'nullable|string|max:50',
            'ciudad' => 'nullable|string|max:255',
            'plazo_default' => 'required|integer|min:1|max:60',
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
            $dir = public_path('logos');
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $archivo = 'logo_'.$negocio->id.'_'.now()->timestamp.'.'.$r->file('logo')->getClientOriginalExtension();
            $r->file('logo')->move($dir, $archivo);
            $data['logo_path'] = 'logos/'.$archivo;
        }

        $negocio->update(array_merge($data, [
            'sms_activo' => $r->boolean('sms_activo'),
        ]));

        return back()->with('ok', 'Configuración guardada');
    }
}
