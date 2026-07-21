<?php

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
    function local(): ?\App\Models\Negocio
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
            return \App\Models\Negocio::find($sel);
        }

        // El admin no entra a ningún local por defecto: gestiona desde el panel
        // y solo "entra" a un local cuando lo selecciona explícitamente.
        if ($u->isAdmin()) {
            return null;
        }

        $default = ($u->negocio_id && in_array((int) $u->negocio_id, $ids, true)) ? (int) $u->negocio_id : $ids[0];

        return \App\Models\Negocio::find($default);
    }
}

