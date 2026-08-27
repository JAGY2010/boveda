<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\InventarioItem;
use App\Models\Negocio;
use App\Models\Operacion;
use App\Models\Separado;
use App\Models\User;
use App\Support\Ledger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Reenviar una operación no puede cobrarla dos veces. Es lo que hace segura
 * la cola sin conexión: si el internet se cae justo después de guardar, el
 * reintento tiene que ser inofensivo.
 */
class IdempotenciaTest extends TestCase
{
    use RefreshDatabase;

    private Negocio $negocio;

    private User $empleado;

    private Separado $separado;

    protected function setUp(): void
    {
        parent::setUp();

        $this->negocio = Negocio::create([
            'nombre' => 'Compraventa Prueba', 'ciudad' => 'Isnos',
            'plazo_default' => 4, 'pct_default' => 20, 'ltv_default' => 50,
            'caja' => 1000000, 'consecutivo_inicial' => 1,
        ]);

        $this->empleado = User::factory()->create(['role' => 'employee', 'negocio_id' => $this->negocio->id]);
        $this->empleado->negocios()->attach($this->negocio->id);

        Ledger::comprarDirecto($this->negocio, 'Bicicleta', 200000);
        $item = $this->negocio->inventario()->firstOrFail();
        $cliente = $this->negocio->clientes()->create(['nombre' => 'Ana Ruiz']);

        $this->separado = Ledger::separarArticulo($item, $cliente->id, 300000);
    }

    #[Test]
    public function test_el_mismo_abono_enviado_dos_veces_solo_cobra_una(): void
    {
        $clave = 'prueba-abono-1';
        $cajaAntes = (int) $this->negocio->fresh()->caja;

        $this->actingAs($this->empleado)
            ->post(route('separados.abonar', $this->separado), ['monto' => 100000, '_clave' => $clave])
            ->assertRedirect();

        // El internet se cayo y el empleado (o la cola) reenvia lo mismo.
        $this->actingAs($this->empleado)
            ->post(route('separados.abonar', $this->separado), ['monto' => 100000, '_clave' => $clave])
            ->assertRedirect();

        $this->assertSame($cajaAntes + 100000, (int) $this->negocio->fresh()->caja);
        $this->assertSame(100000, (int) $this->separado->fresh()->abonado);
        $this->assertSame(1, $this->separado->fresh()->abonos()->count());
    }

    #[Test]
    public function test_dos_abonos_distintos_si_se_cobran_los_dos(): void
    {
        $cajaAntes = (int) $this->negocio->fresh()->caja;

        $this->actingAs($this->empleado)
            ->post(route('separados.abonar', $this->separado), ['monto' => 50000, '_clave' => 'uno']);
        $this->actingAs($this->empleado)
            ->post(route('separados.abonar', $this->separado), ['monto' => 50000, '_clave' => 'dos']);

        $this->assertSame($cajaAntes + 100000, (int) $this->negocio->fresh()->caja);
        $this->assertSame(2, $this->separado->fresh()->abonos()->count());
    }

    #[Test]
    public function test_sin_clave_todo_sigue_funcionando_igual(): void
    {
        // Compatibilidad: un formulario sin clave no se bloquea.
        $this->actingAs($this->empleado)
            ->post(route('separados.abonar', $this->separado), ['monto' => 70000])
            ->assertRedirect();

        $this->assertSame(70000, (int) $this->separado->fresh()->abonado);
        $this->assertSame(0, Operacion::count());
    }

    #[Test]
    public function test_una_operacion_fallida_se_puede_corregir_y_reenviar(): void
    {
        $clave = 'intento-que-falla';

        // Monto invalido: la validacion lo rechaza.
        $this->actingAs($this->empleado)
            ->post(route('separados.abonar', $this->separado), ['monto' => 0, '_clave' => $clave])
            ->assertSessionHasErrors('monto');

        // La clave no puede quedar quemada: el empleado corrige y reenvia.
        $this->assertSame(0, Operacion::count());

        $this->actingAs($this->empleado)
            ->post(route('separados.abonar', $this->separado), ['monto' => 90000, '_clave' => $clave])
            ->assertRedirect();

        $this->assertSame(90000, (int) $this->separado->fresh()->abonado);
    }

    #[Test]
    public function test_la_operacion_repetida_lleva_a_donde_llevo_la_primera(): void
    {
        $clave = 'abono-con-destino';

        $primera = $this->actingAs($this->empleado)
            ->post(route('separados.abonar', $this->separado), ['monto' => 60000, '_clave' => $clave]);
        $destino = $primera->headers->get('Location');

        $segunda = $this->actingAs($this->empleado)
            ->post(route('separados.abonar', $this->separado), ['monto' => 60000, '_clave' => $clave]);

        $this->assertSame($destino, $segunda->headers->get('Location'));
        $segunda->assertSessionHas('ok', 'Esa operación ya estaba registrada.');
    }

    #[Test]
    public function test_no_se_crea_un_empeno_dos_veces(): void
    {
        $cliente = Cliente::firstOrFail();
        $clave = 'empeno-repetido';

        $datos = [
            'cliente_id' => $cliente->id,
            'categoria' => 'Otro',
            'articulo' => 'Equipo de sonido',
            'principal' => 100000,
            'pct' => 20,
            'plazo' => 4,
            '_clave' => $clave,
        ];

        $this->actingAs($this->empleado)->post(route('empenos.store'), $datos)->assertRedirect();
        $this->actingAs($this->empleado)->post(route('empenos.store'), $datos)->assertRedirect();

        // Un empeño duplicado seria un contrato duplicado y plata prestada dos veces.
        $this->assertSame(1, $this->negocio->empenos()->count());
    }

    #[Test]
    public function test_una_compra_de_inventario_no_se_duplica(): void
    {
        $cajaAntes = (int) $this->negocio->fresh()->caja;
        $datos = ['descripcion' => 'Nevera', 'costo' => 150000, '_clave' => 'compra-1'];

        $this->actingAs($this->empleado)->post(route('inventario.comprar'), $datos);
        $this->actingAs($this->empleado)->post(route('inventario.comprar'), $datos);

        $this->assertSame($cajaAntes - 150000, (int) $this->negocio->fresh()->caja);
        $this->assertSame(1, InventarioItem::where('descripcion', 'Nevera')->count());
    }
}
