<?php

namespace Tests\Feature;

use App\Database\PostgresConnector;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;

/**
 * La caída del 20/08/2026 duró tres horas porque los workers se quedaron
 * bloqueados sobre conexiones muertas. Estas pruebas fijan que los tiempos
 * de detección viajen de verdad en la cadena de conexión: si alguien los
 * quita o cambia el conector, esto falla.
 */
class PostgresTimeoutTest extends TestCase
{
    /**
     * @param  array<string, mixed>  $extra
     */
    private function dsn(array $extra = []): string
    {
        $metodo = new ReflectionMethod(PostgresConnector::class, 'getDsn');

        return $metodo->invoke(new PostgresConnector, array_merge([
            'host' => 'db.interno',
            'port' => 5432,
            'database' => 'boveda',
        ], $extra));
    }

    #[Test]
    public function test_el_dsn_lleva_los_tiempos_de_deteccion(): void
    {
        $dsn = $this->dsn([
            'connect_timeout' => 5,
            'keepalives' => 1,
            'keepalives_idle' => 30,
            'keepalives_interval' => 10,
            'keepalives_count' => 3,
            'tcp_user_timeout' => 15000,
        ]);

        $this->assertStringContainsString('connect_timeout=5', $dsn);
        $this->assertStringContainsString('keepalives=1', $dsn);
        $this->assertStringContainsString('keepalives_idle=30', $dsn);
        $this->assertStringContainsString('keepalives_interval=10', $dsn);
        $this->assertStringContainsString('keepalives_count=3', $dsn);
        $this->assertStringContainsString('tcp_user_timeout=15000', $dsn);
    }

    #[Test]
    public function test_sigue_construyendo_bien_lo_de_siempre(): void
    {
        $dsn = $this->dsn(['sslmode' => 'require', 'connect_timeout' => 5]);

        $this->assertStringStartsWith('pgsql:', $dsn);
        $this->assertStringContainsString('host=db.interno', $dsn);
        $this->assertStringContainsString("dbname='boveda'", $dsn);
        $this->assertStringContainsString('port=5432', $dsn);
        $this->assertStringContainsString('sslmode=require', $dsn);
    }

    #[Test]
    public function test_sin_valores_no_ensucia_la_conexion(): void
    {
        // Si no hay ajustes, se respeta lo que traiga libpq por defecto.
        $dsn = $this->dsn();

        $this->assertStringNotContainsString('keepalives', $dsn);
        $this->assertStringNotContainsString('connect_timeout', $dsn);
        $this->assertStringNotContainsString('tcp_user_timeout', $dsn);
    }

    #[Test]
    public function test_los_valores_van_como_numeros(): void
    {
        // Un valor de texto no puede colarse tal cual en la cadena.
        $dsn = $this->dsn(['keepalives_idle' => '30; algo=raro']);

        $this->assertStringContainsString('keepalives_idle=30', $dsn);
        $this->assertStringNotContainsString('algo=raro', $dsn);
    }

    #[Test]
    public function test_la_configuracion_trae_tiempos_razonables(): void
    {
        // Sin esto, keepalives_idle usa el default de Linux: 7200 s.
        $this->assertSame(5, (int) config('database.connections.pgsql.connect_timeout'));
        $this->assertSame(1, (int) config('database.connections.pgsql.keepalives'));

        $idle = (int) config('database.connections.pgsql.keepalives_idle');
        $this->assertGreaterThan(0, $idle);
        $this->assertLessThanOrEqual(60, $idle, 'Detectar una base caida no puede tardar minutos.');

        $this->assertGreaterThan(0, (int) config('database.connections.pgsql.tcp_user_timeout'));
    }

    #[Test]
    public function test_el_conector_de_pgsql_es_el_nuestro(): void
    {
        $this->assertInstanceOf(PostgresConnector::class, app('db.connector.pgsql'));
    }
}
