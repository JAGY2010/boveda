<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Sonda de salud del servicio.
 *
 * Railway solo consulta esto AL DESPLEGAR, para no mandarle trafico a una
 * version rota; no lo vigila despues de que la version queda en vivo. Por
 * eso el endpoint tambien esta pensado para que lo llame un monitor externo
 * cada minuto: no abre sesion, no escribe nada y hace una sola consulta.
 */
class HealthController
{
    public function __invoke(): JsonResponse
    {
        try {
            DB::connection()->select('select 1');
        } catch (Throwable $e) {
            /* Sin base de datos la app no puede operar: se responde 503 para
               que el despliegue no reciba trafico y el monitor externo avise.
               No se filtra el detalle del error: esta ruta es publica. */
            return response()->json([
                'status' => 'error',
                'database' => 'unreachable',
            ], 503);
        }

        return response()->json([
            'status' => 'ok',
            'database' => 'ok',
        ]);
    }
}
