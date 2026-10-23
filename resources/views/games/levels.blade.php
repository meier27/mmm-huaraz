<x-games.shell :title="$game['title'].' · Niveles'" :description="$game['text']" :heading="$game['title']" :icon="$game['icon']"
               :back="['url' => route('games.index'), 'label' => 'Todos los juegos']">
    <x-slot:subtitle>{{ $game['text'] }}</x-slot:subtitle>

    @unless ($game['ready'])
        <x-ui.empty-state class="mt-8">Este juego estará disponible cuando se cargue su contenido en el panel.</x-ui.empty-state>
    @else
        {{-- Resumen del jugador en este juego --}}
        <div class="mt-6 flex flex-wrap items-center gap-3">
            @if ($stats)
                <span class="inline-flex min-h-10 items-center gap-2 rounded-full bg-white px-4 text-sm font-semibold text-primary ring-1 ring-line">
                    <x-ui.icon name="check" class="size-4 text-success" /> {{ $stats['levels'] }} de {{ \App\Services\GameCatalog::LEVELS }} niveles
                </span>
                <span class="inline-flex min-h-10 items-center gap-2 rounded-full bg-white px-4 text-sm font-semibold text-primary ring-1 ring-line">
                    <x-ui.icon name="star" class="size-4 text-brand-strong" /> {{ $stats['stars'] }} estrellas
                </span>
                @if ($stats['medal'])
                    @include('games.partials.medal', ['medal' => $stats['medal']])
                @endif
            @else
                <p class="rounded-control bg-cream px-4 py-3 text-sm text-primary ring-1 ring-line">
                    Sin cuenta puedes jugar los niveles 1 a {{ \App\Services\GameCatalog::GUEST_LEVELS }} (no se guardan).
                    <a href="{{ route('player.register') }}" class="font-semibold text-brand-strong underline">Crea tu cuenta</a> o
                    <a href="{{ route('player.login') }}" class="font-semibold text-brand-strong underline">ingresa</a> para jugar los {{ \App\Services\GameCatalog::LEVELS }} y ganar premios.
                </p>
            @endif
        </div>

        {{-- Mapa de niveles --}}
        <ol class="mt-6 grid grid-cols-3 gap-3 sm:grid-cols-5 lg:grid-cols-6" aria-label="Niveles">
            @foreach ($levels as $level)
                <li>
                    @if ($level['open'])
                        <a href="{{ route('games.play', [$game['slug'], $level['number']]) }}"
                           @class(['group flex h-full flex-col items-center rounded-card p-3 text-center ring-1 transition hover:-translate-y-0.5 hover:shadow-card sm:p-4',
                                   'bg-white ring-line' => $level['stars'] > 0,
                                   'bg-linear-to-br from-accent-light/40 to-white ring-brand-strong shadow-card' => $level['stars'] === 0])
                           aria-label="Nivel {{ $level['number'] }}: {{ $level['text'] }}{{ $level['stars'] ? ', '.$level['stars'].' de 3 estrellas' : ', por jugar' }}">
                            <span class="font-display text-2xl font-bold text-primary sm:text-3xl">{{ $level['number'] }}</span>
                            <span class="mt-1 flex gap-0.5" aria-hidden="true">
                                @foreach (range(1, 3) as $s)
                                    <x-ui.icon name="star" @class(['size-4', 'fill-accent-light text-brand-strong' => $s <= $level['stars'], 'text-sand-dark' => $s > $level['stars']]) />
                                @endforeach
                            </span>
                            <span class="mt-1.5 line-clamp-2 text-[0.7rem] leading-tight text-muted sm:text-xs">{{ $level['text'] }}</span>
                        </a>
                    @else
                        <div class="flex h-full flex-col items-center rounded-card bg-stone-100 p-3 text-center text-stone-400 sm:p-4" aria-label="Nivel {{ $level['number'] }}: bloqueado">
                            <span class="font-display text-2xl font-bold sm:text-3xl">{{ $level['number'] }}</span>
                            <x-ui.icon name="lock" class="mt-1 size-4" />
                            <span class="mt-1.5 line-clamp-2 text-[0.7rem] leading-tight sm:text-xs">{{ $player ? 'Supera el anterior' : 'Con cuenta' }}</span>
                        </div>
                    @endif
                </li>
            @endforeach
        </ol>
    @endunless

    <section class="mt-16" aria-labelledby="mas-juegos">
        <h2 id="mas-juegos" class="text-2xl font-bold">Más juegos</h2>
        <ul class="mt-5 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-5">
            @foreach ($others as $other)
                <li>@include('games.partials.card', ['game' => $other, 'compact' => true])</li>
            @endforeach
        </ul>
    </section>
</x-games.shell>
