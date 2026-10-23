<div>
    <x-admin.page-header title="Ranking semanal"
        description="Semanas de lunes a domingo (hora de Lima). Puntos: 10 por cada estrella nueva, {{ \App\Services\GameRewards::DAILY_POINTS }} por acertar el reto del día y bonos de racha. Al cerrar una semana terminada, los {{ \App\Services\GameRewards::WINNERS }} primeros reciben el premio que elijas (quien ganó en las últimas {{ \App\Services\GameRewards::WIN_COOLDOWN_WEEKS }} semanas cede su lugar al siguiente)." />

    @if (session('status'))
        <x-ui.alert type="success" class="mb-6">{{ session('status') }}</x-ui.alert>
    @endif
    @error('prizes.*')<x-ui.alert type="error" class="mb-6">{{ $message }}</x-ui.alert>@enderror

    <label class="mb-6 block max-w-sm">
        <span class="block text-sm font-medium">Semana</span>
        <select wire:model.live="week" class="mt-1 block min-h-11 w-full rounded-control border border-stone-300 bg-white px-3 py-2 focus:border-brand-strong">
            @foreach ($weeks as $w)
                <option value="{{ $w->toDateString() }}">{{ $w->translatedFormat('j M') }} – {{ $w->copy()->endOfWeek()->translatedFormat('j M Y') }}{{ $loop->first ? ' (en curso)' : '' }}</option>
            @endforeach
        </select>
    </label>

    <div class="grid gap-6 lg:grid-cols-2 lg:items-start">
        {{-- Tabla de la semana --}}
        <section class="rounded-card bg-white p-5 shadow-card" aria-labelledby="tabla">
            <h2 id="tabla" class="text-lg font-bold">Posiciones · {{ $monday->translatedFormat('j M') }} al {{ $sunday->translatedFormat('j M') }}</h2>
            @if ($ranking->isEmpty())
                <p class="mt-3 text-sm text-muted">Nadie sumó puntos esta semana.</p>
            @else
                <ol class="mt-3 divide-y divide-line">
                    @foreach ($ranking as $row)
                        <li class="flex items-center justify-between gap-3 py-2 text-sm">
                            <span><span class="inline-block w-6 font-bold text-brand-strong">{{ $loop->iteration }}</span> {{ $row->nickname }}</span>
                            <span class="font-semibold tabular-nums">{{ (int) $row->points }} pts</span>
                        </li>
                    @endforeach
                </ol>
            @endif
        </section>

        {{-- Cierre --}}
        <section class="rounded-card bg-white p-5 shadow-card" aria-labelledby="cierre">
            <h2 id="cierre" class="text-lg font-bold">Ganadores</h2>

            @if ($winners->isNotEmpty())
                <ul class="mt-3 space-y-2">
                    @foreach ($winners as $winner)
                        <li class="rounded-control bg-cream-light p-3 text-sm ring-1 ring-line">
                            <p class="font-semibold">{{ $winner->position }}.º {{ $winner->player_nickname }} · {{ $winner->points }} pts</p>
                            @if ($winner->claim)
                                <p class="text-muted">{{ $winner->claim->prize->title }} · código <span class="font-mono font-bold">{{ $winner->claim->code }}</span> · {{ $winner->claim->status->label() }}</p>
                            @else
                                <p class="text-muted">Sin premio</p>
                            @endif
                        </li>
                    @endforeach
                </ul>
                <p class="mt-3 text-xs text-muted">Semana cerrada. Entrega los premios en Panel › Canjes.</p>
            @elseif ($isCurrent || ! $ended)
                <p class="mt-3 text-sm text-muted">La semana sigue en curso: se podrá cerrar desde el lunes.</p>
            @elseif ($candidates->isEmpty())
                <p class="mt-3 text-sm text-muted">No hay jugadores que puedan ganar esta semana.</p>
            @else
                @if ($weeklyPrizes->isEmpty())
                    <x-ui.alert type="warning" class="mt-3">Crea primero un premio con la meta «Premio del ranking semanal» en Panel › Premios.</x-ui.alert>
                @endif
                <form wire:submit="close" class="mt-3 space-y-3">
                    @foreach ($candidates as $i => $row)
                        <div class="rounded-control bg-cream-light p-3 ring-1 ring-line">
                            <p class="text-sm font-semibold">{{ $i + 1 }}.º {{ $row->nickname }} · {{ (int) $row->points }} pts</p>
                            <label class="mt-2 block text-sm">
                                <span class="sr-only">Premio para el puesto {{ $i + 1 }}</span>
                                <select wire:model="prizes.{{ $i + 1 }}" class="block min-h-11 w-full rounded-control border border-stone-300 bg-white px-3 py-2">
                                    <option value="">Sin premio</option>
                                    @foreach ($weeklyPrizes as $prize)
                                        <option value="{{ $prize->id }}" @disabled($prize->remaining() === 0)>{{ $prize->title }} ({{ $prize->remaining() }} disponibles)</option>
                                    @endforeach
                                </select>
                            </label>
                        </div>
                    @endforeach
                    <x-ui.button type="submit" wire:confirm="¿Cerrar la semana y generar los códigos de los ganadores? No se puede deshacer.">Cerrar semana y generar códigos</x-ui.button>
                </form>
            @endif
        </section>
    </div>
</div>
