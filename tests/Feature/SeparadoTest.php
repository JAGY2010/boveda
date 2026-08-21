<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\InventarioItem;
use App\Models\Negocio;
use App\Models\Separado;
use App\Models\User;
use App\Support\Ledger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SeparadoTest extends TestCase
{
    use RefreshDatabase;

    private Negocio $negocio;

    private User $dueno;

    private User $empleado;

    private InventarioItem $item;

    private Cliente $cliente;

    protected function setUp(): void
    {
        parent::setUp();

        $this->negocio = Negocio::create([
            'nombre' => 'Compraventa Prueba', 'ciudad' => 'Isnos',
            'plazo_default' => 4, 'pct_default' => 20, 'ltv_default' => 50,
            'caja' => 1000000, 'consecutivo_inicial' => 1,
        ]);

        $this->dueno = User::factory()->create(['role' => 'owner', 'negocio_id' => $this->negocio->id]);
        $this->dueno->negocios()->attach($this->negocio->id);

        $this->empleado = User::factory()->create(['role' => 'employee', 'negocio_id' => $this->negocio->id]);
        $this->empleado->negocios()->attach($this->negocio->id);

        // Un artículo comprado en 200.000 que queda en inventario.
        Ledger::comprarDirecto($this->negocio, 'Bicicleta Trek', 200000);
        $this->negocio->refresh();
        $this->item = $this->negocio->inventario()->firstOrFail();

        $this->cliente = $this->negocio->clientes()->create([
            'nombre' => 'Ana Ruiz', 'cedula' => '1.083.000.111',
        ]);
    }

    private function separar(int $precio = 300000, int $inicial = 0): Separado
    {
        $this->actingAs($this->empleado)
            ->post(route('separados.store', $this->item), [
                'cliente_id' => $this->cliente->id,
                'precio' => $precio,
                'abono_inicial' => $inicial,
            ])->assertRedirect();

        return Separado::firstOrFail();
    }

    #[Test]
    public function test_separar_saca_el_articulo_de_la_venta_pero_no_mueve_plata(): void
    {
        $cajaAntes = $this->negocio->caja;
        $inventarioAntes = $this->negocio->inventario_valor;

        $sep = $this->separar(300000);

        $this->negocio->refresh();
        // El artículo sigue siendo del negocio: nada de dinero se movió.
        $this->assertSame($cajaAntes, (int) $this->negocio->caja);
        $this->assertSame($inventarioAntes, (int) $this->negocio->inventario_valor);
        $this->assertSame(0, (int) $this->negocio->abonos_separados);

        $this->assertSame('separado', $this->item->fresh()->estado);
        $this->assertSame(300000, (int) $sep->precio);
        $this->assertSame(0, (int) $sep->abonado);
    }

    #[Test]
    public function test_el_articulo_separado_no_aparece_para_vender(): void
    {
        $this->separar();

        $this->actingAs($this->empleado)
            ->get(route('inventario.index'))
            ->assertOk()
            ->assertSee('Separados (abonando)');

        // Ya no se puede vender por el camino normal.
        $this->actingAs($this->empleado)
            ->post(route('inventario.vender', $this->item), ['valor' => 300000])
            ->assertStatus(422);
    }

    #[Test]
    public function test_abono_entra_a_caja_pero_no_es_ganancia_todavia(): void
    {
        $sep = $this->separar(300000);
        $cajaAntes = (int) $this->negocio->fresh()->caja;

        $this->actingAs($this->empleado)
            ->post(route('separados.abonar', $sep), ['monto' => 120000])
            ->assertRedirect();

        $this->negocio->refresh();
        $this->assertSame($cajaAntes + 120000, (int) $this->negocio->caja);
        // Comprometido, no ganado.
        $this->assertSame(120000, (int) $this->negocio->abonos_separados);
        $this->assertSame(0, (int) $this->negocio->acum_margen);
        $this->assertSame(120000, (int) $sep->fresh()->abonado);
    }

    #[Test]
    public function test_el_total_invertido_no_cuenta_dos_veces_el_abono(): void
    {
        $sep = $this->separar(300000);
        $invertidoAntes = $this->negocio->fresh()->totalInvertido();

        $this->actingAs($this->empleado)
            ->post(route('separados.abonar', $sep), ['monto' => 150000]);

        /* El abono sube la caja pero el artículo sigue en inventario: si no se
           restara la bolsa de abonos, el capital parecería crecer solo. */
        $this->assertSame($invertidoAntes, $this->negocio->fresh()->totalInvertido());
    }

    #[Test]
    public function test_no_se_puede_entregar_sin_terminar_de_pagar(): void
    {
        $sep = $this->separar(300000);
        $this->actingAs($this->empleado)->post(route('separados.abonar', $sep), ['monto' => 100000]);

        $this->actingAs($this->empleado)
            ->post(route('separados.entregar', $sep))
            ->assertRedirect();

        $this->assertSame('activo', $sep->fresh()->estado);
        $this->assertSame('separado', $this->item->fresh()->estado);
    }

    #[Test]
    public function test_el_abono_nunca_pasa_del_saldo(): void
    {
        $sep = $this->separar(300000);

        $this->actingAs($this->empleado)
            ->post(route('separados.abonar', $sep), ['monto' => 500000]);

        // Se recibe lo que falta, no de más.
        $this->assertSame(300000, (int) $sep->fresh()->abonado);
        $this->assertSame(300000, (int) $this->negocio->fresh()->abonos_separados);
    }

    #[Test]
    public function test_al_entregar_se_reconoce_la_ganancia_y_sale_del_inventario(): void
    {
        $sep = $this->separar(300000);
        $this->actingAs($this->empleado)->post(route('separados.abonar', $sep), ['monto' => 300000]);

        $cajaAntes = (int) $this->negocio->fresh()->caja;

        $this->actingAs($this->empleado)
            ->post(route('separados.entregar', $sep))
            ->assertRedirect(route('inventario.index'));

        $this->negocio->refresh();
        // La plata ya había entrado abono a abono: la caja no se toca.
        $this->assertSame($cajaAntes, (int) $this->negocio->caja);
        $this->assertSame(0, (int) $this->negocio->abonos_separados);
        $this->assertSame(0, (int) $this->negocio->inventario_valor);
        // Ganancia = precio - costo.
        $this->assertSame(100000, (int) $this->negocio->acum_margen);

        $item = $this->item->fresh();
        $this->assertSame('vendido', $item->estado);
        $this->assertSame(300000, (int) $item->venta);
        $this->assertSame('entregado', $sep->fresh()->estado);
    }

    #[Test]
    public function test_cancelar_devolviendo_todo_deja_las_cuentas_como_al_principio(): void
    {
        $sep = $this->separar(300000);
        $cajaInicial = (int) $this->negocio->fresh()->caja;

        $this->actingAs($this->empleado)->post(route('separados.abonar', $sep), ['monto' => 120000]);

        $this->actingAs($this->dueno)
            ->post(route('separados.cancelar', $sep), ['devuelto' => 120000])
            ->assertRedirect();

        $this->negocio->refresh();
        $this->assertSame($cajaInicial, (int) $this->negocio->caja);
        $this->assertSame(0, (int) $this->negocio->abonos_separados);
        $this->assertSame(0, (int) $this->negocio->acum_margen);
        // El artículo vuelve a estar para vender.
        $this->assertSame('disponible', $this->item->fresh()->estado);
        $this->assertSame('cancelado', $sep->fresh()->estado);
    }

    #[Test]
    public function test_lo_retenido_al_cancelar_es_ganancia(): void
    {
        $sep = $this->separar(300000);
        $this->actingAs($this->empleado)->post(route('separados.abonar', $sep), ['monto' => 120000]);
        $cajaConAbono = (int) $this->negocio->fresh()->caja;

        $this->actingAs($this->dueno)
            ->post(route('separados.cancelar', $sep), ['devuelto' => 50000]);

        $this->negocio->refresh();
        $this->assertSame($cajaConAbono - 50000, (int) $this->negocio->caja);
        $this->assertSame(0, (int) $this->negocio->abonos_separados);
        $this->assertSame(70000, (int) $this->negocio->acum_margen);
    }

    #[Test]
    public function test_el_empleado_no_puede_cancelar_un_separado(): void
    {
        $sep = $this->separar(300000);
        $this->actingAs($this->empleado)->post(route('separados.abonar', $sep), ['monto' => 50000]);

        $this->actingAs($this->empleado)
            ->post(route('separados.cancelar', $sep), ['devuelto' => 0])
            ->assertStatus(403);

        $this->assertSame('activo', $sep->fresh()->estado);
    }

    #[Test]
    public function test_no_se_puede_devolver_mas_de_lo_abonado(): void
    {
        $sep = $this->separar(300000);
        $this->actingAs($this->empleado)->post(route('separados.abonar', $sep), ['monto' => 50000]);

        $this->actingAs($this->dueno)
            ->post(route('separados.cancelar', $sep), ['devuelto' => 90000])
            ->assertSessionHasErrors('devuelto');
    }

    #[Test]
    public function test_separar_creando_un_cliente_nuevo(): void
    {
        $this->actingAs($this->empleado)
            ->post(route('separados.store', $this->item), [
                'nuevo_nombre' => 'Pedro Nuevo',
                'nuevo_cedula' => '99.888.777',
                'precio' => 250000,
                'abono_inicial' => 50000,
            ])->assertRedirect();

        $sep = Separado::firstOrFail();
        $this->assertSame('Pedro Nuevo', $sep->cliente->nombre);
        $this->assertSame(50000, (int) $sep->abonado);
        $this->assertDatabaseHas('clientes', ['nombre' => 'Pedro Nuevo', 'negocio_id' => $this->negocio->id]);
    }

    #[Test]
    public function test_separar_sin_cliente_valido_avisa(): void
    {
        $this->actingAs($this->empleado)
            ->post(route('separados.store', $this->item), ['cliente_id' => 9999, 'precio' => 250000])
            ->assertRedirect();

        $this->assertSame(0, Separado::count());
        $this->assertSame('disponible', $this->item->fresh()->estado);
    }

    #[Test]
    public function test_no_se_separa_un_articulo_de_otro_local(): void
    {
        $otro = Negocio::create([
            'nombre' => 'Otro local', 'ciudad' => 'Pitalito',
            'plazo_default' => 4, 'pct_default' => 20, 'ltv_default' => 50,
            'caja' => 500000, 'consecutivo_inicial' => 1,
        ]);
        Ledger::comprarDirecto($otro, 'Ajeno', 100000);
        $ajeno = $otro->inventario()->firstOrFail();

        $this->actingAs($this->empleado)
            ->post(route('separados.store', $ajeno), ['cliente_id' => $this->cliente->id, 'precio' => 200000])
            ->assertStatus(403);
    }

    #[Test]
    public function test_el_recibo_muestra_el_saldo_de_ese_dia_no_el_de_hoy(): void
    {
        $sep = $this->separar(300000);
        $this->actingAs($this->empleado)->post(route('separados.abonar', $sep), ['monto' => 100000]);
        $primero = $sep->fresh()->abonos()->firstOrFail();

        // Un segundo abono no debe cambiar el recibo del primero.
        $this->actingAs($this->empleado)->post(route('separados.abonar', $sep), ['monto' => 50000]);

        $this->actingAs($this->empleado)
            ->get(route('separados.recibo', $primero))
            ->assertOk()
            ->assertSee(cop(100000))   // abonado hasta ese momento
            ->assertSee(cop(200000));  // saldo de ese momento
    }

    #[Test]
    public function test_el_tablero_avisa_cuanto_de_la_caja_es_de_separados(): void
    {
        $sep = $this->separar(300000);

        // Sin abonos, la caja es toda del negocio.
        $this->actingAs($this->dueno)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('efectivo en el negocio');

        $this->actingAs($this->empleado)->post(route('separados.abonar', $sep), ['monto' => 120000]);

        // Con abonos, el duenno tiene que ver que parte de esa caja esta comprometida.
        $this->actingAs($this->dueno)->get(route('dashboard'))
            ->assertOk()
            ->assertSee(cop(120000).' es de separados');
    }

    #[Test]
    public function test_la_pantalla_del_separado_carga(): void
    {
        $sep = $this->separar(300000, 80000);

        $this->actingAs($this->empleado)
            ->get(route('separados.show', $sep))
            ->assertOk()
            ->assertSee('Ana Ruiz')
            ->assertSee('Bicicleta Trek');
    }
}
