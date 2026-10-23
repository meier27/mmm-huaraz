<div>
    <x-admin.page-header title="Canjes de premios" description="Pide al jugador su código, búscalo aquí y márcalo como entregado. Verifica que el nombre coincida con quien lo recibe." />

    @if (session('status'))
        <x-ui.alert type="success" class="mb-6">{{ session('status') }}</x-ui.alert>
    @endif

    <div class="mb-6 flex flex-col gap-3 sm:flex-row">
        <label class="flex-1">
            <span class="sr-only">Buscar por código o apodo</span>
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Código (p. ej. K7PM-29QX) o apodo"
                   class="block min-h-11 w-full rounded-control border border-stone-300 bg-white px-3 py-2 font-mono uppercase placeholder:font-sans placeholder:normal-case focus:border-brand-strong">
        </label>
        <label>
            <span class="sr-only">Estado</span>
            <select wire:model.live="status" class="block min-h-11 w-full rounded-control border border-stone-300 bg-white px-3 py-2 focus:border-brand-strong">
                @foreach (\App\Enums\ClaimStatus::cases() as $case)
                    <option value="{{ $case->value }}">{{ $case->label() }}</option>
                @endforeach
                <option value="todos">Todos</option>
            </select>
        </label>
    </div>

    <ul class="divide-y divide-line rounded-card bg-white shadow-card">
        @forelse ($claims as $claim)
            <li wire:key="claim-{{ $claim->id }}" class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <p class="font-mono text-lg font-bold tracking-widest text-primary">{{ $claim->code }}</p>
                    <p class="font-semibold">{{ $claim->prize->title }}</p>
                    <p class="text-sm text-muted">
                        {{ $claim->player_nickname }}@if ($claim->player) · {{ $claim->player->name }}@else · cuenta borrada @endif
                        · reclamado el {{ $claim->created_at->translatedFormat('j M Y, H:i') }}
                    </p>
                    @if ($followLinks && $claim->status === \App\Enums\ClaimStatus::Pending)
                        <p class="mt-1 text-xs {{ $claim->follow_declared_at ? 'text-success' : 'text-warning' }}">
                            {{ $claim->follow_declared_at ? 'Declaró que sigue las páginas' : 'Aún no confirma que sigue las páginas' }}
                        </p>
                        <label class="mt-2 inline-flex min-h-10 items-center gap-2 text-sm font-semibold">
                            <input type="checkbox" wire:model="verified.{{ $claim->id }}" class="size-5 rounded border-stone-300">
                            Verifiqué en su celular que sigue {{ implode(' e ', array_keys($followLinks)) }}
                        </label>
                        @error('verified.'.$claim->id)<p class="text-sm text-danger">{{ $message }}</p>@enderror
                    @endif
                    @if ($claim->status === \App\Enums\ClaimStatus::Delivered)
                        <p class="text-xs text-success">Entregado el {{ $claim->delivered_at->translatedFormat('j M Y, H:i') }}{{ $claim->deliveredBy ? ' por '.$claim->deliveredBy->name : '' }}</p>
                    @endif
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    @if ($claim->status === \App\Enums\ClaimStatus::Pending)
                        <x-ui.button wire:click="deliver({{ $claim->id }})" wire:confirm="¿Entregar «{{ $claim->prize->title }}» a {{ $claim->player_nickname }}?">Marcar entregado</x-ui.button>
                        <x-ui.button variant="ghost" class="text-danger!" wire:click="cancel({{ $claim->id }})" wire:confirm="¿Anular el canje {{ $claim->code }}? La unidad vuelve a estar disponible.">Anular</x-ui.button>
                    @else
                        <span @class(['rounded-full px-3 py-1 text-sm font-semibold', 'bg-green-100 text-success' => $claim->status === \App\Enums\ClaimStatus::Delivered, 'bg-stone-100 text-muted' => $claim->status === \App\Enums\ClaimStatus::Cancelled])>{{ $claim->status->label() }}</span>
                    @endif
                </div>
            </li>
        @empty
            <li class="p-6 text-center text-muted">No hay canjes {{ $search !== '' ? 'con esa búsqueda' : 'en este estado' }}.</li>
        @endforelse
    </ul>
    <div class="mt-4">{{ $claims->links() }}</div>
</div>
