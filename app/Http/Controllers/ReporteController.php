<?php

namespace App\Http\Controllers;

use App\Models\Pago;
use Illuminate\Http\Request;

class ReporteController
{
    /** Reporte de actividad por período (solo dueño/admin). */
    public function index(Request $r)
    {
        abort_unless(auth()->user()->puedeVerDinero(), 403);

        $negocio = local();
        $periodo = $r->query('periodo', 'hoy');

        [$desde, $hasta] = match ($periodo) {
            'mes' => [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()],
            'mespasado' => [now()->subMonthNoOverflow()->startOfMonth()->toDateString(), now()->subMonthNoOverflow()->endOfMonth()->toDateString()],
            'personalizado' => [
                $r->query('desde') ?: now()->toDateString(),
                $r->query('hasta') ?: now()->toDateString(),
            ],
            default => [now()->toDateString(), now()->toDateString()],
        };

        // Pagos (interés + abono a capital) en el rango.
        $pagos = Pago::whereBetween('fecha', [$desde, $hasta])
            ->whereHas('empeno', fn ($q) => $q->where('negocio_id', $negocio->id))
            ->get();
        $interesCobrado = (int) $pagos->sum('interes');
        $abonosCapital = (int) $pagos->sum('abono');

        // Empeños nuevos por su fecha de inicio.
        $empenosNuevos = $negocio->empenos()->whereBetween('inicio', [$desde, $hasta])->get();
        $cantEmpenos = $empenosNuevos->count();
        $prestado = (int) $empenosNuevos->sum('principal');

        // Gastos del período.
        $gastos = (int) $negocio->gastos()->whereBetween('fecha', [$desde, $hasta])->sum('monto');

        $ganancia = $interesCobrado - $gastos;

        return view('reportes.index', compact(
            'periodo', 'desde', 'hasta',
            'interesCobrado', 'abonosCapital', 'cantEmpenos', 'prestado', 'gastos', 'ganancia'
        ));
    }
}
