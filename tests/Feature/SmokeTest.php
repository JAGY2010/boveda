<?php

namespace Tests\Feature;

use App\Models\Empeno;
use App\Models\Negocio;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SmokeTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed(DatabaseSeeder::class);

        return User::where('email', 'admin@boveda.test')->firstOrFail();
    }

    private function owner(): User
    {
        $this->seed(DatabaseSeeder::class);

        return User::where('email', 'dueno@boveda.test')->firstOrFail();
    }

    public function test_paginas_de_invitado_cargan(): void
    {
        $this->get('/login')->assertOk();
        $this->get('/register')->assertOk();
    }

    public function test_todas_las_pantallas_cargan(): void
    {
        // El dueño es quien opera un local (el admin gestiona desde el panel).
        $this->actingAs($this->owner());
        $id = Empeno::query()->min('id');

        $rutas = [
            '/dashboard', '/clientes', '/clientes/nuevo',
            '/empenos', '/empenos/nuevo', "/empenos/{$id}", "/empenos/{$id}/contrato",
            '/inventario', '/contabilidad', '/configuracion', '/equipo', '/consolidado',
        ];

        foreach ($rutas as $ruta) {
            $this->get($ruta)->assertOk();
        }
    }

    public function test_el_empleado_no_ve_contabilidad(): void
    {
        $this->seed(DatabaseSeeder::class);
        $empleado = User::where('email', 'empleado@boveda.test')->firstOrFail();

        $this->actingAs($empleado)->get('/contabilidad')->assertForbidden();
        $this->actingAs($empleado)->get('/empenos')->assertOk();
    }

    public function test_pago_corre_el_vencimiento(): void
    {
        $this->actingAs($this->owner());
        $empeno = Empeno::where('estado', 'activo')->firstOrFail();
        $antes = $empeno->meses_pagados;

        $this->post("/empenos/{$empeno->id}/pago", [])->assertRedirect();

        $this->assertEquals($antes + 1, $empeno->fresh()->meses_pagados);
    }

    public function test_crear_empeno_con_cliente_nuevo(): void
    {
        $this->actingAs($this->owner());

        $this->post('/empenos', [
            'nuevo_nombre' => 'Cliente Prueba',
            'nuevo_cedula' => '99999',
            'categoria' => 'Moto',
            'atributos' => ['marca' => 'Honda', 'cilindraje' => '125', 'anio' => '2020'],
            'principal' => 500000,
            'pct' => 20,
            'plazo' => 4,
        ])->assertRedirect();

        $this->assertDatabaseHas('clientes', ['nombre' => 'Cliente Prueba']);
        $this->assertDatabaseHas('empenos', ['saldo' => 500000, 'categoria' => 'Moto']);
    }

    public function test_dueno_crea_empleado(): void
    {
        $this->actingAs($this->owner());

        $this->get('/equipo')->assertOk();
        $this->post('/equipo', [
            'name' => 'Empleado Nuevo', 'email' => 'emp2@boveda.test', 'password' => 'secret123',
        ])->assertRedirect();

        $this->assertDatabaseHas('users', ['email' => 'emp2@boveda.test', 'role' => 'employee']);
    }

    public function test_admin_crea_local_y_usuario(): void
    {
        $this->actingAs($this->admin());

        foreach (['/admin/locales', '/admin/locales/nuevo', '/admin/usuarios', '/admin/usuarios/nuevo'] as $ruta) {
            $this->get($ruta)->assertOk();
        }

        $this->post('/admin/locales', [
            'nombre' => 'Local Nuevo', 'ciudad' => 'Neiva', 'plazo_default' => 4,
            'pct_default' => 20, 'ltv_default' => 50, 'caja_inicial' => 1000000,
            'consecutivo_inicial' => 1,
        ])->assertRedirect('/admin/locales');
        $this->assertDatabaseHas('negocios', ['nombre' => 'Local Nuevo', 'caja' => 1000000]);

        $localId = Negocio::where('nombre', 'Local Nuevo')->value('id');
        $this->post('/admin/usuarios', [
            'name' => 'Nuevo Empleado', 'email' => 'nuevo@boveda.test', 'password' => 'secret123',
            'role' => 'employee', 'locales' => [$localId],
        ])->assertRedirect();
        $this->assertDatabaseHas('users', ['email' => 'nuevo@boveda.test', 'role' => 'employee']);
    }

    public function test_admin_entra_a_un_local(): void
    {
        $admin = $this->admin();
        $localId = Negocio::query()->min('id');

        // Sin entrar, el tablero lo manda al panel; al entrar (sesión), funciona.
        $this->actingAs($admin)->get('/dashboard')->assertRedirect(route('admin.locales.index'));
        $this->actingAs($admin)->withSession(['local_id' => $localId])->get('/dashboard')->assertOk();
    }

    public function test_multi_local_y_consolidado(): void
    {
        $dueno = $this->owner();
        $empleado = User::where('email', 'empleado@boveda.test')->firstOrFail();

        // El dueño tiene 2 locales -> ve el consolidado y puede cambiar de local.
        $this->actingAs($dueno)->get('/consolidado')->assertOk();
        $ids = $dueno->accessibleNegocioIds();
        $this->assertCount(2, $ids);
        $this->actingAs($dueno)->post('/local/cambiar', ['local_id' => $ids[1]])->assertRedirect();

        // El empleado: sin consolidado ni panel de admin.
        $this->actingAs($empleado)->get('/consolidado')->assertForbidden();
        $this->actingAs($empleado)->get('/admin/locales')->assertForbidden();
    }

    public function test_config_edita_datos_del_negocio(): void
    {
        $this->actingAs($this->owner());

        $this->put('/configuracion', [
            'nombre' => 'La Playita Editada', 'nit' => '999-9', 'ciudad' => 'X',
            'representante' => 'Nuevo Rep', 'direccion' => 'Calle X', 'telefono' => '300',
            'plazo_default' => 4, 'pct_default' => 20, 'ltv_default' => 50, 'consecutivo_inicial' => 5364,
        ])->assertRedirect();

        $this->assertDatabaseHas('negocios', ['nombre' => 'La Playita Editada', 'nit' => '999-9', 'representante' => 'Nuevo Rep']);
    }

    public function test_interes_corrido_por_cuartos(): void
    {
        $this->seed(DatabaseSeeder::class);
        $e = Empeno::where('estado', 'activo')->firstOrFail();
        $e->update(['saldo' => 1000000, 'pct' => 8, 'meses_pagados' => 0]);
        $e->refresh();

        $this->assertEquals(80000, $e->interesMes());

        // [días transcurridos => interés corrido esperado]
        $casos = [
            [0, 0],        // mismo día: nada
            [5, 20000],    // 1er cuarto (mitad de la mitad)
            [15, 40000],   // mitad de mes
            [20, 60000],   // 3er cuarto
            [30, 80000],   // mes completo
            [45, 120000],  // más de un mes: sigue corriendo
        ];

        foreach ($casos as [$dias, $esperado]) {
            $e->inicio = now()->subDays($dias);
            $this->assertEquals($esperado, $e->interesCorrido(), "a los {$dias} días");
        }
    }

    public function test_pago_con_interes_recibido_distinto(): void
    {
        $this->actingAs($this->owner());
        $e = Empeno::where('estado', 'activo')->firstOrFail();
        $cajaAntes = (int) $e->negocio->caja;

        $this->post("/empenos/{$e->id}/pago", ['interes_recibido' => 12345])->assertRedirect();

        $this->assertEquals($cajaAntes + 12345, (int) $e->negocio()->first()->caja);
        $this->assertDatabaseHas('pagos', ['empeno_id' => $e->id, 'interes' => 12345]);
    }

    public function test_retiro_con_valor_recibido(): void
    {
        $this->actingAs($this->owner());
        $e = Empeno::where('estado', 'activo')->firstOrFail();
        $n = $e->negocio;
        $cajaAntes = (int) $n->caja;
        $prestadoAntes = (int) $n->prestado;
        $saldo = (int) $e->saldo;
        $recibido = $saldo + 12345; // valor distinto al esperado

        $this->post("/empenos/{$e->id}/retirar", ['valor_recibido' => $recibido])->assertRedirect();

        $e->refresh();
        $n->refresh();
        $this->assertEquals('retirado', $e->estado);
        $this->assertEquals(0, (int) $e->saldo);
        $this->assertEquals($cajaAntes + $recibido, (int) $n->caja);
        $this->assertEquals($prestadoAntes - $saldo, (int) $n->prestado);
    }

    public function test_suscripcion_vencida_bloquea_operacion(): void
    {
        $dueno = $this->owner();
        $empleado = User::where('email', 'empleado@boveda.test')->firstOrFail();
        $admin = User::where('email', 'admin@boveda.test')->firstOrFail();

        // Vencer todos los locales del dueño (ayer) -> queda bloqueado sin importar el activo.
        Negocio::whereIn('id', $dueno->accessibleNegocioIds())
            ->update(['suscripcion_hasta' => now()->subDay()->toDateString()]);

        $this->actingAs($dueno)->get('/dashboard')->assertRedirect(route('suscripcion.bloqueada'));
        $this->actingAs($dueno)->get(route('suscripcion.bloqueada'))->assertOk();
        $this->actingAs($empleado)->get('/empenos')->assertRedirect(route('suscripcion.bloqueada'));

        // El admin NO se bloquea aunque entre a un local vencido.
        $localVencido = Negocio::whereIn('id', $dueno->accessibleNegocioIds())->first();
        $this->actingAs($admin)->withSession(['local_id' => $localVencido->id])->get('/dashboard')->assertOk();
    }

    public function test_admin_renueva_suspende_reactiva(): void
    {
        $admin = $this->admin();
        $local = Negocio::query()->first();
        $local->update(['suscripcion_hasta' => now()->subDay()->toDateString(), 'suspendido' => false]);

        // Renovar 2 meses (estaba vencido -> cuenta desde hoy) -> activa y futuro.
        $this->actingAs($admin)->post(route('admin.locales.renovar', $local), ['meses' => 2])->assertRedirect();
        $local->refresh();
        $this->assertTrue($local->suscripcion_hasta->isFuture());
        $this->assertEquals('activa', $local->estadoSuscripcion());

        // Suspender -> bloqueada; Reactivar -> deja de estarlo.
        $this->actingAs($admin)->post(route('admin.locales.suspender', $local))->assertRedirect();
        $this->assertTrue($local->refresh()->suscripcionBloqueada());
        $this->actingAs($admin)->post(route('admin.locales.reactivar', $local))->assertRedirect();
        $this->assertFalse($local->refresh()->suspendido);
    }

    public function test_admin_restablece_contrasena(): void
    {
        $admin = $this->admin();
        $empleado = User::where('email', 'empleado@boveda.test')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.usuarios.password', $empleado), ['password' => 'nuevaclave123'])
            ->assertRedirect();

        $this->assertTrue(Hash::check('nuevaclave123', $empleado->fresh()->password));
    }

    public function test_directorio_y_detalle_de_local(): void
    {
        $admin = $this->admin();
        $local = Negocio::query()->first();

        $this->actingAs($admin)->get(route('admin.locales.show', $local))->assertOk();
        $this->actingAs($admin)->get(route('admin.usuarios.index', ['q' => 'dueno', 'role' => 'owner']))->assertOk();
        $this->actingAs($admin)->get(route('admin.usuarios.create', ['local' => $local->id]))->assertOk();
    }

    public function test_local_nuevo_trae_prueba_activa(): void
    {
        $this->actingAs($this->admin());

        $this->post('/admin/locales', [
            'nombre' => 'Prueba Suscripcion', 'ciudad' => 'X', 'plazo_default' => 4,
            'pct_default' => 20, 'ltv_default' => 50, 'caja_inicial' => 0, 'consecutivo_inicial' => 1,
        ])->assertRedirect('/admin/locales');

        $n = Negocio::where('nombre', 'Prueba Suscripcion')->firstOrFail();
        $this->assertNotNull($n->suscripcion_hasta);
        $this->assertEquals('activa', $n->estadoSuscripcion());
    }

    public function test_admin_sin_locales_va_al_panel(): void
    {
        // Producción recién desplegada: hay admin pero aún no hay locales.
        $admin = User::factory()->create(['role' => 'admin', 'negocio_id' => null]);

        foreach (['/dashboard', '/clientes', '/empenos', '/inventario', '/contabilidad', '/configuracion'] as $ruta) {
            $this->actingAs($admin)->get($ruta)->assertRedirect(route('admin.locales.index'));
        }

        $this->actingAs($admin)->get('/admin/locales')->assertOk();
        $this->actingAs($admin)->get('/admin/usuarios')->assertOk();
    }
}
