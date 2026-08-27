<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Empeno;
use App\Models\Negocio;
use App\Support\Ledger;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * El número de contrato va impreso en el papel que firma el cliente: no
 * puede repetirse. Antes solo lo cuidaba una validación de la aplicación,
 * que no ve las carreras entre dos empeños simultáneos.
 */
class NumeroContratoTest extends TestCase
{
    use RefreshDatabase;

    private Negocio $negocio;

    private Cliente $cliente;

    protected function setUp(): void
    {
        parent::setUp();

        $this->negocio = Negocio::create([
            'nombre' => 'Compraventa Prueba', 'ciudad' => 'Isnos',
            'plazo_default' => 4, 'pct_default' => 20, 'ltv_default' => 50,
            'caja' => 10000000, 'consecutivo_inicial' => 100,
        ]);

        $this->cliente = $this->negocio->clientes()->create(['nombre' => 'Ana Ruiz']);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function crear(array $extra = []): Empeno
    {
        return Ledger::crearEmpeno($this->negocio, $this->cliente->id, array_merge([
            'articulo' => 'Moto', 'principal' => 100000, 'pct' => 20, 'plazo' => 4,
        ], $extra));
    }

    #[Test]
    public function test_la_base_impide_dos_contratos_con_el_mismo_numero(): void
    {
        $this->crear();

        // Saltandose la aplicacion, insertando directo: la base tiene que negarse.
        $this->expectException(UniqueConstraintViolationException::class);

        DB::table('empenos')->insert([
            'negocio_id' => $this->negocio->id,
            'cliente_id' => $this->cliente->id,
            'numero' => 100,
            'articulo' => 'Otro', 'principal' => 1, 'saldo' => 1,
            'pct' => 20, 'plazo' => 4, 'inicio' => hoyLocal(),
            'meses_pagados' => 0, 'estado' => 'activo',
        ]);
    }

    #[Test]
    public function test_dos_locales_pueden_tener_el_mismo_numero(): void
    {
        // El consecutivo es por local: el 100 de un negocio no choca con el de otro.
        $otro = Negocio::create([
            'nombre' => 'Otro', 'ciudad' => 'Pitalito',
            'plazo_default' => 4, 'pct_default' => 20, 'ltv_default' => 50,
            'caja' => 10000000, 'consecutivo_inicial' => 100,
        ]);
        $cli = $otro->clientes()->create(['nombre' => 'Otro cliente']);

        $a = $this->crear();
        $b = Ledger::crearEmpeno($otro, $cli->id, [
            'articulo' => 'Moto', 'principal' => 100000, 'pct' => 20, 'plazo' => 4,
        ]);

        $this->assertSame(100, (int) $a->numero);
        $this->assertSame(100, (int) $b->numero);
    }

    #[Test]
    public function test_si_el_numero_ya_esta_tomado_toma_el_siguiente(): void
    {
        $this->crear();                       // 100
        $segundo = $this->crear();            // 101

        $this->assertSame(101, (int) $segundo->numero);
        $this->assertSame(2, Empeno::count());
    }

    #[Test]
    public function test_un_numero_escrito_a_mano_y_repetido_si_falla(): void
    {
        $this->crear(['numero' => 500]);

        /* Aqui no hay carrera: alguien escribio un numero que ya existe.
           Reintentar seria inventarse otro contrato distinto al del papel. */
        $this->expectException(UniqueConstraintViolationException::class);
        $this->crear(['numero' => 500]);
    }

    #[Test]
    public function test_el_numero_arranca_en_el_consecutivo_del_local(): void
    {
        $this->assertSame(100, (int) $this->crear()->numero);
    }
}
