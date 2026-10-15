@props([
    'title' => null,
    'description' => null,
    'noindex' => false,
])

@php
    $siteName = config('app.name');
    $metaDescription = $description ?? "{$settings->name} «{$settings->theme}» en {$settings->city}, del {$settings->dateRangeLabel()}.";

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
    <meta name="description" content="{{ $metaDescription }}">
    <meta property="og:title" content="{{ $title ?? $siteName }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="es_PE">
    <meta name="theme-color" content="#713218">
    @if ($noindex)
        <meta name="robots" content="noindex, nofollow">
    @endif
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
                MMM {{ $settings->city }} <span class="text-brand-strong">{{ $settings->starts_on->year }}</span>
            </a>

            <nav aria-label="Principal" class="hidden lg:block">
                <ul class="flex items-center gap-1 text-sm font-medium">
                    @foreach ($navigation as $item)
                        <li>
                            <a href="{{ route($item['route']) }}"
                               @class(['rounded-control px-3 py-2 hover:bg-cream', 'bg-cream text-brand-strong' => request()->routeIs($item['route'])])
                               @if (request()->routeIs($item['route'])) aria-current="page" @endif>
                                {{ $item['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>

            <button type="button" class="inline-flex size-11 items-center justify-center rounded-control lg:hidden"
                    @click="open = !open" :aria-expanded="open.toString()" aria-controls="menu-movil" aria-expanded="false">
                <span class="sr-only">Menú</span>
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path x-show="!open" stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16" />
                    <path x-show="open" x-cloak stroke-linecap="round" d="M6 6l12 12M18 6L6 18" />
                </svg>
            </button>
        </div>

        <nav id="menu-movil" aria-label="Principal móvil" x-show="open" x-cloak @keydown.escape.window="open = false" class="border-t border-line lg:hidden">
            <ul class="container-content grid grid-cols-2 gap-1 py-3">
                @foreach ($navigation as $item)
                    <li>
                        <a href="{{ route($item['route']) }}"
                           @class(['block rounded-control px-3 py-3 font-medium hover:bg-cream', 'bg-cream text-brand-strong' => request()->routeIs($item['route'])])
                           @if (request()->routeIs($item['route'])) aria-current="page" @endif>
                            {{ $item['label'] }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>
    </header>

    <main id="contenido" class="flex-1">
        {{ $slot }}
    </main>

    <footer class="bg-earth text-cream">
        <div class="container-content grid gap-8 py-10 text-sm sm:grid-cols-3">
            <div>
                <p class="font-display text-base font-bold text-white">{{ $settings->name }}</p>
                <p class="mt-1">«{{ $settings->theme }}»</p>
                <p class="mt-1">{{ $settings->city }} · {{ $settings->dateRangeLabel() }}</p>
            </div>

            <div>
                <p class="font-semibold text-white">Contacto</p>
                <ul class="mt-2 space-y-1">
                    @if ($settings->contact_phone)
                        <li><a href="tel:{{ preg_replace('/[^0-9+]/', '', $settings->contact_phone) }}" class="hover:text-white hover:underline">{{ $settings->contact_phone }}</a></li>
                    @endif
                    @if ($settings->whatsappUrl())
                        <li><a href="{{ $settings->whatsappUrl() }}" class="hover:text-white hover:underline" rel="noopener" target="_blank">WhatsApp</a></li>
                    @endif
                    @if ($settings->contact_email)
                        <li><a href="mailto:{{ $settings->contact_email }}" class="break-all hover:text-white hover:underline">{{ $settings->contact_email }}</a></li>
                    @endif
                    @if (! $settings->contact_phone && ! $settings->contact_whatsapp && ! $settings->contact_email)
                        <li>Datos de contacto por confirmar.</li>
                    @endif
                </ul>
            </div>

            <div>
                <p class="font-semibold text-white">Enlaces</p>
                <ul class="mt-2 space-y-1">
                    @foreach ($settings->socialLinks() as $network => $url)
                        <li><a href="{{ $url }}" class="hover:text-white hover:underline" rel="noopener" target="_blank">{{ $network }}</a></li>
                    @endforeach
                    <li>
                        <a href="{{ config('convention.official_url') }}" class="hover:text-white hover:underline" rel="noopener" target="_blank">
                            Portal oficial MMM Perú<span class="sr-only"> (se abre en una pestaña nueva)</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        <div class="border-t border-white/15">
            <p class="container-content py-4 text-xs text-cream/80">{{ $settings->organization }}</p>
        </div>
    </footer>

    @livewireScripts
</body>
</html>
