@props([
    'title' => null,
    'description' => null,
    'noindex' => false,
    'event' => null, // evento de la página: muestra su submenú de módulos
    'overlay' => false, // cabecera transparente sobre una portada oscura (inicio)
])

@php
    $siteName = config('app.name');
    $metaDescription = $description ?? ($site->tagline ?: "{$site->church_name}: enseñanzas, Biblia, eventos y actividades de la iglesia.");

    // Barra principal: accesos clave con icono.
    $navigation = [
        ['url' => route('about'), 'label' => 'Nosotros', 'icon' => 'users', 'active' => 'about'],
        ['url' => route('teachings.index'), 'label' => 'Enseñanzas', 'icon' => 'list', 'active' => 'teachings.*'],
        ['url' => route('bible.index'), 'label' => 'Biblia', 'icon' => 'book', 'active' => 'bible.*'],
        ['url' => route('events.index'), 'label' => 'Eventos', 'icon' => 'calendar', 'active' => 'events.*'],
        ['url' => route('bethel'), 'label' => 'Bethel TV', 'icon' => 'tv', 'active' => 'bethel'],
        ['url' => route('contact'), 'label' => 'Contacto', 'icon' => 'chat', 'active' => 'contact'],
    ];

    // Menú desplegable por columnas (solo páginas y sitios que existen).
    $megaMenu = [
        'Nosotros' => [
            ['url' => route('home'), 'label' => 'Inicio'],
            ['url' => route('about'), 'label' => 'Quiénes somos'],
            ['url' => route('about').'#lideres', 'label' => 'Nuestros líderes'],
            ['url' => route('home').'#visitanos', 'label' => 'Horario de cultos'],
            ['url' => route('contact'), 'label' => 'Contacto y preguntas'],
        ],
        'Palabra' => array_merge(
            [['url' => route('teachings.index'), 'label' => 'Todas las enseñanzas']],
            array_map(fn ($c) => ['url' => route('teachings.index', ['categoria' => $c->value]), 'label' => $c->label()], \App\Enums\TeachingCategory::cases()),
            [['url' => route('bible.index'), 'label' => 'Leer la Biblia']],
        ),
        'Eventos' => array_merge(
            [['url' => route('events.index'), 'label' => 'Agenda de eventos']],
            $featuredEvent ? [['url' => route('events.show', $featuredEvent), 'label' => $featuredEvent->title, 'strong' => true]] : [],
            $featuredEvent ? collect([
                'program' => ['events.program', 'Programa'],
                'venues' => ['events.venue', 'Sede y cómo llegar'],
                'donations' => ['events.donations', 'Donaciones'],
            ])->filter(fn ($item, $module) => $featuredEvent->hasModule($module))
                ->map(fn ($item) => ['url' => route($item[0], $featuredEvent), 'label' => $item[1]])->values()->all() : [],
        ),
        'Medios' => [
            ['url' => route('bethel'), 'label' => 'Bethel TV en vivo'],
            ['url' => config('bible.bethel_tv_url'), 'label' => 'Bethel Televisión', 'external' => true],
            ['url' => config('bible.bethel_radio_url'), 'label' => 'Bethel Radio', 'external' => true],
            ['url' => config('convention.impacto_url'), 'label' => 'Impacto Evangelístico', 'external' => true],
            ['url' => config('convention.official_url'), 'label' => 'Portal MMM Perú', 'external' => true],
            ['url' => config('convention.motto_url'), 'label' => 'Lema del año', 'external' => true],
        ],
    ];

    // Redes configuradas en Panel › Datos del sitio (nombre => icono).
    $socialIcons = ['Facebook' => 'facebook', 'Instagram' => 'instagram', 'YouTube' => 'youtube', 'TikTok' => 'tiktok'];

    // Submenú del evento de la página: solo sus módulos activos.
    $eventNavigation = $event?->hasAnyModule() ? collect([
        ['route' => 'events.show', 'label' => 'Resumen', 'module' => null],
        ['route' => 'events.program', 'label' => 'Programa', 'module' => 'program'],
        ['route' => 'events.venue', 'label' => 'Sede', 'module' => 'venues'],
        ['route' => 'events.donations', 'label' => 'Donaciones', 'module' => 'donations'],
    ])->filter(fn ($item) => $item['module'] === null || $event->hasModule($item['module'])) : collect();
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
    <meta name="theme-color" content="#39312d">
    @if ($noindex)
        <meta name="robots" content="noindex, nofollow">
    @endif
    @vite(['resources/css/app.css', 'resources/js/reveal.js'])
    <x-theme-style />
    @livewireStyles
</head>
<body class="flex min-h-dvh flex-col">
    <a href="#contenido" class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-50 focus:rounded-control focus:bg-white focus:px-4 focus:py-2 focus:shadow-card">
        Saltar al contenido
    </a>

    {{--
        Cabecera como la del portal oficial: logotipo, redes en círculos y botón
        de menú redondo. En el inicio va transparente sobre la portada; al bajar
        (o en las demás páginas) pasa a fondo sólido y se compacta.
        Sin backdrop-filter/transform aquí: convertirían la cabecera en el bloque
        contenedor del panel «fixed» del menú y este quedaría invisible.
    --}}
    <header x-data="{ open: false, scrolled: false, overlay: @js($overlay) }"
            x-init="scrolled = window.scrollY > 40"
            @scroll.window.throttle.100ms="scrolled = window.scrollY > 40"
            x-effect="document.documentElement.classList.toggle('overflow-hidden', open)"
            @keydown.escape.window="open = false"
            @class(['inset-x-0 top-0 z-40 text-white transition-[background-color,box-shadow] duration-300', 'fixed' => $overlay, 'sticky bg-primary-deep shadow-lg' => ! $overlay])
            :class="overlay && (open || scrolled) ? 'bg-primary-deep shadow-lg' : (overlay ? 'bg-linear-to-b from-black/45 to-transparent' : '')">
        <div class="container-content flex items-center justify-between gap-4 transition-[height] duration-300"
             :class="scrolled && ! open ? 'h-20' : 'h-24 sm:h-28'">
            <a href="{{ route('home') }}" class="flex items-center gap-4">
                <img src="{{ asset('images/logo-mmm-peru.png') }}" alt="Movimiento Misionero Mundial Perú" width="800" height="251"
                     class="w-auto transition-[height] duration-300" :class="scrolled && ! open ? 'h-11 sm:h-12' : 'h-12 sm:h-16'">
                <span class="hidden border-s border-white/40 ps-4 font-display text-xl font-bold tracking-wide sm:block">Huaraz</span>
            </a>

            <div class="flex items-center gap-2 sm:gap-3">
                {{-- Botón de menú redondo (naranja del portal); abierto: ✕ en blanco --}}
                <button type="button" class="me-1 inline-flex size-14 items-center justify-center rounded-full shadow-lg transition-colors sm:size-16"
                        :class="open ? 'bg-white text-primary-deep' : 'bg-accent-light text-primary-deep hover:bg-brand'"
                        @click="open = !open" :aria-expanded="open.toString()" aria-controls="menu-movil" aria-expanded="false">
                    <span class="sr-only" x-text="open ? 'Cerrar menú' : 'Abrir menú'">Abrir menú</span>
                    <span x-show="!open"><x-ui.icon name="menu" class="size-7" /></span>
                    <span x-show="open" x-cloak><x-ui.icon name="close" class="size-7" /></span>
                </button>

                {{--
                    Redes y contacto en círculos blancos (solo los que tienen enlace en
                    Panel › Datos del sitio). En móvil, solo Facebook, Instagram y WhatsApp.
                --}}
                @php
                    $headerLinks = collect([
                        ['label' => 'Facebook', 'icon' => 'facebook', 'url' => $site->facebook_url, 'mobile' => true],
                        ['label' => 'Instagram', 'icon' => 'instagram', 'url' => $site->instagram_url, 'mobile' => true],
                        ['label' => 'WhatsApp', 'icon' => 'whatsapp', 'url' => $site->whatsappUrl(), 'mobile' => true],
                        ['label' => 'YouTube', 'icon' => 'youtube', 'url' => $site->youtube_url, 'mobile' => false],
                        ['label' => 'TikTok', 'icon' => 'tiktok', 'url' => $site->tiktok_url, 'mobile' => false],
                        ['label' => 'Correo electrónico', 'icon' => 'mail', 'url' => $site->contact_email ? 'mailto:'.$site->contact_email : null, 'mobile' => false],
                    ])->filter(fn ($link) => filled($link['url']));
                @endphp
                @if ($headerLinks->isNotEmpty())
                    <ul class="flex items-center gap-1.5 sm:gap-2" aria-label="Redes y contacto">
                        @foreach ($headerLinks as $link)
                            @php
                                $external = str_starts_with($link['url'], 'http');
                            @endphp
                            <li @class(['hidden sm:block' => ! $link['mobile']])>
                                <a href="{{ $link['url'] }}" title="{{ $link['label'] }}" @if ($external) target="_blank" rel="noopener" @endif
                                   class="inline-flex size-9 items-center justify-center rounded-full bg-white text-primary-deep transition-colors hover:bg-accent-light sm:size-10">
                                    <x-ui.icon :name="$link['icon']" class="size-4.5 sm:size-5" /><span class="sr-only">{{ $link['label'] }}@if ($external) (pestaña nueva)@endif</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

        {{-- Menú desplegable por columnas --}}
        <nav id="menu-movil" aria-label="Menú completo" x-show="open" x-cloak
             x-transition:enter="transition duration-300 ease-out-soft" x-transition:enter-start="opacity-0 -translate-y-3" x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition duration-200 ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             class="fixed inset-x-0 top-24 bottom-0 overflow-y-auto border-t border-white/10 bg-primary-deep sm:top-28">
            <div class="container-content py-10">
                {{-- Accesos principales --}}
                <ul class="mb-10 grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-6">
                    @foreach ($navigation as $item)
                        @php
                            $current = request()->routeIs($item['active']);
                        @endphp
                        <li>
                            <a href="{{ $item['url'] }}" @if ($current) aria-current="page" @endif
                               @class(['flex min-h-12 items-center gap-2 rounded-control px-4 text-sm font-bold tracking-wide uppercase hover:bg-white/10 hover:text-brand', 'bg-white/15 text-brand' => $current, 'bg-white/5' => ! $current])>
                                <x-ui.icon :name="$item['icon']" class="size-4 text-brand" /> {{ $item['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>

                <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($megaMenu as $group => $links)
                        <section aria-labelledby="menu-{{ Str::slug($group) }}">
                            <h2 id="menu-{{ Str::slug($group) }}" class="font-sans text-lg font-extrabold tracking-wide text-white uppercase">{{ $group }}</h2>
                            <ul class="mt-3 space-y-1">
                                @foreach ($links as $link)
                                    <li>
                                        <a href="{{ $link['url'] }}"
                                           @if ($link['external'] ?? false) target="_blank" rel="noopener" @endif
                                           @class(['inline-flex min-h-9 items-center gap-1.5 transition-colors hover:text-brand', 'font-semibold text-brand' => $link['strong'] ?? false, 'text-cream/85' => ! ($link['strong'] ?? false)])>
                                            {{ $link['label'] }}
                                            @if ($link['external'] ?? false)
                                                <x-ui.icon name="external" class="size-3.5 opacity-70" /><span class="sr-only"> (pestaña nueva)</span>
                                            @endif
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </section>
                    @endforeach
                </div>

                <div class="mt-12 flex flex-wrap items-center justify-between gap-4 border-t border-white/10 pt-8">
                    <ul class="flex flex-wrap items-center gap-2">
                        @foreach ($site->socialLinks() as $network => $url)
                            <li>
                                <a href="{{ $url }}" target="_blank" rel="noopener" class="inline-flex min-h-10 items-center gap-2 rounded-full border border-white/20 px-4 text-sm font-semibold hover:bg-white/10">
                                    <x-ui.icon :name="$socialIcons[$network]" class="size-4" /> {{ $network }}<span class="sr-only"> (pestaña nueva)</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                    <a href="{{ route('login') }}" rel="nofollow" class="inline-flex items-center gap-2 text-sm text-cream/70 hover:text-white">
                        <x-ui.icon name="user" class="size-4" /> Acceso al panel
                    </a>
                </div>
            </div>
        </nav>

        @if ($eventNavigation->isNotEmpty())
            <nav aria-label="Secciones del evento" class="border-t border-sand-dark bg-cream text-primary">
                <div class="container-content flex items-center gap-3">
                    <span class="hidden shrink-0 text-xs font-semibold tracking-wide text-brand-strong uppercase md:block">{{ $event->title }}</span>
                    <ul class="-mx-1 flex gap-1 overflow-x-auto py-2 text-sm font-medium" x-init="$nextTick(() => { const a = $el.querySelector('[aria-current]'); if (a) $el.scrollLeft = a.offsetLeft - ($el.clientWidth - a.offsetWidth) / 2 })">
                        @foreach ($eventNavigation as $item)
                            <li class="shrink-0">
                                <a href="{{ route($item['route'], $event) }}"
                                   @class(['flex min-h-10 items-center rounded-control px-3 hover:bg-white hover:text-brand-strong', 'bg-white text-brand-strong shadow-sm' => request()->routeIs($item['route'])])
                                   @if (request()->routeIs($item['route'])) aria-current="page" @endif>
                                    {{ $item['label'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </nav>
        @endif
    </header>

    {{-- Páginas interiores: fondo crema con ondas suaves; la portada lleva sus propias secciones. --}}
    <main id="contenido" @class(['flex-1', 'bg-cream bg-rings pb-14 sm:pb-20' => ! $overlay])>
        {{ $slot }}
    </main>

    {{--
        Pie: borde superior inclinado (se monta un poco sobre la sección anterior),
        fila de marca con redes y columnas de enlaces, contacto, evento y medios.
    --}}
    <footer class="relative z-10 -mt-10 bg-primary-deep pt-20 text-cream/80 [clip-path:polygon(0_2.5rem,100%_0,100%_100%,0_100%)] sm:-mt-14 sm:pt-24 sm:[clip-path:polygon(0_3.5rem,100%_0,100%_100%,0_100%)]">
        <div class="container-content">
            {{-- Marca + redes --}}
            <div class="flex flex-col gap-6 border-b border-white/10 pb-8 sm:flex-row sm:items-center sm:justify-between">
                <a href="{{ route('home') }}" class="flex items-center gap-4">
                    <img src="{{ asset('images/logo-mmm-peru.png') }}" alt="Movimiento Misionero Mundial Perú" width="800" height="251" class="h-12 w-auto" loading="lazy">
                    <span class="border-s border-white/25 ps-4">
                        <span class="block font-display text-lg font-bold text-white">Huaraz</span>
                        <span class="block text-xs text-cream/60">{{ $site->church_name }}</span>
                    </span>
                </a>
                @if ($headerLinks->isNotEmpty())
                    <ul class="flex flex-wrap items-center gap-2" aria-label="Redes y contacto">
                        @foreach ($headerLinks as $link)
                            @php
                                $external = str_starts_with($link['url'], 'http');
                            @endphp
                            <li>
                                <a href="{{ $link['url'] }}" title="{{ $link['label'] }}" @if ($external) target="_blank" rel="noopener" @endif
                                   class="inline-flex size-10 items-center justify-center rounded-full bg-white/10 text-cream transition-colors hover:bg-accent-light hover:text-primary-deep">
                                    <x-ui.icon :name="$link['icon']" class="size-4.5" /><span class="sr-only">{{ $link['label'] }}@if ($external) (pestaña nueva)@endif</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <div class="grid gap-10 py-10 text-sm sm:grid-cols-2 lg:grid-cols-[1.2fr_1fr_1fr_1fr]">
                {{-- Enlaces rápidos --}}
                <nav aria-labelledby="pie-enlaces">
                    <h2 id="pie-enlaces" class="font-sans text-sm font-bold text-white">Enlaces rápidos</h2>
                    <ul class="mt-3 grid grid-cols-2 gap-x-6 sm:mt-4 sm:gap-y-2.5">
                        @foreach ([['url' => route('home'), 'label' => 'Inicio'], ...$navigation] as $item)
                            <li>
                                <a href="{{ $item['url'] }}" class="group inline-flex min-h-10 items-center gap-1.5 transition-colors hover:text-white sm:min-h-0">
                                    <x-ui.icon name="chevron-right" class="size-3.5 text-brand transition group-hover:translate-x-0.5" /> {{ $item['label'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </nav>

                {{-- Contacto --}}
                <div>
                    <h2 class="font-sans text-sm font-bold text-white">Contacto</h2>
                    <ul class="mt-4 space-y-3">
                        @if ($site->address)
                            <li class="flex gap-3"><x-ui.icon name="map-pin" class="mt-0.5 size-4 shrink-0 text-brand" />
                                <a href="{{ $site->mapsLink() }}" class="hover:text-white" rel="noopener" target="_blank">{{ $site->address }}<span class="sr-only"> (Google Maps, pestaña nueva)</span></a></li>
                        @endif
                        @if ($site->contact_phone)
                            <li class="flex gap-3"><x-ui.icon name="phone" class="mt-0.5 size-4 shrink-0 text-brand" />
                                <a href="tel:{{ preg_replace('/[^0-9+]/', '', $site->contact_phone) }}" class="hover:text-white">{{ $site->contact_phone }}</a></li>
                        @endif
                        @if ($site->whatsappUrl())
                            <li class="flex gap-3"><x-ui.icon name="whatsapp" class="mt-0.5 size-4 shrink-0 text-brand" />
                                <a href="{{ $site->whatsappUrl() }}" class="hover:text-white" rel="noopener" target="_blank">{{ $site->contact_whatsapp }}<span class="sr-only"> (WhatsApp, pestaña nueva)</span></a></li>
                        @endif
                        @if ($site->contact_email)
                            <li class="flex gap-3"><x-ui.icon name="mail" class="mt-0.5 size-4 shrink-0 text-brand" />
                                <a href="mailto:{{ $site->contact_email }}" class="break-all hover:text-white">{{ $site->contact_email }}</a></li>
                        @endif
                        @foreach (array_slice($site->scheduleLines(), 0, 4) as $line)
                            <li class="flex gap-3"><x-ui.icon name="clock" class="mt-0.5 size-4 shrink-0 text-brand" /> <span>{{ $line }}</span></li>
                        @endforeach
                        @unless ($site->hasContact() || $site->address || $site->scheduleLines())
                            <li>Datos de contacto por confirmar.</li>
                        @endunless
                    </ul>
                </div>

                {{-- Evento destacado --}}
                <div>
                    @if ($featuredEvent)
                        <h2 class="font-sans text-sm font-bold text-white">{{ $featuredEvent->title }}</h2>
                        <ul class="mt-4 space-y-3">
                            @if ($featuredEvent->theme)
                                <li class="flex gap-3"><x-ui.icon name="star" class="mt-0.5 size-4 shrink-0 text-brand" /> <span>«{{ $featuredEvent->theme }}»</span></li>
                            @endif
                            @if ($featuredEvent->hasDates())
                                <li class="flex gap-3"><x-ui.icon name="calendar" class="mt-0.5 size-4 shrink-0 text-brand" /> <span>{{ $featuredEvent->dateLabel() }}</span></li>
                            @endif
                            @if ($featuredEvent->city)
                                <li class="flex gap-3"><x-ui.icon name="map-pin" class="mt-0.5 size-4 shrink-0 text-brand" /> <span>{{ $featuredEvent->city }}</span></li>
                            @endif
                        </ul>
                        <a href="{{ route('events.show', $featuredEvent) }}" class="mt-5 inline-flex min-h-10 items-center gap-2 rounded-control bg-brand-strong px-4 text-sm font-bold text-white transition hover:bg-brand hover:text-primary-deep">
                            Ver el evento <x-ui.icon name="arrow-right" class="size-4" />
                        </a>
                    @else
                        <h2 class="font-sans text-sm font-bold text-white">Eventos</h2>
                        <a href="{{ route('events.index') }}" class="mt-4 inline-flex min-h-10 items-center gap-2 rounded-control bg-brand-strong px-4 text-sm font-bold text-white transition hover:bg-brand hover:text-primary-deep">
                            Ver la agenda <x-ui.icon name="arrow-right" class="size-4" />
                        </a>
                    @endif
                </div>

                {{-- Medios oficiales --}}
                <div>
                    <h2 class="font-sans text-sm font-bold text-white">Medios oficiales</h2>
                    <ul class="mt-3 sm:mt-4 sm:space-y-2.5">
                        @foreach ([
                            ['Portal oficial MMM Perú', config('convention.official_url')],
                            ['Bethel Televisión', config('bible.bethel_tv_url')],
                            ['Bethel Radio', config('bible.bethel_radio_url')],
                            ['Impacto Evangelístico', config('convention.impacto_url')],
                        ] as [$label, $url])
                            <li>
                                <a href="{{ $url }}" class="group inline-flex min-h-10 items-center gap-1.5 transition-colors hover:text-white sm:min-h-0" rel="noopener" target="_blank">
                                    <x-ui.icon name="chevron-right" class="size-3.5 text-brand transition group-hover:translate-x-0.5" /> {{ $label }}<span class="sr-only"> (se abre en una pestaña nueva)</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>

        <div class="border-t border-white/10">
            <div class="container-content flex flex-col gap-2 py-5 text-xs text-cream/60 sm:flex-row sm:items-center sm:justify-between">
                <p>© {{ now()->year }} {{ $site->church_name }} · {{ config('convention.organization') }}</p>
                <a href="{{ route('login') }}" rel="nofollow" class="inline-flex min-h-10 items-center gap-1.5 hover:text-white sm:min-h-0"><x-ui.icon name="user" class="size-3.5" /> Acceso al panel</a>
            </div>
        </div>
    </footer>

    {{-- En lugar de @livewireScripts: funciona también en un subdirectorio. --}}
    {{ \App\Support\LivewireAssets::scripts() }}
</body>
</html>
