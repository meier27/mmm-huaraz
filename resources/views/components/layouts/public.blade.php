@props([
    'title' => null,
    'description' => null,
])

@php
    $siteName = config('app.name');

    // Solo se muestran enlaces a páginas que ya existen.
    $navigation = collect([
        ['route' => 'home', 'label' => 'Inicio'],
        ['route' => 'program', 'label' => 'Programa'],
        ['route' => 'venue', 'label' => 'Sede'],
        ['route' => 'dishes.index', 'label' => 'Reservas'],
        ['route' => 'donations', 'label' => 'Donaciones'],
        ['route' => 'accommodations', 'label' => 'Hospedaje'],
        ['route' => 'tourism', 'label' => 'Turismo'],
        ['route' => 'contact', 'label' => 'Contacto'],
    ])->filter(fn ($item) => Route::has($item['route']));
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? "$title · $siteName" : $siteName }}</title>
    @if ($description)
        <meta name="description" content="{{ $description }}">
    @endif
    <meta name="theme-color" content="#713218">
    @vite('resources/css/app.css')
    @livewireStyles
</head>
<body class="flex min-h-dvh flex-col">
    <a href="#contenido" class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-50 focus:rounded-control focus:bg-white focus:px-4 focus:py-2 focus:shadow-card">
        Saltar al contenido
    </a>

    <header x-data="{ open: false }" class="sticky top-0 z-40 border-b border-line bg-white/95 backdrop-blur">
        <div class="container-content flex h-16 items-center justify-between gap-4">
            <a href="{{ route('home') }}" class="font-display text-lg font-bold text-earth">
                MMM Huaraz <span class="text-brand-strong">2026</span>
            </a>

            @if ($navigation->count() > 1)
                <nav aria-label="Principal" class="hidden md:block">
                    <ul class="flex items-center gap-1 text-sm font-medium">
                        @foreach ($navigation as $item)
                            <li>
                                <a href="{{ route($item['route']) }}"
                                   @class(['rounded-control px-3 py-2 hover:bg-cream', 'text-brand-strong' => request()->routeIs($item['route'])])
                                   @if (request()->routeIs($item['route'])) aria-current="page" @endif>
                                    {{ $item['label'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </nav>

                <button type="button" class="inline-flex size-11 items-center justify-center rounded-control md:hidden"
                        @click="open = !open" :aria-expanded="open.toString()" aria-controls="menu-movil">
                    <span class="sr-only">Abrir menú</span>
                    <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path x-show="!open" stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16" />
                        <path x-show="open" x-cloak stroke-linecap="round" d="M6 6l12 12M18 6L6 18" />
                    </svg>
                </button>
            @endif
        </div>

        @if ($navigation->count() > 1)
            <nav id="menu-movil" aria-label="Principal móvil" x-show="open" x-cloak @keydown.escape.window="open = false" class="border-t border-line md:hidden">
                <ul class="container-content flex flex-col py-2">
                    @foreach ($navigation as $item)
                        <li>
                            <a href="{{ route($item['route']) }}" class="block rounded-control px-3 py-3 font-medium hover:bg-cream"
                               @if (request()->routeIs($item['route'])) aria-current="page" @endif>
                                {{ $item['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        @endif
    </header>

    <main id="contenido" class="flex-1">
        {{ $slot }}
    </main>

    <footer class="bg-earth text-cream">
        <div class="container-content flex flex-col gap-2 py-8 text-sm sm:flex-row sm:items-center sm:justify-between">
            <p>{{ config('convention.organization') }} · {{ config('convention.city') }} {{ \Illuminate\Support\Carbon::parse(config('convention.starts_at'))->year }}</p>
            <a href="{{ config('convention.official_url') }}" class="underline underline-offset-4 hover:text-white" rel="noopener" target="_blank">
                Portal oficial MMM Perú<span class="sr-only"> (se abre en una pestaña nueva)</span>
            </a>
        </div>
    </footer>

    @livewireScripts
</body>
</html>
