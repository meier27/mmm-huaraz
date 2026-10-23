{{--
    Reto del día: la misma pregunta para todos, un intento. Requiere $daily
    (pregunta o null), $dailyAnswer (respuesta de hoy o null) y $from.
--}}
@if ($daily)
    <section id="reto" class="scroll-mt-28 overflow-hidden rounded-card bg-white shadow-card ring-1 ring-black/5" aria-labelledby="reto-titulo">
        <div class="flex flex-wrap items-center justify-between gap-2 bg-linear-to-r from-primary-deep to-gradient-end px-5 py-3 text-white">
            <h2 id="reto-titulo" class="flex items-center gap-2 font-display text-lg font-bold text-white"><x-ui.icon name="calendar" class="size-5 text-accent-light" /> Reto del día</h2>
            <span class="text-sm text-cream/80">+{{ \App\Services\GameRewards::DAILY_POINTS }} puntos · un intento · {{ today()->translatedFormat('l j \d\e F') }}</span>
        </div>
        <div class="p-5">
            <p class="font-display text-lg leading-snug font-bold text-primary sm:text-xl">{{ $daily['prompt'] }}</p>

            @auth('player')
                @if ($dailyAnswer)
                    <div @class(['mt-4 rounded-control p-4 text-sm', 'bg-green-50 text-success' => $dailyAnswer->correct, 'bg-red-50 text-danger' => ! $dailyAnswer->correct])>
                        <p class="font-semibold">{{ $dailyAnswer->correct ? '¡Acertaste el reto de hoy!' : 'Hoy no acertaste.' }} La respuesta es «{{ $daily['answer'] }}».</p>
                        <a href="{{ route('bible.search', ['q' => $daily['reference']]) }}" class="mt-1 inline-flex items-center gap-1.5 font-semibold text-brand-strong underline" target="_blank" rel="noopener">
                            <x-ui.icon name="book" class="size-4" /> Leer {{ $daily['reference'] }}<span class="sr-only"> (pestaña nueva)</span>
                        </a>
                        <p class="mt-1 text-muted">Vuelve mañana por un reto nuevo.</p>
                    </div>
                @else
                    <form method="POST" action="{{ route('games.daily') }}" class="mt-4 grid gap-2 sm:grid-cols-2">
                        @csrf
                        <input type="hidden" name="from" value="{{ $from }}">
                        @foreach ($daily['choices'] as $choice)
                            <button type="submit" name="choice" value="{{ $choice }}"
                                    class="flex min-h-12 items-center gap-3 rounded-control border-2 border-line bg-white px-4 py-2 text-left font-semibold text-primary transition hover:border-brand-strong hover:bg-cream-light">
                                <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-cream text-sm" aria-hidden="true">{{ $loop->iteration }}</span>
                                {{ $choice }}
                            </button>
                        @endforeach
                    </form>
                    <p class="mt-2 text-xs text-muted">Piensa bien: solo tienes un intento.</p>
                @endif
            @else
                <p class="mt-3 text-sm text-muted">
                    <a href="{{ route('player.login') }}" class="font-semibold text-brand-strong underline">Ingresa</a> o
                    <a href="{{ route('player.register') }}" class="font-semibold text-brand-strong underline">crea tu cuenta</a> para responder y sumar puntos para el ranking semanal.
                </p>
            @endauth
        </div>
    </section>
@endif
