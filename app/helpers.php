<?php

use App\Models\Negocio;
use Carbon\Carbon;
use Carbon\CarbonInterface;

if (! function_exists('cop')) {
    /** Formatea un entero de pesos colombianos: 1234567 -> "$1.234.567". */
    function cop($n): string
    {
        $n = (int) round((float) $n);

        return ($n < 0 ? '-$' : '$').number_format(abs($n), 0, ',', '.');
    }
}

if (! function_exists('diasDelPeriodo')) {
    /** Días fijos de cada período de cobro (mensual = 30 comercial para prorratear). */
    function diasDelPeriodo(?string $periodo): int
    {
        return match ($periodo) {
            'diario' => 1,
            'semanal' => 7,
            'quincenal' => 15,
            default => 30,
        };
    }
}

if (! function_exists('periodoLabel')) {
    /** Nombre singular del período: día | semana | quincena | mes. */
    function periodoLabel(?string $periodo): string
    {
        return match ($periodo) {
            'diario' => 'día',
            'semanal' => 'semana',
            'quincenal' => 'quincena',
            default => 'mes',
        };
    }
}

if (! function_exists('periodoLabelPlural')) {
    function periodoLabelPlural(?string $periodo): string
    {
        return match ($periodo) {
            'diario' => 'días',
            'semanal' => 'semanas',
            'quincenal' => 'quincenas',
            default => 'meses',
        };
    }
}

if (! function_exists('redondearCien')) {
    /** Redondea al cien más cercano (49 abajo, 50 arriba). Ej: 308266 -> 308300; 308245 -> 308200. */
    function redondearCien($n): int
    {
        return (int) (round(((float) $n) / 100) * 100);
    }
}

if (! function_exists('numeroALetras')) {
    /**
     * Convierte un entero a palabras en español (sin depender de la extensión intl).
     * Pensado para montos en pesos: 1200000 -> "un millón doscientos mil".
     * Soporta hasta 999.999.999 (más que suficiente para un empeño).
     */
    function numeroALetras(int $n): string
    {
        if ($n === 0) {
            return 'cero';
        }
        if ($n < 0) {
            return 'menos '.numeroALetras(-$n);
        }

        $decenas = ['', '', '', 'treinta', 'cuarenta', 'cincuenta', 'sesenta', 'setenta', 'ochenta', 'noventa'];
        $centenas = ['', 'ciento', 'doscientos', 'trescientos', 'cuatrocientos', 'quinientos', 'seiscientos', 'setecientos', 'ochocientos', 'novecientos'];
        $u = ['', 'uno', 'dos', 'tres', 'cuatro', 'cinco', 'seis', 'siete', 'ocho', 'nueve', 'diez', 'once', 'doce', 'trece', 'catorce', 'quince', 'dieciséis', 'diecisiete', 'dieciocho', 'diecinueve', 'veinte', 'veintiuno', 'veintidós', 'veintitrés', 'veinticuatro', 'veinticinco', 'veintiséis', 'veintisiete', 'veintiocho', 'veintinueve'];

        // Convierte 1..999. $apoc apocopa "uno"->"un", "veintiuno"->"veintiún" (antes de mil/millones/pesos).
        $g = function (int $num, bool $apoc) use ($decenas, $centenas, $u): string {
            if ($num === 100) {
                return 'cien';
            }
            $r = '';
            $c = intdiv($num, 100);
            $resto = $num % 100;
            if ($c > 0) {
                $r .= $centenas[$c].' ';
            }
            if ($resto > 0) {
                if ($resto < 30) {
                    $w = $u[$resto];
                    if ($apoc && $resto === 1) {
                        $w = 'un';
                    } elseif ($apoc && $resto === 21) {
                        $w = 'veintiún';
                    }
                    $r .= $w;
                } else {
                    $d = intdiv($resto, 10);
                    $un = $resto % 10;
                    $r .= $decenas[$d];
                    if ($un > 0) {
                        $w = $u[$un];
                        if ($apoc && $un === 1) {
                            $w = 'un';
                        }
                        $r .= ' y '.$w;
                    }
                }
            }

            return trim($r);
        };

        $millones = intdiv($n, 1000000);
        $miles = intdiv($n % 1000000, 1000);
        $resto = $n % 1000;

        $texto = '';
        if ($millones > 0) {
            $texto .= ($millones === 1 ? 'un millón' : $g($millones, true).' millones').' ';
        }
        if ($miles > 0) {
            $texto .= ($miles === 1 ? 'mil' : $g($miles, true).' mil').' ';
        }
        if ($resto > 0) {
            $texto .= $g($resto, true);
        }

        return trim($texto);
    }
}

if (! function_exists('local')) {
    /** El local (negocio) activo del usuario: el seleccionado o el primero al que tiene acceso. */
    function local(): ?Negocio
    {
        $u = auth()->user();
        if (! $u) {
            return null;
        }

        $ids = array_map('intval', $u->accessibleNegocioIds());
        if (empty($ids)) {
            return null;
        }

        $sel = (int) session('local_id');
        if ($sel && in_array($sel, $ids, true)) {
            return Negocio::find($sel);
        }

        // El admin no entra a ningún local por defecto: gestiona desde el panel
        // y solo "entra" a un local cuando lo selecciona explícitamente.
        if ($u->isAdmin()) {
            return null;
        }

        $default = ($u->negocio_id && in_array((int) $u->negocio_id, $ids, true)) ? (int) $u->negocio_id : $ids[0];

        return Negocio::find($default);
    }
}

/* =============================================================
   LA FECHA, EN HORA DE COLOMBIA

   Las fechas se guardan en UTC, y eso está bien: es lo correcto y lo
   que espera Laravel. El problema no es cómo se guardan sino cómo se
   COMPARAN.

   `now()` devuelve UTC. Colombia va cinco horas atrás, así que a partir
   de las 7 de la noche `now()->toDateString()` ya devuelve la fecha de
   MAÑANA. Consecuencias medidas:

     - Un abono recibido a las 8 p.m. se guarda con fecha del día
       siguiente y no sale en el reporte del día.
     - El interés corrido cuenta un día de más. Y como el cobro redondea
       HACIA ARRIBA al bloque del local, ese día de más puede saltar un
       bloque entero: un cliente que paga el día justo en que se cumple
       su mes termina pagando dos.

   Estas funciones NO cambian dónde ni cómo se guarda nada. Solo dicen
   qué día es aquí.
   ============================================================= */

if (! function_exists('zonaLocal')) {
    /** La zona donde opera el negocio. Configurable por si algún día se
     *  vende en otro país. */
    function zonaLocal(): string
    {
        return (string) config('app.zona_negocio', 'America/Bogota');
    }
}

if (! function_exists('ahoraLocal')) {
    /** El instante actual, visto desde la zona del negocio. */
    function ahoraLocal(): CarbonInterface
    {
        return now()->setTimezone(zonaLocal());
    }
}

if (! function_exists('hoyLocal')) {
    /** Hoy como 'Y-m-d', en la zona del negocio.
     *  Reemplaza a `now()->toDateString()`. */
    function hoyLocal(): string
    {
        return ahoraLocal()->toDateString();
    }
}

if (! function_exists('enLocal')) {
    /** Pasa cualquier fecha a la zona del negocio, para poder compararla
     *  con otra del mismo lado.
     *
     *  Comparar un `startOfDay()` en UTC contra otro en Colombia da
     *  resultados que parecen correctos casi siempre y fallan de noche,
     *  que es justo cuando nadie está revisando. */
    function enLocal(DateTimeInterface $d): CarbonInterface
    {
        return Carbon::instance($d)->setTimezone(zonaLocal());
    }
}
