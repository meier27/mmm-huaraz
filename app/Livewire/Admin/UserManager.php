<?php

namespace App\Livewire\Admin;

use App\Enums\Role;
use App\Livewire\Forms\UserForm;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Gestión de cuentas del equipo (solo administradores).
 * Las cuentas no se eliminan: se desactivan, para conservar la auditoría.
 */
#[Layout('components.layouts.admin')]
#[Title('Usuarios')]
class UserManager extends Component
{
    public UserForm $form;

    #[Locked]
    public ?int $editingId = null;

    public bool $showForm = false;

    public function boot(): void
    {
        $this->authorize('manage-users');
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $this->resetForm();
        $user = User::findOrFail($id);
        $this->form->fillFrom($user);
        $this->editingId = $user->id;
        $this->showForm = true;
    }

    public function save(): void
    {
        $attributes = $this->form->attributes();
        $role = Role::from($attributes['role']);
        $isActive = (bool) $attributes['is_active'];

        DB::transaction(function () use ($attributes, $role, $isActive) {
            $user = $this->editingId ? User::lockForUpdate()->findOrFail($this->editingId) : new User;

            if ($user->exists) {
                $this->guardAgainstLockout($user, $role, $isActive);
            }

            $user->fill(['name' => $attributes['name'], 'email' => $attributes['email']]);
            $user->role = $role;
            $user->is_active = $isActive;

            if ($this->form->password !== '') {
                $user->password = $this->form->password;
            }

            $user->save();
        });

        $this->resetForm();
        session()->flash('status', 'Usuario guardado.');
    }

    public function cancel(): void
    {
        $this->resetForm();
    }

    public function render(): View
    {
        return view('livewire.admin.user-manager', [
            'users' => User::query()->orderByDesc('is_active')->orderBy('name')->get(),
            'roles' => Role::cases(),
        ]);
    }

    private function guardAgainstLockout(User $user, Role $role, bool $isActive): void
    {
        $losesAdmin = $role !== Role::Admin || ! $isActive;

        if ($user->is(auth()->user()) && $losesAdmin) {
            throw ValidationException::withMessages([
                'form.role' => 'No puedes quitarte el rol de administrador ni desactivar tu propia cuenta.',
            ]);
        }

        if ($losesAdmin && $user->isLastActiveAdmin()) {
            throw ValidationException::withMessages([
                'form.role' => 'Debe quedar al menos un administrador activo.',
            ]);
        }
    }

    private function resetForm(): void
    {
        $this->form->reset();
        $this->resetValidation();
        $this->editingId = null;
        $this->showForm = false;
    }
}
