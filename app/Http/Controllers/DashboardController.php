<?php

namespace App\Http\Controllers;

class DashboardController
{
    public function index()
    {
        $negocio = local();

        // Sin local asignado: el admin va a crear el primero; a los demás se les avisa.
        if (! $negocio) {
            return auth()->user()->isAdmin()
                ? redirect()->route('admin.locales.index')
                : abort(403, 'Aún no tienes un local asignado. Pídele al administrador que te asigne uno.');
        }

        $activos = $negocio->empenos()->with('cliente')->where('estado', 'activo')->get();

        $proximos = $activos->sortBy(fn ($e) => $e->vencimiento()->timestamp)->take(8)->values();

        $porPerder = $activos->filter(fn ($e) => $e->mesesSinPagar() >= $e->plazo)->values();

        $enMora = $activos->filter(fn ($e) => $e->estadoCalculado() === 'en mora')->count();

        $venceSemana = $activos->filter(function ($e) {
            // Hoy en hora local: con UTC, despues de las 7 p.m. la cuenta
            // regresiva de vencimientos mostraba un dia menos.
            $d = ahoraLocal()->startOfDay()->diffInDays($e->vencimiento(), false);

            return $d >= 0 && $d <= 7;
        })->count();

        return view('dashboard', compact('negocio', 'activos', 'proximos', 'porPerder', 'enMora', 'venceSemana'));
    }
}
