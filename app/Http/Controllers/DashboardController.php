<?php

namespace App\Http\Controllers;

class DashboardController
{
    public function index()
    {
        $negocio = local();

        $activos = $negocio->empenos()->with('cliente')->where('estado', 'activo')->get();

        $proximos = $activos->sortBy(fn ($e) => $e->vencimiento()->timestamp)->take(8)->values();

        $porPerder = $activos->filter(fn ($e) => $e->mesesSinPagar() >= $e->plazo)->values();

        $venceSemana = $activos->filter(function ($e) {
            $d = now()->startOfDay()->diffInDays($e->vencimiento(), false);

            return $d >= 0 && $d <= 7;
        })->count();

        return view('dashboard', compact('negocio', 'activos', 'proximos', 'porPerder', 'venceSemana'));
    }
}
