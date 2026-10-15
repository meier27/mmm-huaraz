<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_an_active_admin(): void
    {
        $this->artisan('app:create-admin')
            ->expectsQuestion('Nombre completo', 'Admin Huaraz')
            ->expectsQuestion('Correo electrónico', 'admin@example.test')
            ->expectsQuestion('Contraseña', 'clave-segura-1')
            ->expectsQuestion('Repite la contraseña', 'clave-segura-1')
            ->assertSuccessful();

        $user = User::where('email', 'admin@example.test')->firstOrFail();
        $this->assertSame(Role::Admin, $user->role);
        $this->assertTrue($user->is_active);
        $this->assertTrue(Hash::check('clave-segura-1', $user->password));
    }

    public function test_it_fails_when_passwords_do_not_match(): void
    {
        $this->artisan('app:create-admin')
            ->expectsQuestion('Nombre completo', 'Admin Huaraz')
            ->expectsQuestion('Correo electrónico', 'admin@example.test')
            ->expectsQuestion('Contraseña', 'clave-segura-1')
            ->expectsQuestion('Repite la contraseña', 'otra-clave')
            ->assertFailed();

        $this->assertDatabaseMissing('users', ['email' => 'admin@example.test']);
    }
}
