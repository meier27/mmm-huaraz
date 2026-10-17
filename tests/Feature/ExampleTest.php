<?php

namespace Tests\Feature;

use Database\Seeders\ConventionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_shows_verified_convention_data(): void
    {
        $this->seed(ConventionSeeder::class);

        $this->get('/')->assertOk()->assertSee('Familia hay esperanza');

        $this->get('/eventos/convencion-nacional-2026')
            ->assertOk()
            ->assertSee('Familia hay esperanza')
            ->assertSee('Estadio Rosas Pampa')
            ->assertSee('22 al 25 de octubre de 2026');
    }

    public function test_home_page_works_before_seeding_without_inventing_an_event(): void
    {
        // Sin datos no se muestra ningún evento destacado (no se inventan datos).
        $this->get('/')->assertOk()->assertDontSee('Familia hay esperanza');
        $this->get('/eventos')->assertOk();
    }

    public function test_responses_include_security_headers(): void
    {
        $this->get('/')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_unknown_page_returns_404(): void
    {
        $this->get('/pagina-que-no-existe')->assertNotFound();
    }

    public function test_health_endpoint_is_available(): void
    {
        $this->get('/up')->assertOk();
    }
}
