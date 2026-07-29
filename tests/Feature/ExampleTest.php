<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_raiz_lleva_al_tablero(): void
    {
        // La página de inicio no muestra contenido propio: envía al tablero
        // (y de ahí, si no hay sesión, al login).
        $this->get(route('home'))->assertRedirect(route('dashboard'));
    }
}
