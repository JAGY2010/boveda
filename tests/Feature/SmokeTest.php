<?php

namespace Tests\Feature;

use App\Models\Empeno;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmokeTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed(DatabaseSeeder::class);

        return User::where('email', 'admin@boveda.test')->firstOrFail();
    }

    public function test_paginas_de_invitado_cargan(): void
    {
        $this->get('/login')->assertOk();
        $this->get('/register')->assertOk();
    }

    public function test_todas_las_pantallas_cargan(): void
    {
        $this->actingAs($this->admin());
        $id = Empeno::query()->min('id');

        $rutas = [
            '/dashboard', '/clientes', '/clientes/nuevo',
            '/empenos', '/empenos/nuevo', "/empenos/{$id}", "/empenos/{$id}/contrato",
            '/inventario', '/contabilidad', '/configuracion',
        ];

        foreach ($rutas as $ruta) {
            $this->get($ruta)->assertOk();
        }
    }

    public function test_el_empleado_no_ve_contabilidad(): void
    {
        $this->admin();
        $empleado = User::where('email', 'empleado@boveda.test')->firstOrFail();

        $this->actingAs($empleado)->get('/contabilidad')->assertForbidden();
        $this->actingAs($empleado)->get('/empenos')->assertOk();
    }

    public function test_pago_corre_el_vencimiento(): void
    {
        $this->actingAs($this->admin());
        $empeno = Empeno::where('estado', 'activo')->firstOrFail();
        $antes = $empeno->meses_pagados;

        $this->post("/empenos/{$empeno->id}/pago", [])->assertRedirect();

        $this->assertEquals($antes + 1, $empeno->fresh()->meses_pagados);
    }

    public function test_crear_empeno_con_cliente_nuevo(): void
    {
        $this->actingAs($this->admin());

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

        $localId = \App\Models\Negocio::where('nombre', 'Local Nuevo')->value('id');
        $this->post('/admin/usuarios', [
            'name' => 'Nuevo Empleado', 'email' => 'nuevo@boveda.test', 'password' => 'secret123',
            'role' => 'employee', 'locales' => [$localId],
        ])->assertRedirect();
        $this->assertDatabaseHas('users', ['email' => 'nuevo@boveda.test', 'role' => 'employee']);
    }

    public function test_multi_local_y_consolidado(): void
    {
        $this->admin();
        $dueno = User::where('email', 'dueno@boveda.test')->firstOrFail();
        $empleado = User::where('email', 'empleado@boveda.test')->firstOrFail();

        // El dueño tiene 2 locales -> ve el consolidado y puede cambiar de local
        $this->actingAs($dueno)->get('/consolidado')->assertOk();
        $ids = $dueno->accessibleNegocioIds();
        $this->assertCount(2, $ids);
        $this->actingAs($dueno)->post('/local/cambiar', ['local_id' => $ids[1]])->assertRedirect();

        // El empleado: sin consolidado ni panel de admin
        $this->actingAs($empleado)->get('/consolidado')->assertForbidden();
        $this->actingAs($empleado)->get('/admin/locales')->assertForbidden();
    }

    public function test_config_edita_datos_del_negocio(): void
    {
        $this->actingAs($this->admin());

        $this->put('/configuracion', [
            'nombre' => 'La Playita Editada', 'nit' => '999-9', 'ciudad' => 'X',
            'representante' => 'Nuevo Rep', 'direccion' => 'Calle X', 'telefono' => '300',
            'plazo_default' => 4, 'pct_default' => 20, 'ltv_default' => 50, 'consecutivo_inicial' => 5364,
        ])->assertRedirect();

        $this->assertDatabaseHas('negocios', ['nombre' => 'La Playita Editada', 'nit' => '999-9', 'representante' => 'Nuevo Rep']);
    }

    public function test_admin_sin_locales_va_al_panel(): void
    {
        // Producción recién desplegada: hay admin pero aún no hay locales.
        $admin = User::factory()->create(['role' => 'admin', 'negocio_id' => null]);

        $this->actingAs($admin)->get('/dashboard')->assertRedirect(route('admin.locales.index'));
    }
}
