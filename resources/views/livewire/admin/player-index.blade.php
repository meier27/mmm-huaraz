<div>
    <x-admin.page-header title="Jugadores" description="Cuentas de los juegos bíblicos (no tienen acceso al panel). Bloquea a quien use un apodo inapropiado: no podrá ingresar ni saldrá en el ranking." />

    <label class="mb-6 block">
        <span class="sr-only">Buscar jugador</span>
        <input type="search" wire:model.live.debounce.300ms="search" placeholder="Apodo, nombre o correo"
               class="block min-h-11 w-full rounded-control border border-stone-300 bg-white px-3 py-2 focus:border-brand-strong">
    </label>

    <ul class="divide-y divide-line rounded-card bg-white shadow-card">
        @forelse ($players as $player)
            <li wire:key="player-{{ $player->id }}" class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <p class="font-semibold">{{ $player->nickname }} <span class="font-normal text-muted">· {{ $player->name }}</span></p>
                    <p class="text-sm text-muted">{{ $player->getAttributes()['email'] }} · desde {{ $player->created_at->translatedFormat('j M Y') }}</p>
                    <p class="text-xs text-brand-strong">{{ (int) $player->stars }} estrellas · {{ $player->levels }} niveles · perfil {{ $player->is_public ? 'público' : 'oculto' }}</p>
                </div>
                <button type="button" wire:click="toggleActive({{ $player->id }})"
                        wire:confirm="{{ $player->is_active ? '¿Bloquear a '.$player->nickname.'?' : '¿Desbloquear a '.$player->nickname.'?' }}"
                        @class(['inline-flex min-h-9 shrink-0 items-center rounded-full px-3 text-sm font-semibold', 'bg-green-100 text-success' => $player->is_active, 'bg-red-100 text-danger' => ! $player->is_active])>
                    {{ $player->is_active ? 'Activo' : 'Bloqueado' }}
                </button>
            </li>
        @empty
            <li class="p-6 text-center text-muted">{{ $search !== '' ? 'Ningún jugador coincide con la búsqueda.' : 'Aún no hay jugadores registrados.' }}</li>
        @endforelse
    </ul>
    <div class="mt-4">{{ $players->links() }}</div>
</div>
