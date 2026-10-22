<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AdminAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin')->assertRedirect(route('login'));
    }

    public function test_login_page_renders(): void
    {
        $this->get(route('login'))->assertOk()->assertSee('Acceso al panel');
    }

    public function test_active_user_can_log_in(): void
    {
        $user = User::factory()->create();

        $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_password_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->post(route('login'), ['email' => $user->email, 'password' => 'incorrecta'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_inactive_user_cannot_log_in(): void
    {
        $user = User::factory()->inactive()->create();

        $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_inactive_user_with_open_session_is_denied(): void
    {
        $user = User::factory()->inactive()->create();

        $this->actingAs($user)->get('/admin')->assertForbidden();
    }

    public function test_login_is_throttled_after_five_failed_attempts(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 5) as $attempt) {
            $this->post(route('login'), ['email' => $user->email, 'password' => 'incorrecta']);
        }

        $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors(['email' => __('auth.throttle', ['seconds' => RateLimiter::availableIn(strtolower($user->email).'|127.0.0.1')])]);

        $this->assertGuest();
    }

    public function test_authenticated_user_sees_dashboard_with_role(): void
    {
        $user = User::factory()->role(Role::Cashier)->create(['name' => 'Ana Prueba']);

        $this->actingAs($user)->get('/admin')
            ->assertOk()
            ->assertSee('Ana Prueba')
            ->assertSee('Tesorería');
    }

    public function test_user_can_log_out(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('admin.logout'))->assertRedirect(route('home'));

        $this->assertGuest();
    }

    public function test_logged_in_user_visiting_login_is_redirected_to_dashboard(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('login'))
            ->assertRedirect(route('admin.dashboard'));
    }
}
