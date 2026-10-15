@props(['id', 'confirm' => '¿Eliminar este registro? Esta acción no se puede deshacer.'])

<div class="flex shrink-0 gap-1">
    <x-ui.button variant="ghost" class="min-h-9! px-3! py-1! text-sm" wire:click="edit({{ $id }})">Editar</x-ui.button>
    <x-ui.button variant="ghost" class="min-h-9! px-3! py-1! text-sm text-danger!" wire:click="delete({{ $id }})" wire:confirm="{{ $confirm }}">Eliminar</x-ui.button>
</div>
