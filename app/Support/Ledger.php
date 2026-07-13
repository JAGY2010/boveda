<?php

namespace App\Support;

use App\Models\Empeno;
use App\Models\InventarioItem;
use App\Models\Negocio;
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
            $numero = max((int) ($n->empenos()->max('numero') ?? 0), (int) $n->consecutivo_inicial - 1) + 1;
            $principal = (int) $data['principal'];

            $empeno = $n->empenos()->create([
                'cliente_id' => $clienteId,
                'numero' => $numero,
                'categoria' => $data['categoria'] ?? null,
                'atributos' => $data['atributos'] ?? [],
                'articulo' => $data['articulo'],
                'serial' => $data['serial'] ?? null,
                'principal' => $principal,
                'saldo' => $principal,
                'pct' => $data['pct'],
                'plazo' => $data['plazo'],
                'inicio' => $data['inicio'] ?? now()->toDateString(),
                'meses_pagados' => 0,
                'estado' => 'activo',
            ]);

            $n->decrement('caja', $principal);
            $n->increment('prestado', $principal);
            self::mov($n, "Préstamo empeño #{$numero}", -$principal);

            return $empeno;
        });
    }

    /** Pagar la cuota de interés del mes: corre el vencimiento +1 mes. */
    public static function pagarInteres(Empeno $e): void
    {
        DB::transaction(function () use ($e) {
            $v = $e->interesMes();
            $e->increment('meses_pagados');
            $e->pagos()->create([
                'fecha' => now()->toDateString(),
                'tipo' => 'interés',
                'interes' => $v,
                'abono' => 0,
            ]);

            $n = $e->negocio;
            $n->increment('caja', $v);
            $n->increment('acum_interes', $v);
            self::mov($n, "Interés empeño #{$e->numero}", $v);
        });
    }

    /** Abonar a capital (siempre pagando además el interés del mes). */
    public static function abonar(Empeno $e, int $abono): void
    {
        DB::transaction(function () use ($e, $abono) {
            $abono = (int) min($abono, $e->saldo);
            $v = $e->interesMes();

            $e->increment('meses_pagados');
            $e->decrement('saldo', $abono);
            $e->pagos()->create([
                'fecha' => now()->toDateString(),
                'tipo' => 'interés + abono',
                'interes' => $v,
                'abono' => $abono,
            ]);

            $n = $e->negocio;
            $n->increment('caja', $v + $abono);
            $n->decrement('prestado', $abono);
            $n->increment('acum_interes', $v);
            self::mov($n, "Interés + abono empeño #{$e->numero}", $v + $abono);
        });
    }

    /** El cliente retira: el capital regresa a caja (más el interés corrido). */
    public static function retirar(Empeno $e): void
    {
        DB::transaction(function () use ($e) {
            $corr = $e->interesCorrido();
            $saldo = (int) $e->saldo;

            $n = $e->negocio;
            $n->increment('caja', $saldo + $corr);
            $n->decrement('prestado', $saldo);
            $n->increment('acum_interes', $corr);
            self::mov($n, "Retiro empeño #{$e->numero} (capital+interés)", $saldo + $corr);

            $e->update(['estado' => 'retirado', 'saldo' => 0]);
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
            ]);

            $e->update(['estado' => 'perdido']);
        });
    }

    /** Compra directa (el cliente vende en vez de empeñar). */
    public static function comprarDirecto(Negocio $n, string $desc, int $costo): void
    {
        DB::transaction(function () use ($n, $desc, $costo) {
            $n->decrement('caja', $costo);
            $n->increment('inventario_valor', $costo);
            $n->inventario()->create([
                'descripcion' => $desc,
                'costo' => $costo,
                'origen' => 'compra',
                'estado' => 'disponible',
            ]);
            self::mov($n, "Compra directa: {$desc}", -$costo);
        });
    }

    /** Vender un artículo: el costo vuelve a caja, el excedente a ganancias. */
    public static function vender(InventarioItem $it, int $valor): void
    {
        DB::transaction(function () use ($it, $valor) {
            $n = $it->negocio;
            $n->increment('caja', $valor);
            $n->decrement('inventario_valor', $it->costo);
            $n->increment('acum_margen', $valor - $it->costo);
            self::mov($n, "Venta: {$it->descripcion}", $valor);

            $it->update(['estado' => 'vendido', 'venta' => $valor]);
        });
    }

    public static function registrarGasto(Negocio $n, string $cat, int $monto, ?string $desc): void
    {
        DB::transaction(function () use ($n, $cat, $monto, $desc) {
            $n->decrement('caja', $monto);
            $n->increment('acum_gastos', $monto);
            $n->gastos()->create([
                'categoria' => $cat,
                'monto' => $monto,
                'descripcion' => $desc,
                'fecha' => now()->toDateString(),
            ]);
            self::mov($n, "Gasto ({$cat})".($desc ? ": {$desc}" : ''), -$monto);
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

    protected static function mov(Negocio $n, string $desc, int $monto): void
    {
        $n->movimientos()->create([
            'descripcion' => $desc,
            'monto' => $monto,
            'fecha' => now()->toDateString(),
        ]);
    }
}
