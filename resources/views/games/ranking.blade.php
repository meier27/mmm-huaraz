<x-games.shell title="Ranking · Juegos bíblicos" description="Ranking semanal y de siempre de los Juegos bíblicos del MMM Huaraz." heading="Ranking" icon="trophy">
    <x-slot:subtitle>
        @if ($weekly)
            Puntos de esta semana · cierra el {{ $weekEnds->translatedFormat('l j \d\e F') }} a las 23:59. Los {{ \App\Services\GameRewards::WINNERS }} primeros ganan premio.
        @else
            Estrellas de todos los niveles superados. Solo aparecen los perfiles públicos.
        @endif
    </x-slot:subtitle>

    @php
        $tab = 'inline-flex min-h-10 items-center rounded-full px-4 text-sm font-semibold';
        $on = 'bg-primary text-white';
        $off = 'bg-white text-primary ring-1 ring-line hover:bg-cream';
    @endphp
    <nav class="mt-6 flex flex-wrap gap-2" aria-label="Tipo de ranking">
        <a href="{{ route('games.ranking') }}" class="{{ $tab }} {{ $weekly ? $on : $off }}" @if ($weekly) aria-current="page" @endif>Esta semana</a>
        <a href="{{ route('games.ranking', ['periodo' => 'siempre']) }}" class="{{ $tab }} {{ ! $weekly && ! $game ? $on : $off }}" @if (! $weekly && ! $game) aria-current="page" @endif>De siempre</a>
        @foreach ($games as $item)
            <a href="{{ route('games.ranking', ['juego' => $item['slug']]) }}" class="{{ $tab }} {{ $game === $item['slug'] ? $on : $off }}" @if ($game === $item['slug']) aria-current="page" @endif>{{ $item['title'] }}</a>
        @endforeach
    </nav>

    <div class="mt-6 grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem] lg:items-start">
        <div>
            @if ($rows->isEmpty())
                <x-ui.empty-state>{{ $weekly ? 'Aún nadie suma puntos esta semana. ¡Sé el primero!' : 'Aún no hay jugadores en el ranking. ¡Sé el primero!' }}</x-ui.empty-state>
            @else
                <ol class="divide-y divide-line overflow-hidden rounded-card bg-white shadow-card ring-1 ring-black/5">
                    @foreach ($rows as $row)
                        @php($mine = auth('player')->user()?->nickname === $row->nickname)
                        <li @class(['flex items-center gap-4 px-4 py-3 sm:px-6', 'bg-accent-light/20' => $mine])>
                            <span @class(['flex size-10 shrink-0 items-center justify-center rounded-full font-display text-lg font-bold',
                                          'bg-yellow-400 text-amber-950' => $loop->iteration === 1, 'bg-stone-300 text-stone-800' => $loop->iteration === 2,
                                          'bg-amber-700 text-white' => $loop->iteration === 3, 'bg-cream text-primary' => $loop->iteration > 3])>{{ $loop->iteration }}</span>
                            <a href="{{ route('games.profile', $row->nickname) }}" class="min-w-0 flex-1 truncate font-semibold text-primary hover:underline">
                                {{ $row->nickname }}@if ($mine) <span class="text-sm font-normal text-muted">(tú)</span>@endif
                            </a>
                            @if ($weekly)
                                <span class="font-bold text-primary tabular-nums">{{ (int) $row->points }} <span class="text-xs font-normal text-muted">pts</span></span>
                            @else
                                <span class="text-right text-sm tabular-nums">
                                    <span class="flex items-center justify-end gap-1 font-bold text-primary"><x-ui.icon name="star" class="size-4 fill-accent-light text-brand-strong" /> {{ $row->stars }}</span>
                                    <span class="text-xs text-muted">{{ $row->levels }} niveles</span>
                                </span>
                            @endif
                        </li>
                    @endforeach
                </ol>
            @endif

            @if ($weekly)
                <div class="mt-4 rounded-card bg-cream p-5 text-sm ring-1 ring-line">
                    <h2 class="font-display text-base font-bold text-primary">Cómo se suman puntos</h2>
                    <ul class="mt-2 space-y-1 text-ink/80">
                        <li>• {{ \App\Services\GameRewards::POINTS_PER_STAR }} puntos por cada estrella nueva en un nivel (repetir lo ya ganado no suma).</li>
                        <li>• {{ \App\Services\GameRewards::DAILY_POINTS }} puntos por acertar el reto del día.</li>
                        <li>• Bono por racha de días seguidos: {{ collect(\App\Services\GameRewards::STREAK_BONUS)->map(fn ($p, $d) => "{$d} días +{$p}")->implode(' · ') }}.</li>
                        <li>• Para recibir el premio hay que seguir las páginas del Movimiento en Facebook e Instagram. Quien gana una semana puede volver a ganar después de {{ \App\Services\GameRewards::WIN_COOLDOWN_WEEKS }} semanas.</li>
                    </ul>
                </div>
            @endif
        </div>

        {{-- Ganadores de las últimas semanas --}}
        <aside class="rounded-card bg-white p-5 shadow-card ring-1 ring-black/5" aria-labelledby="ganadores">
            <h2 id="ganadores" class="flex items-center gap-2 font-display text-lg font-bold text-primary"><x-ui.icon name="trophy" class="size-5 text-brand-strong" /> Ganadores de la semana</h2>
            @forelse ($winners as $week => $list)
                <div class="mt-4">
                    <p class="text-xs font-semibold tracking-wide text-muted uppercase">Semana del {{ \Illuminate\Support\Carbon::parse($week)->translatedFormat('j \d\e F') }}</p>
                    <ol class="mt-1 space-y-1 text-sm">
                        @foreach ($list as $winner)
                            <li class="flex justify-between gap-2"><span><span class="font-bold text-brand-strong">{{ $winner->position }}.º</span> {{ $winner->player_nickname }}</span><span class="text-muted tabular-nums">{{ $winner->points }} pts</span></li>
                        @endforeach
                    </ol>
                </div>
            @empty
                <p class="mt-3 text-sm text-muted">Los ganadores aparecerán aquí al cerrar cada semana.</p>
            @endforelse
        </aside>
    </div>
</x-games.shell>
