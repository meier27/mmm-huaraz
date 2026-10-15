<?php

namespace App\Livewire\Forms;

use App\Enums\Role;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserForm extends ContentForm
{
    protected array $except = ['password'];

    public string $name = '';

    public string $email = '';

    public string $role = 'editor';

    public bool $is_active = true;

    public string $password = '';

    protected function rules(): array
    {
        $editingId = $this->getComponent()->editingId;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($editingId)],
            'role' => ['required', Rule::enum(Role::class)],
            'is_active' => ['boolean'],
            // Obligatoria al crear; al editar, vacía significa "no cambiar".
            'password' => [$editingId ? 'nullable' : 'required', 'string', 'max:255', Password::defaults()],
        ];
    }
}
