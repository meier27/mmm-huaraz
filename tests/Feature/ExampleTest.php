<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_home_page_shows_verified_convention_data(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('Familia hay esperanza')
            ->assertSee('Estadio Rosas Pampa')
            ->assertSee('25 de octubre de 2026');
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
