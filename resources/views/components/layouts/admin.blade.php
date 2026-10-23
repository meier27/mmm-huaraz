@props(['title' => 'Panel'])

@php
    // Menú lateral agrupado. Cada grupo y cada enlace se muestran solo si el
    // rol tiene permiso (la autorización real está en las rutas y componentes).
    $adminFeatured = auth()->check() && Gate::allows('manage-content') ? \App\Models\ChurchEvent::featured() : null;

    // ¿Estamos dentro de un evento? (la ruta trae el modelo o su id)
    $routeEvent = request()->route('event');
    $currentEventId = $routeEvent instanceof \App\Models\ChurchEvent ? $routeEvent->id : (int) $routeEvent;
    $onEvents = request()->routeIs('admin.events', 'admin.events.*');
    $onFeatured = $adminFeatured && $currentEventId === $adminFeatured->id;

    $groups = auth()->check() ? collect([
        'General' => [
            ['route' => 'admin.dashboard', 'label' => 'Inicio', 'icon' => 'home', 'can' => 'access-admin'],
            ['route' => 'admin.donations', 'label' => 'Aportes', 'icon' => 'coins', 'can' => 'manage-donations', 'badge' => 'donations'],
        ],
        'Sitio web' => [
            ['route' => 'admin.site', 'label' => 'Datos del sitio', 'icon' => 'settings', 'can' => 'manage-content'],
            ['route' => 'admin.appearance', 'label' => 'Apariencia', 'icon' => 'image', 'can' => 'manage-content'],
            ['route' => 'admin.social', 'label' => 'Redes sociales', 'icon' => 'facebook', 'can' => 'manage-content'],
            ['route' => 'admin.banners', 'label' => 'Banners', 'icon' => 'image', 'can' => 'manage-content'],
            ['route' => 'admin.gallery', 'label' => 'Fotos «Nuestra familia»', 'icon' => 'users', 'can' => 'manage-content'],
            ['route' => 'admin.media', 'label' => 'Medios', 'icon' => 'tv', 'can' => 'manage-content'],
            ['route' => 'admin.mottos', 'label' => 'Lemas', 'icon' => 'star', 'can' => 'manage-content'],
            ['route' => 'admin.teachings', 'label' => 'Enseñanzas', 'icon' => 'book', 'can' => 'manage-content'],
            ['route' => 'admin.doctrine', 'label' => 'Doctrinario', 'icon' => 'shield', 'can' => 'manage-content'],
            ['route' => 'admin.verses', 'label' => 'Versículos', 'icon' => 'quote', 'can' => 'manage-content'],
            ['route' => 'admin.about', 'label' => 'Página «Nosotros»', 'icon' => 'info', 'can' => 'manage-content'],
            ['route' => 'admin.leaders', 'label' => 'Líderes', 'icon' => 'users', 'can' => 'manage-content'],
            ['route' => 'admin.faqs', 'label' => 'Preguntas', 'icon' => 'help', 'can' => 'manage-content'],
        ],
        'Juegos' => [
            ['route' => 'admin.games', 'label' => 'Preguntas y palabras', 'icon' => 'puzzle', 'can' => 'manage-content'],
            ['route' => 'admin.weekly', 'label' => 'Ranking semanal', 'icon' => 'star', 'can' => 'manage-content'],
            ['route' => 'admin.prizes', 'label' => 'Premios', 'icon' => 'trophy', 'can' => 'manage-content'],
            ['route' => 'admin.claims', 'label' => 'Canjes', 'icon' => 'check', 'can' => 'manage-content', 'badge' => 'claims'],
            ['route' => 'admin.players', 'label' => 'Jugadores', 'icon' => 'users', 'can' => 'manage-content'],
        ],
        'Eventos' => array_values(array_filter([
            ['route' => 'admin.events', 'label' => 'Todos los eventos', 'icon' => 'calendar', 'can' => 'manage-content', 'is_active' => $onEvents && ! $onFeatured],
            // Acceso directo al evento destacado y sus pestañas.
            $adminFeatured ? ['route' => 'admin.events.edit', 'params' => $adminFeatured, 'label' => $adminFeatured->title, 'icon' => 'star', 'can' => 'manage-content', 'is_active' => $onFeatured] : null,
            ['route' => 'admin.donation-methods', 'label' => 'Métodos de donación', 'icon' => 'heart', 'can' => 'manage-content'],
        ])),
        'Administración' => [
            ['route' => 'admin.users', 'label' => 'Usuarios', 'icon' => 'user', 'can' => 'manage-users'],
            ['route' => 'admin.audit', 'label' => 'Auditoría', 'icon' => 'shield', 'can' => 'manage-users'],
        ],
    ])->map(fn ($items) => collect($items)->filter(fn ($item) => Gate::allows($item['can'])))->filter->isNotEmpty() : collect();

    $badges = [
        'donations' => Gate::allows('manage-donations') ? \App\Models\Donation::query()->status(\App\Enums\DonationStatus::Pending)->count() : 0,
        'claims' => Gate::allows('manage-content') ? \App\Models\PrizeClaim::query()->where('status', \App\Enums\ClaimStatus::Pending->value)->count() : 0,
    ];
    $user = auth()->user();
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title }} · Administración · {{ config('app.name') }}</title>
    @vite('resources/css/app.css')
    <x-theme-style />
    @livewireStyles
</head>
<body class="min-h-dvh bg-stone-100" x-data="{ menu: false }" x-on:keydown.escape.window="menu = false">
    <a href="#contenido" class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-60 focus:rounded-control focus:bg-white focus:px-4 focus:py-2">
        Saltar al contenido
    </a>

    @auth
        {{-- Fondo del cajón en móvil --}}
        <div x-show="menu" x-cloak x-transition.opacity class="fixed inset-0 z-40 bg-stone-950/50 lg:hidden" x-on:click="menu = false" aria-hidden="true"></div>

        <aside id="menu-panel"
               class="fixed inset-y-0 left-0 z-50 flex w-72 -translate-x-full flex-col bg-stone-900 text-stone-300 transition-transform duration-200 lg:translate-x-0"
               x-bind:class="menu ? 'translate-x-0' : ''"
               aria-label="Menú del panel">
            {{-- Marca y usuario --}}
            <div class="flex items-center justify-between gap-3 px-5 pt-5">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3">
                    <span class="flex size-10 items-center justify-center rounded-control bg-brand font-display text-lg font-extrabold text-primary" aria-hidden="true">M</span>
                    <span class="leading-tight">
                        <span class="block font-display font-bold text-white">MMM Huaraz</span>
                        <span class="block text-xs text-stone-400">Panel de administración</span>
                    </span>
                </a>
                <button type="button" class="inline-flex size-10 items-center justify-center rounded-control text-stone-400 hover:bg-white/10 hover:text-white lg:hidden" x-on:click="menu = false">
                    <x-ui.icon name="close" class="size-5" /><span class="sr-only">Cerrar menú</span>
                </button>
            </div>

            <div class="mx-5 mt-5 flex items-center gap-3 rounded-card bg-white/5 p-3">
                <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-stone-700 font-semibold text-white" aria-hidden="true">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                <span class="min-w-0 leading-tight">
                    <span class="block truncate text-sm font-semibold text-white">{{ $user->name }}</span>
                    <span class="block truncate text-xs text-stone-400">{{ $user->role->label() }}</span>
                </span>
            </div>

            {{-- Navegación --}}
            <nav aria-label="Administración" class="mt-4 flex-1 overflow-y-auto px-3 pb-4">
                @foreach ($groups as $group => $items)
                    <p class="mt-4 mb-1 px-3 text-[0.7rem] font-semibold tracking-[0.15em] text-stone-500 uppercase">{{ $group }}</p>
                    <ul class="space-y-0.5">
                        @foreach ($items as $item)
                            @php
                                $active = $item['is_active'] ?? request()->routeIs($item['active'] ?? $item['route']);
                                $badge = isset($item['badge']) ? $badges[$item['badge']] : 0;
                            @endphp
                            <li>
                                <a href="{{ route($item['route'], $item['params'] ?? []) }}" wire:navigate
                                   @class([
                                       'flex min-h-11 items-center gap-3 rounded-full px-3 text-sm font-medium transition-colors',
                                       'bg-brand text-primary shadow-sm' => $active,
                                       'hover:bg-white/10 hover:text-white' => ! $active,
                                   ])
                                   @if ($active) aria-current="page" @endif>
                                    <x-ui.icon :name="$item['icon']" class="size-5" />
                                    <span class="flex-1 truncate">{{ $item['label'] }}</span>
                                    @if ($badge > 0)
                                        <span @class(['min-w-6 rounded-full px-1.5 text-center text-xs font-bold tabular-nums', 'bg-primary text-white' => $active, 'bg-brand text-primary' => ! $active])>
                                            {{ $badge }}<span class="sr-only"> pendientes</span>
                                        </span>
                                    @endif
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endforeach
            </nav>

            {{-- Pie del menú --}}
            <div class="space-y-1 border-t border-white/10 p-3">
                <a href="{{ route('home') }}" target="_blank" rel="noopener" class="flex min-h-11 items-center gap-3 rounded-full px-3 text-sm font-medium hover:bg-white/10 hover:text-white">
                    <x-ui.icon name="external" class="size-5" /> Ver sitio<span class="sr-only"> (se abre en una pestaña nueva)</span>
                </a>
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="flex min-h-11 w-full items-center gap-3 rounded-full px-3 text-sm font-medium hover:bg-white/10 hover:text-white">
                        <x-ui.icon name="logout" class="size-5" /> Cerrar sesión
                    </button>
                </form>
            </div>
        </aside>

        {{-- Barra superior solo en móvil/tablet --}}
        <header class="sticky top-0 z-30 flex h-14 items-center gap-3 border-b border-line bg-white px-4 lg:hidden">
            <button type="button" class="inline-flex size-11 items-center justify-center rounded-control text-primary hover:bg-cream"
                    x-on:click="menu = true" aria-controls="menu-panel" x-bind:aria-expanded="menu.toString()" aria-expanded="false">
                <x-ui.icon name="menu" /><span class="sr-only">Abrir menú</span>
            </button>
            <span class="truncate font-display font-bold text-primary">{{ $title }}</span>
        </header>
    @endauth

    <main id="contenido" @class(['min-w-0 px-4 py-6 sm:px-6 lg:px-10 lg:py-8', 'lg:pl-[calc(18rem+2.5rem)]' => auth()->check(), 'container-content' => ! auth()->check()])>
        @if (session('status'))
            <x-ui.alert type="success" class="mb-6">{{ session('status') }}</x-ui.alert>
        @endif

        {{ $slot }}
    </main>

    {{-- En lugar de @livewireScripts: funciona también en un subdirectorio. --}}
    {{ \App\Support\LivewireAssets::scripts() }}
</body>
</html>
