<x-games.shell title="Juegos bíblicos" description="Aprende la Palabra jugando: crucigrama, sopa de letras, trivia, completa el versículo, ordena los libros y rompecabezas. 30 niveles por juego y premios.">
    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem] lg:items-end">
        <div>
            <x-ui.section-label>Aprende jugando</x-ui.section-label>
            <h1 class="mt-3 text-3xl font-extrabold sm:text-4xl">Juegos bíblicos</h1>
            <p class="mt-3 max-w-2xl text-lg text-muted">
                Seis juegos con {{ \App\Services\GameCatalog::LEVELS }} niveles cada uno. Supera niveles, gana estrellas y medallas, compártelas y llévate premios.
                Cada respuesta lleva su cita: ábrela para leer el pasaje completo.
            </p>
        </div>
        @if ($stats)
            <div class="rounded-card bg-linear-to-br from-primary-deep via-primary to-gradient-end p-5 text-white shadow-lg">
                <p class="text-sm text-cream/80">Tu avance</p>
                <p class="mt-1 flex items-baseline gap-4">
                    <span><span class="font-display text-3xl font-bold">{{ $stats['stars'] }}</span> <span class="text-sm text-cream/80">estrellas</span></span>
                    <span><span class="font-display text-3xl font-bold">{{ $stats['levels'] }}</span> <span class="text-sm text-cream/80">niveles</span></span>
                </p>
                <a href="{{ route('games.account') }}" class="mt-3 inline-flex items-center gap-1.5 text-sm font-semibold text-accent-light hover:underline">Ver mi avance y premios <x-ui.icon name="arrow-right" class="size-4" /></a>
            </div>
        @else
            <div class="rounded-card bg-cream p-5 ring-1 ring-line">
                <p class="font-semibold text-primary">Prueba los primeros {{ \App\Services\GameCatalog::GUEST_LEVELS }} niveles sin cuenta.</p>
                <p class="mt-1 text-sm text-muted">Para guardar tu avance, desbloquear los {{ \App\Services\GameCatalog::LEVELS }} niveles y ganar premios, crea tu cuenta (desde los 14 años).</p>
                <div class="mt-3 flex flex-wrap gap-2">
                    <x-ui.button href="{{ route('player.register') }}" class="min-h-10! px-4! text-sm">Crear cuenta</x-ui.button>
                    <x-ui.button href="{{ route('player.login') }}" variant="secondary" class="min-h-10! px-4! text-sm">Ingresar</x-ui.button>
                </div>
            </div>
        @endif
    </div>

    {{-- Reto del día y ranking de la semana --}}
    <div class="mt-10 grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem] lg:items-start">
        @include('games.partials.daily', ['from' => 'index'])

        <section class="rounded-card bg-white p-5 shadow-card ring-1 ring-black/5" aria-labelledby="semana">
            <h2 id="semana" class="flex items-center gap-2 font-display text-lg font-bold text-primary"><x-ui.icon name="trophy" class="size-5 text-brand-strong" /> Esta semana</h2>
            @if ($stats)
                <div class="mt-3 grid grid-cols-2 gap-2 text-center">
                    <div class="rounded-control bg-cream p-3"><p class="font-display text-2xl font-bold text-primary">{{ $weeklyPoints }}</p><p class="text-xs text-muted">tus puntos</p></div>
                    <div class="rounded-control bg-cream p-3"><p class="font-display text-2xl font-bold text-primary">{{ $streak }}</p><p class="text-xs text-muted">{{ $streak === 1 ? 'día de racha' : 'días de racha' }}</p></div>
                </div>
            @endif
            @if ($weeklyTop->isNotEmpty())
                <ol class="mt-3 space-y-1.5 text-sm">
                    @foreach ($weeklyTop as $row)
                        <li class="flex items-center justify-between gap-2">
                            <span class="truncate"><span class="font-bold text-brand-strong">{{ $loop->iteration }}.º</span> {{ $row->nickname }}</span>
                            <span class="font-semibold tabular-nums">{{ (int) $row->points }} pts</span>
                        </li>
                    @endforeach
                </ol>
            @else
                <p class="mt-3 text-sm text-muted">Aún nadie suma puntos esta semana.</p>
            @endif
            <a href="{{ route('games.ranking') }}" class="mt-4 inline-flex items-center gap-1.5 text-sm font-semibold text-brand-strong hover:underline">Ver el ranking semanal <x-ui.icon name="arrow-right" class="size-4" /></a>
        </section>
    </div>

    <ul class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3 lg:gap-6">
        @foreach ($games as $game)
            <li>@include('games.partials.card', ['game' => $game, 'done' => $stats['games'][$game['slug']]['levels'] ?? null])</li>
        @endforeach
    </ul>

    {{-- Cómo funciona --}}
    <section class="mt-14 grid gap-4 sm:grid-cols-3" aria-labelledby="como-funciona">
        <h2 id="como-funciona" class="sr-only">Cómo funciona</h2>
        @foreach ([
            ['star', 'Gana estrellas', 'Hasta 3 por nivel: menos errores y menos ayudas, más estrellas. Cada nivel superado abre el siguiente.'],
            ['trophy', 'Ranking semanal', 'Suma puntos con niveles nuevos, el reto del día y tu racha. Los 3 primeros de cada semana ganan premio.'],
            ['heart', 'Premios', 'Alcanza la meta de un premio, reclámalo y muestra tu código en la iglesia para recibirlo.'],
        ] as [$icon, $title, $text])
            <div class="rounded-card bg-white p-5 shadow-card ring-1 ring-black/5">
                <span class="flex size-11 items-center justify-center rounded-xl bg-cream text-brand-strong"><x-ui.icon :name="$icon" class="size-5" /></span>
                <h3 class="mt-3 font-display text-lg font-bold text-primary">{{ $title }}</h3>
                <p class="mt-1 text-sm leading-relaxed text-muted">{{ $text }}</p>
            </div>
        @endforeach
    </section>

    @if ($prizes->isNotEmpty())
        <section class="mt-14" aria-labelledby="premios-titulo">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <x-ui.section-label>Premios</x-ui.section-label>
                    <h2 id="premios-titulo" class="mt-2 text-2xl font-bold sm:text-3xl">Lo que puedes ganar</h2>
                </div>
                @auth('player')
                    <a href="{{ route('games.account') }}#premios" class="text-sm font-semibold text-brand-strong hover:underline">Ver mi progreso hacia cada premio</a>
                @endauth
            </div>
            <ul class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($prizes as $prize)
                    <li>@include('games.partials.prize', ['prize' => $prize])</li>
                @endforeach
            </ul>
        </section>
    @endif
</x-games.shell>
