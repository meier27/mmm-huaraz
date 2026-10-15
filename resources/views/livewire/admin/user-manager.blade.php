<div>
    <x-admin.page-header title="Usuarios" description="Cuentas del equipo. Las cuentas no se eliminan: se desactivan para conservar la auditoría.">
        <x-slot:actions>
            <x-ui.button wire:click="create">Nuevo usuario</x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    @if ($showForm)
        <x-admin.form-panel :title="$editingId ? 'Editar usuario' : 'Nuevo usuario'">
            <x-admin.input label="Nombre completo" model="form.name" autocomplete="off" />
            <x-admin.input label="Correo electrónico" model="form.email" type="email" autocomplete="off" />
            <x-admin.select label="Rol" model="form.role" :options="collect($roles)->mapWithKeys(fn ($r) => [$r->value => $r->label()])" />
            <x-admin.input label="{{ $editingId ? 'Nueva contraseña (opcional)' : 'Contraseña' }}" model="form.password" type="password" autocomplete="new-password"
                           :hint="$editingId ? 'Déjala vacía para no cambiarla.' : null" />
            <x-admin.checkbox label="Cuenta activa" model="form.is_active" />
        </x-admin.form-panel>
    @endif

    <ul class="divide-y divide-line rounded-card bg-white shadow-card">
        @foreach ($users as $user)
            <li wire:key="user-{{ $user->id }}" class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <p class="font-semibold">
                        {{ $user->name }}
                        @if ($user->is(auth()->user()))<span class="text-sm font-normal text-muted">(tú)</span>@endif
                    </p>
                    <p class="mt-1 text-sm text-muted">{{ $user->email }} · {{ $user->role->label() }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <span @class(['rounded-full px-3 py-1 text-xs font-semibold', 'bg-green-100 text-success' => $user->is_active, 'bg-stone-100 text-muted' => ! $user->is_active])>
                        {{ $user->is_active ? 'Activa' : 'Desactivada' }}
                    </span>
                    <x-ui.button variant="ghost" class="min-h-9! px-3! py-1! text-sm" wire:click="edit({{ $user->id }})">Editar</x-ui.button>
                </div>
            </li>
        @endforeach
    </ul>
</div>
