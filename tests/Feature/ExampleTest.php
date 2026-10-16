<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_sin_sesion_la_raiz_muestra_la_bienvenida(): void
    {
        // Quien llega sin sesión ve qué es Bóveda en vez de rebotar al login.
        $this->get(route('home'))->assertOk()->assertViewIs('bienvenida')
            ->assertSee('href="https://gaviriapp.dev/"', false);
    }

    public function test_con_sesion_la_raiz_lleva_al_tablero(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('home'))
            ->assertRedirect(route('dashboard'));
    }
}
