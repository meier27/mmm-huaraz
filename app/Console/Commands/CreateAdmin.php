<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class CreateAdmin extends Command
{
    protected $signature = 'app:create-admin';

    protected $description = 'Crea una cuenta de administrador (la contraseña se pide de forma interactiva)';

    public function handle(): int
    {
        $name = text('Nombre completo', required: true, validate: ['name' => 'max:255']);

        $email = text('Correo electrónico', required: true, validate: ['email' => 'email|max:255|unique:users,email']);

        // Nunca se acepta la contraseña como argumento: quedaría en el historial del shell.
        $password = password('Contraseña', required: true, validate: ['password' => Password::defaults()]);
        $confirmation = password('Repite la contraseña', required: true);

        if (! hash_equals($password, $confirmation)) {
            $this->error('Las contraseñas no coinciden.');

            return self::FAILURE;
        }

        $user = new User(['name' => $name, 'email' => $email, 'password' => $password]);
        $user->role = Role::Admin;
        $user->is_active = true;
        $user->save();

        $this->info("Administrador creado: {$user->email}");

        return self::SUCCESS;
    }
}
