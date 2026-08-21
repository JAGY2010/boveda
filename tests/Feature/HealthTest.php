<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class HealthTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_health_responde_ok_con_la_base_arriba(): void
    {
        $this->getJson('/health')
            ->assertOk()
            ->assertExactJson(['status' => 'ok', 'database' => 'ok']);
    }

    #[Test]
    public function test_health_no_pide_autenticacion(): void
    {
        // Es la sonda del despliegue: tiene que responder sin sesion.
        $this->assertGuest();
        $this->getJson('/health')->assertOk();
    }

    #[Test]
    public function test_health_responde_503_si_la_base_no_contesta(): void
    {
        DB::shouldReceive('connection->select')
            ->once()
            ->andThrow(new RuntimeException('conexion rechazada'));

        $this->getJson('/health')
            ->assertStatus(503)
            ->assertExactJson(['status' => 'error', 'database' => 'unreachable']);
    }

    #[Test]
    public function test_health_no_filtra_el_detalle_del_error(): void
    {
        // La ruta es publica: el mensaje interno no puede salir en la respuesta.
        DB::shouldReceive('connection->select')
            ->once()
            ->andThrow(new RuntimeException('password authentication failed for user secreto'));

        $r = $this->getJson('/health')->assertStatus(503);

        $this->assertStringNotContainsString('secreto', $r->getContent());
        $this->assertStringNotContainsString('password', $r->getContent());
    }

    #[Test]
    public function test_health_no_crea_sesiones_en_la_base(): void
    {
        /* Va fuera del grupo "web" justamente para esto: con
           SESSION_DRIVER=database un monitor externo llenaria la tabla. */
        $antes = DB::table('sessions')->count();

        $this->getJson('/health')->assertOk();

        $this->assertSame($antes, DB::table('sessions')->count());
    }
}
