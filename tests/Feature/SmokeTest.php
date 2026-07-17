<?php

namespace Tests\Feature;

use App\Models\Cliente;
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
            '/inventario', '/contabilidad', '/reporte', '/configuracion', '/equipo', '/consolidado',
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
        $this->actingAs($empleado)->get('/configuracion')->assertForbidden();
        $this->actingAs($empleado)->get('/empenos')->assertOk();
    }

    public function test_numero_a_letras(): void
    {
        $this->assertEquals('cero', numeroALetras(0));
        $this->assertEquals('mil', numeroALetras(1000));
        $this->assertEquals('cien mil', numeroALetras(100000));
        $this->assertEquals('veintiún mil', numeroALetras(21000));
        $this->assertEquals('un millón', numeroALetras(1000000));
        $this->assertEquals('un millón doscientos mil', numeroALetras(1200000));
        $this->assertEquals('un millón doscientos treinta y cuatro mil quinientos sesenta y siete', numeroALetras(1234567));
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
            'nuevo_contacto2' => '3001112233',
            'categoria' => 'Moto',
            'atributos' => ['marca' => 'Honda', 'cilindraje' => '125', 'anio' => '2020'],
            'color' => 'Negra',
            'observaciones' => 'Buen estado general',
            'principal' => 500000,
            'pct' => 20,
            'plazo' => 4,
        ])->assertRedirect();

        $this->assertDatabaseHas('clientes', ['nombre' => 'Cliente Prueba', 'contacto2' => '3001112233']);
        $this->assertDatabaseHas('empenos', ['saldo' => 500000, 'categoria' => 'Moto', 'color' => 'Negra', 'observaciones' => 'Buen estado general']);

        // El contrato renderiza (2 copias, una por página) sin error.
        $empeno = Empeno::where('color', 'Negra')->firstOrFail();
        $this->get("/empenos/{$empeno->id}/contrato")->assertOk();
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

        // Fijar la fecha de vencimiento (calendario) -> activa, y reactiva.
        $fecha = now()->addMonths(2)->toDateString();
        $this->actingAs($admin)->post(route('admin.locales.renovar', $local), ['fecha' => $fecha])->assertRedirect();
        $local->refresh();
        $this->assertEquals($fecha, $local->suscripcion_hasta->toDateString());
        $this->assertEquals('activa', $local->estadoSuscripcion());

        // Fecha en el pasado -> rechazada.
        $this->actingAs($admin)
            ->post(route('admin.locales.renovar', $local), ['fecha' => now()->subDay()->toDateString()])
            ->assertSessionHasErrors('fecha');

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

    public function test_owner_elimina_empeno_por_error(): void
    {
        $dueno = $this->owner();

        $this->actingAs($dueno)->post('/empenos', [
            'nuevo_nombre' => 'Error Cliente', 'nuevo_cedula' => '88888',
            'categoria' => 'Otro', 'principal' => 300000, 'pct' => 20, 'plazo' => 4,
        ])->assertRedirect();

        $empeno = Empeno::where('principal', 300000)->latest('id')->firstOrFail();
        $negocio = $empeno->negocio;
        $cajaAntes = (int) $negocio->caja;

        $this->actingAs($dueno)
            ->delete("/empenos/{$empeno->id}", ['motivo' => 'creado por error'])
            ->assertRedirect(route('empenos.index'));

        $this->assertDatabaseMissing('empenos', ['id' => $empeno->id]);
        $this->assertDatabaseHas('eliminaciones', ['numero' => $empeno->numero, 'motivo' => 'creado por error']);
        $this->assertEquals($cajaAntes + 300000, (int) $negocio->fresh()->caja);
    }

    public function test_empleado_no_elimina_ni_ve_historial(): void
    {
        $this->seed(DatabaseSeeder::class);
        $empleado = User::where('email', 'empleado@boveda.test')->firstOrFail();
        $empeno = Empeno::where('estado', 'activo')->firstOrFail();

        $this->actingAs($empleado)->delete("/empenos/{$empeno->id}")->assertForbidden();
        $this->actingAs($empleado)->get('/eliminaciones')->assertForbidden();
        $this->assertDatabaseHas('empenos', ['id' => $empeno->id]);
    }

    public function test_no_elimina_empeno_con_pagos(): void
    {
        $dueno = $this->owner();
        $empeno = Empeno::where('estado', 'activo')->firstOrFail();

        $this->actingAs($dueno)->post("/empenos/{$empeno->id}/pago", [])->assertRedirect();
        $this->actingAs($dueno)->delete("/empenos/{$empeno->id}")->assertRedirect();

        $this->assertDatabaseHas('empenos', ['id' => $empeno->id]);
        $this->actingAs($dueno)->get('/eliminaciones')->assertOk();
    }

    public function test_empeno_con_fecha_de_inicio_pasada(): void
    {
        $dueno = $this->owner();
        $fecha = now()->subMonths(2)->toDateString();

        $this->actingAs($dueno)->post('/empenos', [
            'nuevo_nombre' => 'Antiguo', 'nuevo_cedula' => '77777',
            'categoria' => 'Otro', 'principal' => 400000, 'pct' => 20, 'plazo' => 4,
            'inicio' => $fecha,
        ])->assertRedirect();

        $e = Empeno::where('principal', 400000)->latest('id')->firstOrFail();
        $this->assertEquals($fecha, $e->inicio->toDateString());

        // Fecha futura -> rechazada.
        $this->actingAs($dueno)->post('/empenos', [
            'nuevo_nombre' => 'Futuro', 'nuevo_cedula' => '66666',
            'categoria' => 'Otro', 'principal' => 100000, 'pct' => 20, 'plazo' => 4,
            'inicio' => now()->addDay()->toDateString(),
        ])->assertSessionHasErrors('inicio');
    }

    public function test_editar_cliente_actualiza_contacto(): void
    {
        $dueno = $this->owner();
        $cliente = Cliente::whereIn('negocio_id', $dueno->accessibleNegocioIds())->firstOrFail();

        $this->actingAs($dueno)->get(route('clientes.edit', $cliente))->assertOk();
        $this->actingAs($dueno)->put(route('clientes.update', $cliente), [
            'nombre' => $cliente->nombre,
            'tel' => '3209998877',
            'contacto2' => '3111112222',
        ])->assertRedirect(route('clientes.index'));

        $fresh = $cliente->fresh();
        $this->assertEquals('3209998877', $fresh->tel);
        $this->assertEquals('3111112222', $fresh->contacto2);
    }

    public function test_owner_deshace_pago(): void
    {
        $dueno = $this->owner();
        $empeno = Empeno::where('estado', 'activo')->firstOrFail();
        $negocio = $empeno->negocio;
        $mesesAntes = (int) $empeno->meses_pagados;
        $saldoAntes = (int) $empeno->saldo;
        $cajaAntes = (int) $negocio->caja;

        // Pago con abono a capital
        $this->actingAs($dueno)->post("/empenos/{$empeno->id}/pago", ['abono' => 10000])->assertRedirect();
        $pago = $empeno->pagos()->reorder('id', 'desc')->firstOrFail();
        $abono = (int) $pago->abono;
        $this->assertEquals($saldoAntes - $abono, (int) $empeno->fresh()->saldo);

        // Deshacer -> todo vuelve como estaba
        $this->actingAs($dueno)->delete("/pagos/{$pago->id}")->assertRedirect();

        $this->assertDatabaseMissing('pagos', ['id' => $pago->id]);
        $this->assertEquals($mesesAntes, (int) $empeno->fresh()->meses_pagados);
        $this->assertEquals($saldoAntes, (int) $empeno->fresh()->saldo);
        $this->assertEquals($cajaAntes, (int) $negocio->fresh()->caja);
    }

    public function test_empleado_no_deshace_pago(): void
    {
        $this->seed(DatabaseSeeder::class);
        $dueno = User::where('email', 'dueno@boveda.test')->firstOrFail();
        $empleado = User::where('email', 'empleado@boveda.test')->firstOrFail();
        $empeno = Empeno::where('estado', 'activo')->firstOrFail();

        $this->actingAs($dueno)->post("/empenos/{$empeno->id}/pago", [])->assertRedirect();
        $pago = $empeno->pagos()->reorder('id', 'desc')->firstOrFail();

        $this->actingAs($empleado)->delete("/pagos/{$pago->id}")->assertForbidden();
        $this->assertDatabaseHas('pagos', ['id' => $pago->id]);
    }

    public function test_pago_con_fecha_pasada(): void
    {
        $dueno = $this->owner();
        $empeno = Empeno::where('estado', 'activo')->firstOrFail();
        $fecha = now()->subMonth()->toDateString();

        $this->actingAs($dueno)->post("/empenos/{$empeno->id}/pago", ['fecha' => $fecha])->assertRedirect();

        $pago = $empeno->pagos()->reorder('id', 'desc')->firstOrFail();
        $this->assertEquals($fecha, $pago->fecha->toDateString());
    }

    public function test_logo_se_guarda_en_la_base_de_datos(): void
    {
        $dueno = $this->owner();
        $negocio = Negocio::find($dueno->negocio_id);

        $this->actingAs($dueno)->put('/configuracion', [
            'nombre' => $negocio->nombre,
            'plazo_default' => 4, 'pct_default' => 20, 'ltv_default' => 50,
            'consecutivo_inicial' => 1,
            'logo' => \Illuminate\Http\UploadedFile::fake()->image('logo.png', 80, 80),
        ])->assertRedirect();

        $this->assertStringStartsWith('data:image/', (string) $negocio->fresh()->logo_data);
    }

    public function test_empeno_con_numero_manual(): void
    {
        $dueno = $this->owner();

        // Migrar un empeño viejo con su número de contrato real
        $this->actingAs($dueno)->post('/empenos', [
            'nuevo_nombre' => 'Viejo', 'nuevo_cedula' => '55555',
            'categoria' => 'Otro', 'principal' => 100000, 'pct' => 20, 'plazo' => 4,
            'numero' => 9999,
        ])->assertRedirect();
        $this->assertDatabaseHas('empenos', ['numero' => 9999, 'principal' => 100000]);

        // Uno nuevo sin número -> continúa desde el más alto (10000)
        $this->actingAs($dueno)->post('/empenos', [
            'nuevo_nombre' => 'Nuevo', 'nuevo_cedula' => '55556',
            'categoria' => 'Otro', 'principal' => 200000, 'pct' => 20, 'plazo' => 4,
        ])->assertRedirect();
        $this->assertDatabaseHas('empenos', ['numero' => 10000, 'principal' => 200000]);

        // Número repetido -> rechazado
        $this->actingAs($dueno)->post('/empenos', [
            'nuevo_nombre' => 'Repetido', 'nuevo_cedula' => '55557',
            'categoria' => 'Otro', 'principal' => 300000, 'pct' => 20, 'plazo' => 4,
            'numero' => 9999,
        ])->assertSessionHasErrors('numero');
    }

    public function test_owner_edita_numero_y_fecha_del_empeno(): void
    {
        $dueno = $this->owner();
        $empleado = User::where('email', 'empleado@boveda.test')->firstOrFail();
        $empeno = Empeno::where('estado', 'activo')->firstOrFail();
        $nuevaFecha = now()->subMonths(3)->toDateString();

        // El empleado no puede editar el número/fecha
        $this->actingAs($empleado)->put("/empenos/{$empeno->id}/datos", [
            'numero' => 77, 'inicio' => $nuevaFecha,
        ])->assertForbidden();

        // El dueño sí (número, fecha y datos del artículo)
        $this->actingAs($dueno)->put("/empenos/{$empeno->id}/datos", [
            'numero' => 88888, 'inicio' => $nuevaFecha,
            'articulo' => 'Moto Editada', 'serial' => 'ABC123', 'color' => 'Rojo', 'observaciones' => 'buen estado',
        ])->assertRedirect();

        $empeno->refresh();
        $this->assertEquals(88888, (int) $empeno->numero);
        $this->assertEquals($nuevaFecha, $empeno->inicio->toDateString());
        $this->assertEquals('Moto Editada', $empeno->articulo);
        $this->assertEquals('Rojo', $empeno->color);
    }

    public function test_reporte_solo_dueno(): void
    {
        $this->seed(DatabaseSeeder::class);
        $dueno = User::where('email', 'dueno@boveda.test')->firstOrFail();
        $empleado = User::where('email', 'empleado@boveda.test')->firstOrFail();

        $this->actingAs($dueno)->get('/reporte')->assertOk();
        $this->actingAs($dueno)->get('/reporte?periodo=mes')->assertOk();
        $this->actingAs($empleado)->get('/reporte')->assertForbidden();
    }

    public function test_filtro_empenos_por_estado(): void
    {
        $this->actingAs($this->owner());

        foreach (['activos', 'mora', 'perder', 'cerrados', 'todos'] as $e) {
            $this->get("/empenos?estado={$e}")->assertOk();
        }
    }

    public function test_recibo_de_pago_carga(): void
    {
        $dueno = $this->owner();
        $empeno = Empeno::where('estado', 'activo')->firstOrFail();

        $this->actingAs($dueno)->post("/empenos/{$empeno->id}/pago", [])->assertRedirect();
        $pago = $empeno->pagos()->reorder('id', 'desc')->firstOrFail();

        $this->actingAs($dueno)->get("/pagos/{$pago->id}/recibo")->assertOk();
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
