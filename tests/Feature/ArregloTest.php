<?php

namespace Tests\Feature;

use App\Models\Arreglo;
use App\Models\InventarioItem;
use App\Models\Negocio;
use App\Models\User;
use App\Support\Ledger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * La llanta que se le compra a la moto para venderla no es un gasto del
 * negocio: es plata invertida en esa moto. Sube su costo, y la ganancia se
 * calcula al venderla.
 */
class ArregloTest extends TestCase
{
    use RefreshDatabase;

    private Negocio $negocio;

    private User $dueno;

    private User $empleado;

    private InventarioItem $moto;

    protected function setUp(): void
    {
        parent::setUp();

        $this->negocio = Negocio::create([
            'nombre' => 'Compraventa Prueba', 'ciudad' => 'Isnos',
            'plazo_default' => 4, 'pct_default' => 20, 'ltv_default' => 50,
            'caja' => 10000000, 'consecutivo_inicial' => 1,
        ]);

        $this->dueno = User::factory()->create(['role' => 'owner', 'negocio_id' => $this->negocio->id]);
        $this->dueno->negocios()->attach($this->negocio->id);
        $this->empleado = User::factory()->create(['role' => 'employee', 'negocio_id' => $this->negocio->id]);
        $this->empleado->negocios()->attach($this->negocio->id);

        Ledger::comprarDirecto($this->negocio, 'CB 125 negra modelo 2021', 3200000);
        $this->negocio->refresh();
        $this->moto = $this->negocio->inventario()->firstOrFail();
    }

    private function arreglar(int $monto = 100000, string $desc = 'Llanta'): void
    {
        $this->actingAs($this->empleado)
            ->post(route('inventario.arreglo', $this->moto), ['descripcion' => $desc, 'monto' => $monto])
            ->assertRedirect()
            ->assertSessionHasNoErrors();
    }

    #[Test]
    public function test_el_arreglo_sube_el_costo_del_articulo(): void
    {
        $this->arreglar(100000);

        $moto = $this->moto->fresh();
        $this->assertSame(3300000, (int) $moto->costo);
        $this->assertSame(3200000, $moto->costoOriginal());
        $this->assertSame(100000, $moto->totalArreglos());
    }

    #[Test]
    public function test_sale_de_caja_pero_no_va_a_gastos(): void
    {
        $antes = $this->negocio->fresh();

        $this->arreglar(100000);

        $n = $this->negocio->fresh();
        $this->assertSame((int) $antes->caja - 100000, (int) $n->caja);
        $this->assertSame((int) $antes->inventario_valor + 100000, (int) $n->inventario_valor);
        // Esto es lo que se pidio: que no aparezca como gasto.
        $this->assertSame(0, (int) $n->acum_gastos);
        $this->assertSame(0, $n->gastos()->count());
    }

    #[Test]
    public function test_el_total_invertido_no_cambia(): void
    {
        // La plata solo pasa de la caja a la moto.
        $antes = $this->negocio->fresh()->totalInvertido();

        $this->arreglar(100000);

        $this->assertSame($antes, $this->negocio->fresh()->totalInvertido());
    }

    #[Test]
    public function test_al_vender_la_ganancia_descuenta_los_arreglos(): void
    {
        $this->arreglar(100000, 'Llanta');
        $this->arreglar(50000, 'Bateria');

        $this->actingAs($this->empleado)
            ->post(route('inventario.vender', $this->moto), ['valor' => 4000000]);

        $n = $this->negocio->fresh();
        // 4.000.000 - (3.200.000 + 100.000 + 50.000)
        $this->assertSame(650000, (int) $n->acum_margen);
        $this->assertSame(0, (int) $n->inventario_valor);
    }

    #[Test]
    public function test_un_separado_entrega_con_el_costo_ya_arreglado(): void
    {
        $cliente = $this->negocio->clientes()->create(['nombre' => 'Ana Ruiz']);
        $sep = Ledger::separarArticulo($this->moto, $cliente->id, 4000000);

        // Se arregla mientras esta separada (antes de entregarla).
        $this->arreglar(100000);

        Ledger::abonarSeparado($sep->fresh(), 4000000);
        Ledger::entregarSeparado($sep->fresh());

        $n = $this->negocio->fresh();
        $this->assertSame(700000, (int) $n->acum_margen); // 4.000.000 - 3.300.000
        $this->assertSame(0, (int) $n->inventario_valor);
    }

    #[Test]
    public function test_queda_en_el_libro_de_movimientos(): void
    {
        $this->arreglar(100000, 'Llanta');

        $this->assertTrue(
            $this->negocio->movimientos()->where('monto', -100000)
                ->where('descripcion', 'like', '%Llanta%')->exists()
        );
    }

    #[Test]
    public function test_no_se_arregla_sin_plata_en_caja(): void
    {
        $this->negocio->update(['caja' => 50000]);

        $this->actingAs($this->empleado)
            ->post(route('inventario.arreglo', $this->moto), ['descripcion' => 'Motor', 'monto' => 900000])
            ->assertSessionHas('error');

        $this->assertSame(3200000, (int) $this->moto->fresh()->costo);
        $this->assertSame(0, Arreglo::count());
    }

    #[Test]
    public function test_no_se_arregla_lo_ya_vendido(): void
    {
        Ledger::vender($this->moto, 4000000);

        $this->actingAs($this->empleado)
            ->post(route('inventario.arreglo', $this->moto), ['descripcion' => 'Llanta', 'monto' => 100000])
            ->assertStatus(422);
    }

    #[Test]
    public function test_el_dueno_quita_un_arreglo_anotado_por_error(): void
    {
        $antes = $this->negocio->fresh();
        $this->arreglar(100000);
        $arreglo = Arreglo::firstOrFail();

        $this->actingAs($this->dueno)
            ->delete(route('arreglos.quitar', $arreglo))
            ->assertRedirect();

        $n = $this->negocio->fresh();
        $this->assertSame((int) $antes->caja, (int) $n->caja);
        $this->assertSame((int) $antes->inventario_valor, (int) $n->inventario_valor);
        $this->assertSame(3200000, (int) $this->moto->fresh()->costo);
        $this->assertSame(0, Arreglo::count());
    }

    #[Test]
    public function test_el_empleado_no_puede_quitar_arreglos(): void
    {
        $this->arreglar(100000);

        $this->actingAs($this->empleado)
            ->delete(route('arreglos.quitar', Arreglo::firstOrFail()))
            ->assertStatus(403);

        $this->assertSame(1, Arreglo::count());
    }

    #[Test]
    public function test_no_se_arregla_un_articulo_de_otro_local(): void
    {
        $otro = Negocio::create([
            'nombre' => 'Otro', 'ciudad' => 'Pitalito',
            'plazo_default' => 4, 'pct_default' => 20, 'ltv_default' => 50,
            'caja' => 1000000, 'consecutivo_inicial' => 1,
        ]);
        Ledger::comprarDirecto($otro, 'Ajena', 100000);

        $this->actingAs($this->empleado)
            ->post(route('inventario.arreglo', $otro->inventario()->firstOrFail()), ['descripcion' => 'x', 'monto' => 1000])
            ->assertStatus(403);
    }

    #[Test]
    public function test_la_pantalla_muestra_el_desglose(): void
    {
        $this->arreglar(100000, 'Llanta trasera');

        $this->actingAs($this->dueno)
            ->get(route('inventario.index'))
            ->assertOk()
            ->assertSee('compra '.cop(3200000).' + arreglos '.cop(100000))
            ->assertSee('Llanta trasera')
            ->assertSee('+ Arreglo');
    }
}
