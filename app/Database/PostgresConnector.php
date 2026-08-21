<?php

namespace App\Database;

use Illuminate\Database\Connectors\PostgresConnector as ConectorDeLaravel;

/**
 * Conector de Postgres que no se queda colgado cuando la base desaparece.
 *
 * El 20 de agosto de 2026 Postgres se cayó a las 20:52 y volvió a las 23:49.
 * La aplicación nunca se recuperó: los workers quedaron bloqueados sobre
 * conexiones muertas y hubo que reiniciarla a mano tres horas después.
 *
 * La razón es que libpq activa los keepalives pero deja los tiempos "por
 * defecto del sistema", y en Linux eso son 7200 segundos: dos horas hasta
 * darse cuenta de que del otro lado ya no hay nadie.
 *
 * Esos parámetros solo se pueden pasar en la cadena de conexión (NO existen
 * como variables de entorno; solo connect_timeout tiene PGCONNECT_TIMEOUT),
 * y el conector de Laravel únicamente añade los de SSL. Así que aquí se
 * añaden los que faltan; PDO se los pasa tal cual a libpq.
 */
class PostgresConnector extends ConectorDeLaravel
{
    /**
     * Parámetros de libpq que dejamos configurar desde config/database.php.
     *
     * @var list<string>
     */
    private const AJUSTES = [
        'connect_timeout',
        'keepalives',
        'keepalives_idle',
        'keepalives_interval',
        'keepalives_count',
        'tcp_user_timeout',
    ];

    /**
     * @param  array<string, mixed>  $config
     */
    protected function getDsn(array $config): string
    {
        $dsn = parent::getDsn($config);

        foreach (self::AJUSTES as $ajuste) {
            $valor = $config[$ajuste] ?? null;

            // Sin valor se deja el de libpq: mejor no tocar que poner algo raro.
            if ($valor === null || $valor === '') {
                continue;
            }

            $dsn .= ";{$ajuste}=".(int) $valor;
        }

        return $dsn;
    }
}
