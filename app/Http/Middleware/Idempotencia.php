<?php

namespace App\Http\Middleware;

use App\Models\Operacion;
use Closure;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Impide que una misma operación se ejecute dos veces.
 *
 * Cada formulario lleva una clave única (la pone js/idempotencia.js, o la
 * cola sin conexión cuando reenvía lo que tenía guardado). La clave se
 * reserva ANTES de ejecutar: si dos peticiones iguales llegan a la vez, la
 * segunda choca contra el índice único y no cobra de nuevo.
 *
 * Si la operación no sale bien —validación fallida, saldo insuficiente— la
 * reserva se borra, porque el empleado tiene que poder corregir y reenviar.
 */
class Idempotencia
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $clave = trim((string) $request->input('_clave', ''));

        // Sin clave se pasa de largo: los formularios viejos siguen funcionando.
        if ($clave === '' || ! $request->isMethod('post')) {
            return $next($request);
        }

        try {
            $operacion = Operacion::create([
                'clave' => $clave,
                'user_id' => auth()->id(),
                'ruta' => $request->path(),
            ]);
        } catch (UniqueConstraintViolationException) {
            // Ya estaba: se responde con el resultado de la primera vez.
            return $this->yaRegistrada($clave, $request);
        }

        $respuesta = $next($request);

        if ($this->salioBien($request, $respuesta)) {
            $operacion->update([
                'destino' => $respuesta->headers->get('Location'),
                'completada' => true,
            ]);
        } else {
            // Que pueda corregir y volver a enviar el mismo formulario.
            $operacion->delete();
        }

        return $respuesta;
    }

    private function yaRegistrada(string $clave, Request $request): Response
    {
        $previa = Operacion::where('clave', $clave)->first();

        /* Si la primera todavía va en camino no hay destino aún; se devuelve
           a donde estaba, sin repetir el cobro. */
        $destino = $previa?->destino ?: url()->previous();

        return redirect($destino)->with('ok', 'Esa operación ya estaba registrada.');
    }

    private function salioBien(Request $request, Response $respuesta): bool
    {
        if (! $respuesta->isRedirect()) {
            return false;
        }

        // Un "volver atrás" con error o con validación fallida no cuenta.
        $sesion = $request->session();

        return ! $sesion->has('errors') && ! $sesion->has('error');
    }
}
