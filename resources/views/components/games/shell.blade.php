@props(['title', 'description' => null, 'heading' => null, 'icon' => null, 'back' => null, 'noindex' => false])

{{--
    Marco de las páginas de juegos: barra propia (Juegos · Ranking · Mi avance
    o Ingresar), el estado del jugador y los mensajes.
--}}
@php
    $me = auth('player')->user();
    $myStars = $me?->stats()['stars'];
    $tabs = [
        ['url' => route('games.index'), 'label' => 'Juegos', 'icon' => 'puzzle', 'active' => request()->routeIs('games.index', 'games.levels', 'games.play')],
        ['url' => route('games.ranking'), 'label' => 'Ranking', 'icon' => 'trophy', 'active' => request()->routeIs('games.ranking', 'games.profile')],
        $me
            ? ['url' => route('games.account'), 'label' => 'Mi avance', 'icon' => 'star', 'active' => request()->routeIs('games.account')]
            : ['url' => route('player.login'), 'label' => 'Ingresar', 'icon' => 'user', 'active' => request()->routeIs('player.*')],
    ];
@endphp

<x-layouts.public :title="$title" :description="$description" :noindex="$noindex">
    {{-- Barra de los juegos --}}
    <div class="border-b border-line bg-white/70">
        <div class="container-content flex min-h-14 flex-wrap items-center justify-between gap-x-4 gap-y-2 py-2">
            <nav aria-label="Juegos bíblicos" class="-mx-2 flex items-center gap-1 overflow-x-auto">
                @foreach ($tabs as $tab)
                    <a href="{{ $tab['url'] }}" @if ($tab['active']) aria-current="page" @endif
                       @class(['inline-flex min-h-10 shrink-0 items-center gap-1.5 rounded-full px-3 text-sm font-semibold transition-colors',
                               'bg-primary text-white' => $tab['active'], 'text-primary hover:bg-cream' => ! $tab['active']])>
                        <x-ui.icon :name="$tab['icon']" class="size-4" /> {{ $tab['label'] }}
                    </a>
                @endforeach
            </nav>
            @if ($me)
                <div class="flex items-center gap-2 text-sm">
                    <a href="{{ route('games.account') }}" class="inline-flex items-center gap-1.5 rounded-full bg-accent-light/25 px-3 py-1.5 font-semibold text-primary ring-1 ring-accent-light/60">
                        <x-ui.icon name="star" class="size-4 text-brand-strong" />
                        <span>{{ $me->nickname }} · {{ $myStars }} <span class="sr-only">estrellas</span></span>
                    </a>
                    <form method="POST" action="{{ route('player.logout') }}">
                        @csrf
                        <button type="submit" class="min-h-10 rounded-full px-3 font-semibold text-muted hover:bg-cream hover:text-primary">Salir</button>
                    </form>
                </div>
            @else
                <a href="{{ route('player.register') }}" class="inline-flex min-h-10 items-center gap-1.5 rounded-full bg-brand-strong px-4 text-sm font-semibold text-white hover:bg-primary">
                    Crear cuenta y ganar premios
                </a>
            @endif
        </div>
    </div>

    <div class="container-content pt-6 pb-16 sm:pt-10">
        @if ($back)
            <a href="{{ $back['url'] }}" class="inline-flex min-h-11 items-center gap-1 text-sm font-semibold text-brand-strong hover:text-primary">
                <x-ui.icon name="chevron-left" class="size-4" /> {{ $back['label'] }}
            </a>
        @endif
        @if ($heading)
            <div @class(['flex items-center gap-4', 'mt-2' => $back])>
                @if ($icon)
                    <span class="flex size-14 shrink-0 items-center justify-center rounded-2xl bg-linear-to-br from-primary-deep to-gradient-end text-accent-light shadow-lg">
                        <x-ui.icon :name="$icon" class="size-7" />
                    </span>
                @endif
                <div class="min-w-0">
                    <h1 class="text-2xl font-extrabold sm:text-3xl">{{ $heading }}</h1>
                    @isset($subtitle)<p class="text-muted">{{ $subtitle }}</p>@endisset
                </div>
            </div>
        @endif

        @if (session('status'))
            <x-ui.alert type="success" class="mt-6">{{ session('status') }}</x-ui.alert>
        @endif

        {{ $slot }}
    </div>
</x-layouts.public>
