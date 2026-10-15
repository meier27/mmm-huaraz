@props(['title' => 'Panel'])

@php
    $adminNavigation = auth()->check() ? collect([
        ['route' => 'admin.dashboard', 'label' => 'Inicio', 'can' => 'access-admin'],
        ['route' => 'admin.settings', 'label' => 'Convención', 'can' => 'manage-content'],
        ['route' => 'admin.program', 'label' => 'Programa', 'can' => 'manage-content'],
        ['route' => 'admin.program-days', 'label' => 'Días', 'can' => 'manage-content'],
        ['route' => 'admin.venues', 'label' => 'Sedes', 'can' => 'manage-content'],
        ['route' => 'admin.donation-methods', 'label' => 'Donaciones', 'can' => 'manage-content'],
        ['route' => 'admin.accommodations', 'label' => 'Hospedaje', 'can' => 'manage-content'],
        ['route' => 'admin.tourist-places', 'label' => 'Turismo', 'can' => 'manage-content'],
        ['route' => 'admin.faqs', 'label' => 'Preguntas', 'can' => 'manage-content'],
        ['route' => 'admin.users', 'label' => 'Usuarios', 'can' => 'manage-users'],
        ['route' => 'admin.audit', 'label' => 'Auditoría', 'can' => 'manage-users'],
    ])->filter(fn ($item) => Gate::allows($item['can'])) : collect();
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title }} · Administración · {{ config('app.name') }}</title>
    @vite('resources/css/app.css')
    @livewireStyles
</head>
<body class="min-h-dvh bg-stone-50">
    <a href="#contenido" class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-50 focus:rounded-control focus:bg-white focus:px-4 focus:py-2">
        Saltar al contenido
    </a>

    @auth
        <header class="border-b border-line bg-white">
            <div class="container-content flex h-16 items-center justify-between gap-4">
                <a href="{{ route('admin.dashboard') }}" class="font-display font-bold text-earth">Panel MMM Huaraz</a>

                <div class="flex items-center gap-3 text-sm">
                    <a href="{{ route('home') }}" class="hidden text-muted underline-offset-4 hover:underline sm:inline">Ver sitio</a>
                    <span class="hidden text-muted md:inline">{{ auth()->user()->name }} · {{ auth()->user()->role->label() }}</span>
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <x-ui.button type="submit" variant="ghost">Cerrar sesión</x-ui.button>
                    </form>
                </div>
            </div>

            @if ($adminNavigation->count() > 1)
                <nav aria-label="Administración" class="border-t border-line">
                    <ul class="container-content flex gap-1 overflow-x-auto py-2 text-sm font-medium">
                        @foreach ($adminNavigation as $item)
                            <li class="shrink-0">
                                <a href="{{ route($item['route']) }}" wire:navigate
                                   @class(['block rounded-control px-3 py-2 hover:bg-cream', 'bg-cream text-brand-strong' => request()->routeIs($item['route'])])
                                   @if (request()->routeIs($item['route'])) aria-current="page" @endif>
                                    {{ $item['label'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </nav>
            @endif
        </header>
    @endauth

    <main id="contenido" class="container-content py-8">
        @if (session('status'))
            <x-ui.alert type="success" class="mb-6">{{ session('status') }}</x-ui.alert>
        @endif

        {{ $slot }}
    </main>

    @livewireScripts
</body>
</html>
