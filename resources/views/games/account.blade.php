<x-games.shell title="Mi avance · Juegos bíblicos" :noindex="true">
    {{-- Resumen --}}
    <section class="relative isolate overflow-hidden rounded-card bg-linear-to-br from-primary-deep via-primary to-gradient-end p-6 text-white shadow-lg sm:p-8" aria-labelledby="resumen">
        <span class="absolute -top-16 -right-10 -z-10 size-56 rounded-full bg-brand/30 blur-3xl" aria-hidden="true"></span>
        <x-ui.mountains class="absolute inset-x-0 bottom-0 -z-10 h-16 text-white/5" />
        <p class="text-sm text-cream/80">Hola,</p>
        <h1 id="resumen" class="font-display text-3xl font-bold text-white sm:text-4xl">{{ $player->nickname }}</h1>
        <dl class="mt-5 grid grid-cols-3 gap-3 sm:max-w-lg">
            <div class="rounded-control bg-white/10 p-3"><dt class="text-xs text-cream/80">Estrellas</dt><dd class="font-display text-2xl font-bold">{{ $stats['stars'] }}</dd></div>
            <div class="rounded-control bg-white/10 p-3"><dt class="text-xs text-cream/80">Niveles</dt><dd class="font-display text-2xl font-bold">{{ $stats['levels'] }}</dd></div>
            <div class="rounded-control bg-white/10 p-3"><dt class="text-xs text-cream/80">Medallas</dt><dd class="font-display text-2xl font-bold">{{ collect($stats['games'])->whereNotNull('medal')->count() }}</dd></div>
        </dl>
        <dl class="mt-3 grid grid-cols-3 gap-3 sm:max-w-lg">
            <div class="rounded-control bg-accent-light/20 p-3 ring-1 ring-accent-light/40"><dt class="text-xs text-cream/80">Puntos esta semana</dt><dd class="font-display text-2xl font-bold">{{ $weeklyPoints }}</dd></div>
            <div class="rounded-control bg-accent-light/20 p-3 ring-1 ring-accent-light/40"><dt class="text-xs text-cream/80">Puesto semanal</dt><dd class="font-display text-2xl font-bold">{{ $weeklyPosition ? $weeklyPosition.'.º' : '—' }}</dd></div>
            <div class="rounded-control bg-accent-light/20 p-3 ring-1 ring-accent-light/40"><dt class="text-xs text-cream/80">Racha</dt><dd class="font-display text-2xl font-bold">{{ $streak }} <span class="text-sm font-normal">{{ $streak === 1 ? 'día' : 'días' }}</span></dd></div>
        </dl>
        <a href="{{ route('games.ranking') }}" class="mt-4 inline-flex items-center gap-1.5 text-sm font-semibold text-accent-light hover:underline">Ver el ranking semanal <x-ui.icon name="arrow-right" class="size-4" /></a>
    </section>

    <div class="mt-6">
        @include('games.partials.daily', ['from' => 'account'])
    </div>

    {{-- Compartir --}}
    <section class="mt-6 flex flex-col gap-3 rounded-card bg-white p-5 shadow-card ring-1 ring-black/5 sm:flex-row sm:items-center sm:justify-between" aria-labelledby="compartir">
        <div>
            <h2 id="compartir" class="font-display text-lg font-bold text-primary">Comparte tus logros</h2>
            @if ($player->is_public)
                <p class="text-sm text-muted">Tu tarjeta: <a href="{{ route('games.profile', $player->nickname) }}" class="font-semibold text-brand-strong underline">{{ route('games.profile', $player->nickname) }}</a></p>
            @else
                <p class="text-sm text-muted">Tu perfil está oculto. Actívalo abajo, en «Mi cuenta», para compartirlo y salir en el ranking.</p>
            @endif
        </div>
        @if ($player->is_public)
            @include('games.partials.share', ['player' => $player, 'stats' => $stats])
        @endif
    </section>

    {{-- Juegos --}}
    <section class="mt-10" aria-labelledby="mis-juegos">
        <h2 id="mis-juegos" class="text-2xl font-bold">Mis juegos</h2>
        <ul class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($games as $game)
                @php($mine = $stats['games'][$game['slug']])
                <li>
                    <a href="{{ route('games.levels', $game['slug']) }}" class="flex h-full items-center gap-4 rounded-card bg-white p-4 shadow-card ring-1 ring-black/5 transition hover:-translate-y-0.5">
                        <span class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-cream text-brand-strong"><x-ui.icon :name="$game['icon']" class="size-6" /></span>
                        <span class="min-w-0 flex-1">
                            <span class="block font-display font-bold text-primary">{{ $game['title'] }}</span>
                            <span class="mt-1 block h-1.5 overflow-hidden rounded-full bg-cream" aria-hidden="true"><span class="block h-full rounded-full bg-brand-strong" style="width: {{ round($mine['levels'] / \App\Services\GameCatalog::LEVELS * 100) }}%"></span></span>
                            <span class="mt-1 flex flex-wrap gap-x-3 text-xs text-muted tabular-nums">
                                <span>{{ $mine['levels'] }}/{{ \App\Services\GameCatalog::LEVELS }} niveles</span><span>{{ $mine['stars'] }} estrellas</span>
                                @if ($mine['medal'])<span class="font-bold text-brand-strong">Medalla de {{ $mine['medal'] }}</span>@endif
                            </span>
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>
    </section>

    {{-- Premios ganados (por meta o en el ranking semanal) --}}
    @if ($claims->isNotEmpty())
        <section class="mt-10" aria-labelledby="ganados">
            <h2 id="ganados" class="text-2xl font-bold">Mis premios ganados</h2>
            <p class="mt-1 text-muted">Muestra el código en la iglesia para recibir tu premio.</p>
            <ul class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($claims as $claim)
                    <li @class(['rounded-card p-4 ring-1', 'bg-green-50 ring-success/30' => $claim->status === \App\Enums\ClaimStatus::Delivered, 'bg-white shadow-card ring-black/5' => $claim->status !== \App\Enums\ClaimStatus::Delivered])>
                        <p class="font-display font-bold text-primary">{{ $claim->prize->title }}</p>
                        <p class="mt-2 font-mono text-2xl font-bold tracking-widest text-primary">{{ $claim->code }}</p>
                        <p class="text-sm font-semibold">{{ $claim->status->label() }}{{ $claim->delivered_at ? ' el '.$claim->delivered_at->translatedFormat('j M Y') : '' }}</p>
                        @if ($followLinks && $claim->status === \App\Enums\ClaimStatus::Pending)
                            @if ($claim->follow_declared_at)
                                <p class="mt-2 text-xs text-success">Confirmaste que sigues las páginas. Muéstralo en tu celular al recogerlo.</p>
                            @else
                                <form method="POST" action="{{ route('games.follow', $claim) }}" class="mt-3 space-y-2">
                                    @csrf
                                    @include('games.partials.follow', ['name' => 'follow', 'id' => 'follow-claim-'.$claim->id, 'required' => true])
                                    <x-ui.button type="submit" variant="secondary" class="w-full text-sm">Confirmar</x-ui.button>
                                </form>
                            @endif
                        @endif
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    {{-- Premios --}}
    <section id="premios" class="mt-10 scroll-mt-28" aria-labelledby="premios-titulo">
        <h2 id="premios-titulo" class="text-2xl font-bold">Premios</h2>
        @if ($prizes->isEmpty())
            <x-ui.empty-state class="mt-4">Pronto anunciaremos los premios. ¡Sigue sumando estrellas!</x-ui.empty-state>
        @else
            <p class="mt-1 text-muted">Cuando alcances la meta, reclama el premio y muestra tu código en la iglesia para recibirlo.</p>
            <ul class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($prizes as $item)
                    <li class="flex flex-col gap-3">
                        @include('games.partials.prize', ['prize' => $item['prize'], 'progress' => $item['progress']])
                        @if ($item['claim'])
                            <div @class(['rounded-control p-4 text-center ring-1', 'bg-green-50 ring-success/30' => $item['claim']->status === \App\Enums\ClaimStatus::Delivered, 'bg-accent-light/25 ring-accent-light' => $item['claim']->status !== \App\Enums\ClaimStatus::Delivered])>
                                <p class="text-xs font-semibold text-muted uppercase">Tu código</p>
                                <p class="font-mono text-2xl font-bold tracking-widest text-primary">{{ $item['claim']->code }}</p>
                                <p class="mt-1 text-sm font-semibold">{{ $item['claim']->status->label() }}{{ $item['claim']->delivered_at ? ' el '.$item['claim']->delivered_at->translatedFormat('j M Y') : '' }}</p>
                            </div>
                        @elseif ($item['reached'] && $item['remaining'] > 0)
                            <form method="POST" action="{{ route('games.claim', $item['prize']) }}" class="space-y-3 rounded-control bg-cream-light p-4 ring-1 ring-line">
                                @csrf
                                @if ($followLinks)
                                    @include('games.partials.follow', ['name' => 'follow', 'id' => 'follow-'.$item['prize']->id])
                                @endif
                                <x-ui.button type="submit" class="w-full"><x-ui.icon name="trophy" class="size-5" /> Reclamar premio</x-ui.button>
                            </form>
                        @elseif ($item['reached'])
                            <p class="rounded-control bg-stone-100 p-3 text-center text-sm text-muted">Alcanzaste la meta, pero este premio ya se agotó.</p>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    {{-- Cuenta --}}
    <section id="cuenta" class="mt-10 grid scroll-mt-28 gap-6 lg:grid-cols-2" aria-labelledby="cuenta-titulo">
        <h2 id="cuenta-titulo" class="sr-only">Mi cuenta</h2>
        <form method="POST" action="{{ route('games.account.update') }}" class="space-y-5 rounded-card bg-white p-6 shadow-card ring-1 ring-black/5" novalidate>
            @csrf @method('PUT')
            <h3 class="font-display text-lg font-bold text-primary">Mi cuenta</h3>
            <x-games.field name="nickname" label="Apodo" :value="$player->nickname" required maxlength="20" />
            <x-games.field name="name" label="Nombre (privado)" :value="$player->name" required maxlength="100" />
            <label class="flex items-start gap-3 text-sm">
                <input type="hidden" name="is_public" value="0">
                <input type="checkbox" name="is_public" value="1" @checked(old('is_public', $player->is_public)) class="mt-0.5 size-5 shrink-0 rounded border-stone-300">
                <span><strong>Perfil público:</strong> mi apodo y mis logros aparecen en el ranking y en mi tarjeta para compartir.</span>
            </label>
            <x-ui.button type="submit">Guardar</x-ui.button>
        </form>

        <form method="POST" action="{{ route('games.account.destroy') }}" class="space-y-4 rounded-card bg-white p-6 shadow-card ring-1 ring-danger/20"
              onsubmit="return confirm('¿Borrar tu cuenta y todo tu avance? No se puede deshacer.')" novalidate>
            @csrf @method('DELETE')
            <h3 class="font-display text-lg font-bold text-danger">Borrar mi cuenta</h3>
            <p class="text-sm text-muted">Se borran tus datos, tus estrellas y tus niveles. Los premios ya entregados quedan registrados solo con tu apodo.</p>
            <x-games.field name="password" type="password" label="Escribe tu contraseña para confirmar" autocomplete="current-password" required />
            <x-ui.button type="submit" variant="secondary" class="border-danger! text-danger!">Borrar mi cuenta</x-ui.button>
        </form>
    </section>
</x-games.shell>
