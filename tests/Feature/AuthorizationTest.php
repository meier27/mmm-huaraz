<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_and_active_flag_are_not_mass_assignable(): void
    {
        $user = User::factory()->create();

        try {
            $user->update(['role' => 'admin', 'is_active' => false]);
            $this->fail('Se esperaba MassAssignmentException.');
        } catch (MassAssignmentException) {
            // Modo estricto: el intento se rechaza en lugar de ignorarse.
        }

        $user->refresh();
        $this->assertSame(Role::Editor, $user->role);
        $this->assertTrue($user->is_active);
    }

    public function test_only_admins_can_manage_users(): void
    {
        $this->assertTrue(Gate::forUser(User::factory()->admin()->create())->allows('manage-users'));

        foreach ([Role::Editor, Role::Cashier] as $role) {
            $this->assertFalse(Gate::forUser(User::factory()->role($role)->create())->allows('manage-users'), $role->value);
        }
    }

    public function test_only_admins_and_editors_can_manage_content(): void
    {
        $this->assertTrue(Gate::forUser(User::factory()->admin()->create())->allows('manage-content'));
        $this->assertTrue(Gate::forUser(User::factory()->role(Role::Editor)->create())->allows('manage-content'));
        $this->assertFalse(Gate::forUser(User::factory()->role(Role::Cashier)->create())->allows('manage-content'));
    }

    public function test_inactive_admin_loses_all_permissions(): void
    {
        $user = User::factory()->admin()->inactive()->create();

        $this->assertFalse(Gate::forUser($user)->allows('access-admin'));
        $this->assertFalse(Gate::forUser($user)->allows('manage-users'));
        $this->assertFalse(Gate::forUser($user)->allows('manage-content'));
    }
}
