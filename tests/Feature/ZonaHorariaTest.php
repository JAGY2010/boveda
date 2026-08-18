<?php

namespace Tests\Feature;

use App\Models\Empeno;
use App\Models\Gasto;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El negocio opera en Colombia; las fechas se guardan en UTC.
 *
 * Esa combinacion tenia un fallo que solo aparecia DE NOCHE: a partir de
 * las 7 p.m. hora local, UTC ya esta en el dia siguiente, asi que la
 * aplicacion contaba un dia de mas. Y como el interes redondea HACIA
 * ARRIBA al bloque de cobro, ese dia de mas podia saltar un bloque
 * entero: el cliente que llegaba el dia justo en que se cumplia su mes
 * terminaba pagando dos.
 *
 * Estas pruebas viajan a las 8 de la noche a proposito. Sin fijar la
 * hora, pasarian de dia y fallarian de noche — que es exactamente como
 * el fallo llego a produccion sin que nadie lo viera.
 */
class ZonaHorariaTest extends TestCase
{
    use RefreshDatabase;

    /** Las 8 p.m. en Colombia: en UTC ya es el dia siguiente. */
    private function anochecer(): void
    {
        $this->travelTo(new \DateTime('2026-08-17 20:30:00', new \DateTimeZone('America/Bogota')));
    }

    public function test_de_noche_hoy_sigue_siendo_hoy(): void
    {
        $this->anochecer();

        $this->assertSame('2026-08-18', now()->toDateString(), 'UTC va un dia adelante');
        $this->assertSame('2026-08-17', hoyLocal(), 'hoyLocal() tiene que dar el dia de Colombia');
    }

    public function test_de_noche_el_interes_no_cobra_un_dia_de_mas(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->anochecer();

        $e = Empeno::where('estado', 'activo')->firstOrFail();
        $e->update(['saldo' => 1000000, 'pct' => 8, 'meses_pagados' => 0, 'periodo' => 'diario']);
        $e->refresh();
        $e->inicio = ahoraLocal()->subDays(5);

        // 5 dias reales: 80.000/30 x 5 = 13.333 -> 13.300
        // Con el fallo contaba 6 dias y cobraba 16.000.
        $this->assertEquals(13300, $e->interesCorrido(), 'cobro de mas por contar en UTC');
    }

    public function test_de_noche_el_bloque_mensual_no_salta_al_siguiente(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->anochecer();

        $e = Empeno::where('estado', 'activo')->firstOrFail();
        $e->update(['saldo' => 1000000, 'pct' => 8, 'meses_pagados' => 0, 'periodo' => 'mensual']);
        $e->refresh();

        // Justo el dia en que se cumple el mes: se cobra UN mes.
        $e->inicio = ahoraLocal()->subDays(30);
        $this->assertEquals(80000, $e->interesCorrido(), 'a los 30 dias exactos se cobra un mes');

        // Con el fallo contaba 31 dias y saltaba al bloque siguiente:
        // ceil(31/30) x 30 = 60 dias = DOS meses.
        $this->assertNotEquals(160000, $e->interesCorrido(), 'estaria cobrando el doble');
    }

    public function test_de_noche_un_gasto_queda_con_la_fecha_de_hoy(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->anochecer();

        $dueno = User::where('email', 'dueno@boveda.test')->firstOrFail();

        $this->actingAs($dueno)
            ->post('/contabilidad/gasto', ['categoria' => 'Prueba nocturna', 'monto' => 10000])
            ->assertRedirect();

        $g = Gasto::where('categoria', 'Prueba nocturna')->firstOrFail();

        // Con el fallo quedaba con fecha del 18 y no salia en el cierre
        // del dia: la plata entraba pero el reporte no la veia.
        $this->assertSame('2026-08-17', $g->fecha->toDateString());
    }

    public function test_de_noche_no_se_acepta_la_fecha_de_manana(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->anochecer();

        $dueno = User::where('email', 'dueno@boveda.test')->firstOrFail();

        // El 18 es MAÑANA en Colombia, aunque UTC ya diga que es hoy.
        $this->actingAs($dueno)->post('/contabilidad/gasto', [
            'categoria' => 'Futuro', 'monto' => 1000, 'fecha' => '2026-08-18',
        ])->assertSessionHasErrors('fecha');
    }
}
