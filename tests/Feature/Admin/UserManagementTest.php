<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Livewire\Admin\UserManager;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admins_can_open_users_and_audit_screens(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get('/admin/usuarios')->assertOk();
        $this->actingAs($admin)->get('/admin/auditoria')->assertOk();

        $editor = User::factory()->role(Role::Editor)->create();
        $this->actingAs($editor)->get('/admin/usuarios')->assertForbidden();
        $this->actingAs($editor)->get('/admin/auditoria')->assertForbidden();

        Livewire::actingAs($editor)->test(UserManager::class)->assertForbidden();
    }

    public function test_admin_creates_a_cashier_account(): void
    {
        Livewire::actingAs(User::factory()->admin()->create())
            ->test(UserManager::class)
            ->call('create')
            ->set('form.name', 'Cajera Uno')
            ->set('form.email', 'caja@example.test')
            ->set('form.role', 'cashier')
            ->set('form.password', 'clave-segura-1')
            ->call('save')
            ->assertHasNoErrors();

        $user = User::where('email', 'caja@example.test')->sole();
        $this->assertSame(Role::Cashier, $user->role);
        $this->assertTrue(Hash::check('clave-segura-1', $user->password));
    }

    public function test_password_is_required_on_create_and_optional_on_edit(): void
    {
        $component = Livewire::actingAs(User::factory()->admin()->create())
            ->test(UserManager::class)
            ->call('create')
            ->set('form.name', 'Sin clave')
            ->set('form.email', 'sinclave@example.test')
            ->call('save')
            ->assertHasErrors(['form.password' => 'required']);

        $user = User::factory()->create(['password' => 'clave-original']);

        $component->call('edit', $user->id)
            ->set('form.name', 'Nombre nuevo')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('clave-original', $user->fresh()->password));
        $this->assertSame('Nombre nuevo', $user->fresh()->name);
    }

    public function test_invalid_role_is_rejected(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs(User::factory()->admin()->create())
            ->test(UserManager::class)
            ->call('edit', $user->id)
            ->set('form.role', 'superadmin')
            ->call('save')
            ->assertHasErrors(['form.role']);

        $this->assertSame(Role::Editor, $user->fresh()->role);
    }

    public function test_admin_cannot_demote_or_deactivate_themselves(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->admin()->create(); // hay otro admin: la regla es por ser uno mismo

        Livewire::actingAs($admin)->test(UserManager::class)
            ->call('edit', $admin->id)
            ->set('form.role', 'editor')
            ->call('save')
            ->assertHasErrors('form.role')
            ->set('form.role', 'admin')
            ->set('form.is_active', false)
            ->call('save')
            ->assertHasErrors('form.role');

        $this->assertTrue($admin->fresh()->isAdmin());
    }

    public function test_admin_can_demote_another_admin_while_one_active_admin_remains(): void
    {
        $otherAdmin = User::factory()->admin()->create();
        $operator = User::factory()->admin()->create();

        Livewire::actingAs($operator)->test(UserManager::class)
            ->call('edit', $otherAdmin->id)
            ->set('form.role', 'editor')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(1, User::where('role', Role::Admin)->where('is_active', true)->count());
    }

    public function test_password_hash_is_never_stored_in_audit_log(): void
    {
        Livewire::actingAs(User::factory()->admin()->create())
            ->test(UserManager::class)
            ->call('create')
            ->set('form.name', 'Auditada')
            ->set('form.email', 'auditada@example.test')
            ->set('form.role', 'delivery')
            ->set('form.password', 'clave-segura-1')
            ->call('save');

        $log = AuditLog::where('action', 'created')->latest('id')->first();
        $this->assertSame('[oculto]', $log->new_values['password']);
        $this->assertSame('delivery', $log->new_values['role']);
    }
}
