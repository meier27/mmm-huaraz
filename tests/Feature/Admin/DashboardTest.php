<?php

namespace Tests\Feature\Admin;

use App\Enums\DonationMethodType;
use App\Enums\Role;
use App\Models\DonationMethod;
use App\Models\User;
use App\Services\DonationService;
use Database\Seeders\ConventionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ConventionSeeder::class);
    }

    public function test_admin_sees_sidebar_groups_metrics_and_pending_badge(): void
    {
        $method = DonationMethod::create(['type' => DonationMethodType::Yape, 'label' => 'Yape', 'is_published' => true]);
        app(DonationService::class)->report(['donation_method_id' => $method->id, 'declared_amount' => '30', 'operation_number' => '1', 'paid_on' => today()->toDateString()], null, null);

        $this->actingAs(User::factory()->admin()->create())->get('/admin')
            ->assertOk()
            ->assertSee('aria-label="Menú del panel"', false)
            ->assertSeeInOrder(['General', 'Sitio web', 'Convención', 'Administración'])
            ->assertSee('Reservas activas')
            ->assertSee('Porciones reservadas por día')
            ->assertSee('Aportes por verificar')
            ->assertSee('1<span class="sr-only"> pendientes</span>', false)
            ->assertSee('Contenido publicado');
    }

    public function test_editor_sees_content_but_no_money_or_admin_links(): void
    {
        $this->actingAs(User::factory()->role(Role::Editor)->create())->get('/admin')
            ->assertOk()
            ->assertSee('Contenido publicado')
            ->assertSee('Publicado en la web')
            ->assertDontSee('Cobrado en reservas')
            ->assertDontSee('Aportes por verificar')
            ->assertDontSee(route('admin.users'))
            ->assertDontSee(route('admin.donations'));
    }

    public function test_cashier_sees_reservations_and_donations_but_not_content_management(): void
    {
        $this->actingAs(User::factory()->role(Role::Cashier)->create())->get('/admin')
            ->assertOk()
            ->assertSee('Cobrado en reservas')
            ->assertSee('Saldo por cobrar')
            ->assertSee('Aportes por verificar')
            ->assertDontSee('Contenido publicado')
            ->assertDontSee(route('admin.banners'));
    }

    public function test_login_page_has_no_sidebar(): void
    {
        $this->get('/admin/login')->assertOk()->assertDontSee('aria-label="Menú del panel"', false);
    }
}
