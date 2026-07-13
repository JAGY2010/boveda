<?php

if (! function_exists('cop')) {
    /** Formatea un entero de pesos colombianos: 1234567 -> "$1.234.567". */
    function cop($n): string
    {
        $n = (int) round((float) $n);

        return ($n < 0 ? '-$' : '$').number_format(abs($n), 0, ',', '.');
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

