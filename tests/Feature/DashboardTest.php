<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_un_usuario_con_local_ve_el_tablero(): void
    {
        $this->seed(DatabaseSeeder::class);
        $dueno = User::where('email', 'dueno@boveda.test')->firstOrFail();

        $this->actingAs($dueno)->get(route('dashboard'))->assertOk();
    }

    public function test_un_usuario_sin_local_no_entra(): void
    {
        // Regla del sistema: sin local asignado no se ve ningún dato de negocio.
        $suelto = User::factory()->create();

        $this->actingAs($suelto)->get(route('dashboard'))->assertForbidden();
    }
}
