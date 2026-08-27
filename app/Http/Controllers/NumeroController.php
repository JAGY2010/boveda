<?php

namespace App\Http\Controllers;

use App\Support\Ledger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Reparte bloques de números de contrato a los dispositivos.
 *
 * Se piden con internet, por adelantado, para poder gastarlos sin él.
 */
class NumeroController
{
    public function reservar(Request $r): JsonResponse
    {
        $negocio = local();

        $data = $r->validate([
            'dispositivo' => 'required|string|max:100',
            'cuantos' => 'nullable|integer|min:1|max:50',
        ]);

        $rango = Ledger::reservarNumeros(
            $negocio,
            $data['dispositivo'],
            (int) ($data['cuantos'] ?? 20)
        );

        return response()->json([
            'desde' => (int) $rango->desde,
            'hasta' => (int) $rango->hasta,
        ]);
    }
}
