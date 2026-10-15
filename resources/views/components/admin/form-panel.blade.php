@props(['title', 'cancel' => true])

<form wire:submit="save" class="mb-8 rounded-card bg-white p-5 shadow-card sm:p-6" novalidate>
    <h2 class="text-lg font-bold">{{ $title }}</h2>

    <div class="mt-4 grid gap-4 sm:grid-cols-2">
        {{ $slot }}
    </div>

    <div class="mt-6 flex flex-wrap gap-2">
        <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="save">
            <span wire:loading.remove wire:target="save">Guardar</span>
            <span wire:loading wire:target="save">Guardando…</span>
        </x-ui.button>
        @if ($cancel)
            <x-ui.button variant="secondary" wire:click="cancel">Cancelar</x-ui.button>
        @endif
    </div>
</form>
