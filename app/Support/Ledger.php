<?php

namespace App\Support;

use App\Models\Empeno;
use App\Models\InventarioItem;
use App\Models\Negocio;
use App\Models\Pago;
use Illuminate\Support\Facades\DB;

/**
 * Ledger: toda la lógica de dinero del negocio en un solo lugar.
 * Mueve las 4 bolsas (caja, prestado, inventario, ganancias) y deja
 * registro de cada movimiento. Todo dentro de transacciones.
 */
class Ledger
{
    /** Nuevo empeño: sale de caja hacia "prestado". */
    public static function crearEmpeno(Negocio $n, int $clienteId, array $data): Empeno
    {
        return DB::transaction(function () use ($n, $clienteId, $data) {
            // Número dado a mano (migración de empeños viejos) o el siguiente automático.
            $numero = ! empty($data['numero'])
                ? (int) $data['numero']
                : max((int) ($n->empenos()->max('numero') ?? 0), (int) $n->consecutivo_inicial - 1) + 1;
            $principal = (int) $data['principal'];

            $empeno = $n->empenos()->create([
                'cliente_id' => $clienteId,
                'numero' => $numero,
                'categoria' => $data['categoria'] ?? null,
                'atributos' => $data['atributos'] ?? [],
                'articulo' => $data['articulo'],
                'serial' => $data['serial'] ?? null,
                'color' => $data['color'] ?? null,
                'observaciones' => $data['observaciones'] ?? null,
                'principal' => $principal,
                'saldo' => $principal,
                'pct' => $data['pct'],
                'plazo' => $data['plazo'],
                'periodo' => $n->periodo ?: 'mensual',
                'inicio' => $data['inicio'] ?? hoyLocal(),
                'meses_pagados' => 0,
                'estado' => 'activo',
            ]);

            $n->decrement('caja', $principal);
            $n->increment('prestado', $principal);
            self::mov($n, "Préstamo empeño #{$numero}", -$principal);

            return $empeno;
        });
    }

    /** Deshacer un pago registrado por error: revierte el dinero, el saldo y el mes. */
    public static function deshacerPago(Pago $pago): void
    {
        DB::transaction(function () use ($pago) {
            $e = $pago->empeno;
            $n = $e->negocio;
            $interes = (int) $pago->interes;
            $abono = (int) $pago->abono;

            $n->decrement('caja', $interes + $abono);
            if ($interes !== 0) {
                $n->decrement('acum_interes', $interes);
            }
            if ($abono > 0) {
                $n->increment('prestado', $abono);
                $e->increment('saldo', $abono);
            }
            if ($e->meses_pagados > 0) {
                $e->decrement('meses_pagados');
            }
            self::mov($n, "Reversa pago empeño #{$e->numero}", -($interes + $abono));

            $pago->delete();
        });
    }

    /** Anular un empeño creado por error (activo y sin pagos): el capital vuelve a caja. */
    public static function anularEmpeno(Empeno $e): void
    {
        DB::transaction(function () use ($e) {
            $n = $e->negocio;
            $principal = (int) $e->principal;

            $n->increment('caja', $principal);
            $n->decrement('prestado', (int) $e->saldo);
            self::mov($n, "Anulación empeño #{$e->numero}", $principal);

            $e->pagos()->delete();
            $e->delete();
        });
    }

    /** Pagar la cuota de interés del mes: corre el vencimiento +1 mes. */
    public static function pagarInteres(Empeno $e, ?int $interesRecibido = null, ?string $fecha = null): void
    {
        DB::transaction(function () use ($e, $interesRecibido, $fecha) {
            $fecha = $fecha ?: hoyLocal();
            $v = $interesRecibido ?? $e->interesMes();
            $e->increment('meses_pagados');
            $e->pagos()->create([
                'fecha' => $fecha,
                'tipo' => 'interés',
                'interes' => $v,
                'abono' => 0,
            ]);

            $n = $e->negocio;
            $n->increment('caja', $v);
            $n->increment('acum_interes', $v);
            self::mov($n, "Interés empeño #{$e->numero}", $v, $fecha);
        });
    }

    /** Abonar a capital (siempre pagando además el interés del mes). */
    public static function abonar(Empeno $e, int $abono, ?int $interesRecibido = null, ?string $fecha = null): void
    {
        DB::transaction(function () use ($e, $abono, $interesRecibido, $fecha) {
            $fecha = $fecha ?: hoyLocal();
            $abono = (int) min($abono, $e->saldo);
            $v = $interesRecibido ?? $e->interesMes();

            $e->increment('meses_pagados');
            $e->decrement('saldo', $abono);
            $e->pagos()->create([
                'fecha' => $fecha,
                'tipo' => 'interés + abono',
                'interes' => $v,
                'abono' => $abono,
            ]);

            $n = $e->negocio;
            $n->increment('caja', $v + $abono);
            $n->decrement('prestado', $abono);
            $n->increment('acum_interes', $v);
            self::mov($n, "Interés + abono empeño #{$e->numero}", $v + $abono, $fecha);
        });
    }

    /** El cliente retira: el capital regresa a caja (más el interés corrido). */
    public static function retirar(Empeno $e, ?int $valorRecibido = null, ?string $fecha = null): void
    {
        DB::transaction(function () use ($e, $valorRecibido, $fecha) {
            $fecha = $fecha ?: hoyLocal();
            $saldo = (int) $e->saldo;
            $recibido = $valorRecibido ?? $e->deudaHoy();
            $interes = $recibido - $saldo; // ganancia real (puede diferir de lo esperado)

            $n = $e->negocio;
            $n->increment('caja', $recibido);
            $n->decrement('prestado', $saldo);
            $n->increment('acum_interes', $interes);
            self::mov($n, "Retiro empeño #{$e->numero}", $recibido, $fecha);

            // Se guardan fecha y valor para poder reconstruir la historia del artículo.
            $e->update([
                'estado' => 'retirado',
                'saldo' => 0,
                'fecha_retiro' => $fecha,
                'valor_retiro' => $recibido,
            ]);
        });
    }

    /** El artículo no se retiró: pasa de "prestado" a inventario (al costo). */
    public static function pasarAInventario(Empeno $e): void
    {
        DB::transaction(function () use ($e) {
            $saldo = (int) $e->saldo;
            $n = $e->negocio;

            $n->decrement('prestado', $saldo);
            $n->increment('inventario_valor', $saldo);
            $n->inventario()->create([
                'empeno_id' => $e->id,
                'descripcion' => $e->articulo,
                'costo' => $saldo,
                'origen' => 'perdido',
                'estado' => 'disponible',
                'fecha_compra' => hoyLocal(),
            ]);

            $e->update(['estado' => 'perdido']);
        });
    }

    /** Compra directa (el cliente vende en vez de empeñar). */
    public static function comprarDirecto(Negocio $n, string $desc, int $costo, ?string $fecha = null): void
    {
        DB::transaction(function () use ($n, $desc, $costo, $fecha) {
            $fecha = $fecha ?: hoyLocal();
            $n->decrement('caja', $costo);
            $n->increment('inventario_valor', $costo);
            $n->inventario()->create([
                'descripcion' => $desc,
                'costo' => $costo,
                'origen' => 'compra',
                'estado' => 'disponible',
                'fecha_compra' => $fecha,
            ]);
            self::mov($n, "Compra directa: {$desc}", -$costo, $fecha);
        });
    }

    /** Vender un artículo: el costo vuelve a caja, el excedente a ganancias. */
    public static function vender(InventarioItem $it, int $valor, ?string $fecha = null): void
    {
        DB::transaction(function () use ($it, $valor, $fecha) {
            $fecha = $fecha ?: hoyLocal();
            $n = $it->negocio;
            $n->increment('caja', $valor);
            $n->decrement('inventario_valor', $it->costo);
            $n->increment('acum_margen', $valor - $it->costo);
            self::mov($n, "Venta: {$it->descripcion}", $valor, $fecha);

            $it->update(['estado' => 'vendido', 'venta' => $valor, 'fecha_venta' => $fecha]);
        });
    }

    public static function registrarGasto(Negocio $n, string $cat, int $monto, ?string $desc, ?string $fecha = null): void
    {
        DB::transaction(function () use ($n, $cat, $monto, $desc, $fecha) {
            $fecha = $fecha ?: hoyLocal();
            $n->decrement('caja', $monto);
            $n->increment('acum_gastos', $monto);
            $n->gastos()->create([
                'categoria' => $cat,
                'monto' => $monto,
                'descripcion' => $desc,
                'fecha' => $fecha,
            ]);
            self::mov($n, "Gasto ({$cat})".($desc ? ": {$desc}" : ''), -$monto, $fecha);
        });
    }

    public static function moverCapital(Negocio $n, string $tipo, int $monto): void
    {
        DB::transaction(function () use ($n, $tipo, $monto) {
            if ($tipo === 'agregar') {
                $n->increment('caja', $monto);
                self::mov($n, 'Ingreso de capital', $monto);
            } else {
                $n->decrement('caja', $monto);
                self::mov($n, 'Retiro de capital', -$monto);
            }
        });
    }

    protected static function mov(Negocio $n, string $desc, int $monto, ?string $fecha = null): void
    {
        $n->movimientos()->create([
            'descripcion' => $desc,
            'monto' => $monto,
            'fecha' => $fecha ?: hoyLocal(),
        ]);
    }
}
