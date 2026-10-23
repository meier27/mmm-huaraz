<x-games.shell :title="$player->nickname.' · Logros'" :description="$player->nickname.' lleva '.$stats['stars'].' estrellas y '.$stats['levels'].' niveles en los Juegos bíblicos del MMM Huaraz.'">
    <article class="mx-auto max-w-3xl">
        {{-- Tarjeta de logros: solo apodo y avance --}}
        <div class="relative isolate overflow-hidden rounded-card bg-linear-to-br from-primary-deep via-primary to-gradient-end p-6 text-center text-white shadow-[0_30px_60px_-30px_rgb(57_49_45/0.8)] sm:p-10">
            <span class="absolute -top-16 -right-10 -z-10 size-56 rounded-full bg-brand/30 blur-3xl" aria-hidden="true"></span>
            <x-ui.mountains class="absolute inset-x-0 bottom-0 -z-10 h-20 text-white/5" />
            <span class="mx-auto flex size-16 items-center justify-center rounded-full bg-accent-light text-primary-deep shadow-lg"><x-ui.icon name="trophy" class="size-8" /></span>
            <p class="mt-4 text-sm tracking-wide text-cream/80 uppercase">Juegos bíblicos · MMM Huaraz</p>
            <h1 class="font-display text-3xl font-bold text-white sm:text-4xl">{{ $player->nickname }}</h1>
            <dl class="mx-auto mt-6 grid max-w-md grid-cols-3 gap-3">
                <div class="rounded-control bg-white/10 p-3"><dt class="text-xs text-cream/80">Estrellas</dt><dd class="font-display text-2xl font-bold">{{ $stats['stars'] }}</dd></div>
                <div class="rounded-control bg-white/10 p-3"><dt class="text-xs text-cream/80">Niveles</dt><dd class="font-display text-2xl font-bold">{{ $stats['levels'] }}</dd></div>
                <div class="rounded-control bg-white/10 p-3"><dt class="text-xs text-cream/80">Medallas</dt><dd class="font-display text-2xl font-bold">{{ collect($stats['games'])->whereNotNull('medal')->count() }}</dd></div>
            </dl>
            <p class="mt-4 text-xs text-cream/70">Juega desde {{ $player->created_at->translatedFormat('F \d\e Y') }}</p>
        </div>

        <ul class="mt-6 grid gap-3 sm:grid-cols-2">
            @foreach ($games as $game)
                @php($mine = $stats['games'][$game['slug']])
                <li class="flex items-center gap-4 rounded-card bg-white p-4 shadow-card ring-1 ring-black/5">
                    <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-cream text-brand-strong"><x-ui.icon :name="$game['icon']" class="size-5" /></span>
                    <span class="min-w-0 flex-1">
                        <span class="block font-display font-bold text-primary">{{ $game['title'] }}</span>
                        <span class="mt-1 block h-1.5 overflow-hidden rounded-full bg-cream" aria-hidden="true"><span class="block h-full rounded-full bg-brand-strong" style="width: {{ round($mine['levels'] / \App\Services\GameCatalog::LEVELS * 100) }}%"></span></span>
                        <span class="mt-1 block text-xs text-muted tabular-nums">{{ $mine['levels'] }}/{{ \App\Services\GameCatalog::LEVELS }} niveles · {{ $mine['stars'] }} estrellas</span>
                    </span>
                    @if ($mine['medal'])
                        <span class="sr-only">Medalla de {{ $mine['medal'] }}</span>
                        <x-ui.icon name="trophy" @class(['size-7 shrink-0', 'text-amber-700' => $mine['medal'] === 'bronce', 'text-stone-400' => $mine['medal'] === 'plata', 'text-yellow-500' => $mine['medal'] === 'oro']) />
                    @endif
                </li>
            @endforeach
        </ul>

        <div class="mt-8 flex flex-col items-center gap-4 text-center">
            @if (auth('player')->id() === $player->id)
                <p class="font-semibold text-primary">Comparte tu tarjeta:</p>
                @include('games.partials.share', ['player' => $player, 'stats' => $stats])
            @else
                <p class="text-muted">¿Te animas a superarlo?</p>
                <x-ui.button href="{{ route('games.index') }}"><x-ui.icon name="puzzle" class="size-5" /> Jugar ahora</x-ui.button>
            @endif
        </div>
    </article>
</x-games.shell>
