<x-layouts.public>
    {{-- ===== Hero: carrusel de banners o portada predeterminada ===== --}}
    @if ($banners->isNotEmpty())
        <h1 class="sr-only">{{ $settings->name }} «{{ $settings->theme }}»</h1>
        <section class="relative isolate overflow-hidden bg-earth text-white"
                 aria-roledescription="carrusel" aria-label="Destacados"
                 x-data="{
                     active: 0,
                     total: {{ $banners->count() }},
                     playing: {{ $banners->count() > 1 ? 'true' : 'false' }} && ! window.matchMedia('(prefers-reduced-motion: reduce)').matches,
                     timer: null,
                     init() { this.start() },
                     start() { this.stop(); if (this.playing) this.timer = setInterval(() => this.next(), 7000) },
                     stop() { clearInterval(this.timer) },
                     toggle() { this.playing = ! this.playing; this.start() },
                     next() { this.active = (this.active + 1) % this.total },
                     prev() { this.active = (this.active - 1 + this.total) % this.total },
                     go(i) { this.active = i; this.start() },
                 }"
                 x-on:mouseenter="stop()" x-on:mouseleave="start()" x-on:focusin="stop()"
                 x-on:keydown.arrow-right="next(); start()" x-on:keydown.arrow-left="prev(); start()">

            <div class="relative min-h-136 sm:min-h-152 lg:min-h-168">
                @foreach ($banners as $banner)
                    <div
                         role="group" aria-roledescription="diapositiva" aria-label="{{ $loop->iteration }} de {{ $banners->count() }}"
                         @class(['absolute inset-0 transition-opacity duration-700 ease-out', 'opacity-100 z-10' => $loop->first, 'opacity-0 z-0' => ! $loop->first])
                         x-bind:class="active === {{ $loop->index }} ? 'opacity-100 z-10' : 'opacity-0 z-0'"
                         x-bind:inert="active !== {{ $loop->index }}">
                        @if ($banner->imageUrl())
                            <img src="{{ $banner->imageUrl() }}" alt="{{ $banner->image_alt ?? '' }}" width="1920" height="900"
                                 class="absolute inset-0 -z-10 size-full object-cover"
                                 @if ($loop->first) fetchpriority="high" @else loading="lazy" @endif decoding="async">
                            <div class="absolute inset-0 -z-10 bg-linear-to-r from-earth/95 via-earth/70 to-earth/10"></div>
                        @else
                            <div class="absolute inset-0 -z-10 bg-linear-to-br from-earth via-[#5a2712] to-navy"></div>
                            <x-ui.mountains class="absolute inset-x-0 bottom-0 -z-10 h-40 text-[#3d1a0b] sm:h-56" />
                        @endif

                        <div class="container-content flex h-full flex-col justify-center py-20">
                            <p class="text-sm font-semibold tracking-[0.2em] text-brand uppercase">{{ $settings->organization }} · {{ $settings->city }}</p>
                            <h2 class="mt-4 max-w-3xl font-display text-4xl leading-tight font-extrabold text-white sm:text-5xl lg:text-6xl">{{ $banner->title }}</h2>
                            @if ($banner->subtitle)
                                <p class="mt-5 max-w-2xl text-lg text-cream/90 sm:text-xl">{{ $banner->subtitle }}</p>
                            @endif
                            @if ($banner->cta_label && $banner->cta_url)
                                <div class="mt-8">
                                    <a href="{{ $banner->cta_url }}" class="inline-flex min-h-12 items-center gap-2 rounded-control bg-brand px-6 py-3 font-bold text-earth shadow-lg transition hover:bg-white"
                                       @if (str_starts_with($banner->cta_url, 'https://')) target="_blank" rel="noopener" @endif>
                                        {{ $banner->cta_label }} <x-ui.icon name="arrow-right" class="size-5" />
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            @if ($banners->count() > 1)
                <div class="absolute inset-x-0 bottom-20 z-20 sm:bottom-24">
                    <div class="container-content flex items-center gap-3">
                        <button type="button" x-on:click="prev(); start()" class="inline-flex size-11 items-center justify-center rounded-full bg-white/15 backdrop-blur hover:bg-white/30" aria-label="Diapositiva anterior">
                            <x-ui.icon name="chevron-left" class="size-5" />
                        </button>
                        <div class="flex items-center gap-2">
                            @foreach ($banners as $banner)
                                <button type="button" x-on:click="go({{ $loop->index }})" class="flex size-6 items-center justify-center" aria-label="Ir a la diapositiva {{ $loop->iteration }}"
                                        x-bind:aria-current="active === {{ $loop->index }} ? 'true' : 'false'">
                                    <span class="block h-2 rounded-full bg-white transition-all" x-bind:class="active === {{ $loop->index }} ? 'w-6' : 'w-2 opacity-50'"></span>
                                </button>
                            @endforeach
                        </div>
                        <button type="button" x-on:click="next(); start()" class="inline-flex size-11 items-center justify-center rounded-full bg-white/15 backdrop-blur hover:bg-white/30" aria-label="Diapositiva siguiente">
                            <x-ui.icon name="chevron-right" class="size-5" />
                        </button>
                        <button type="button" x-on:click="toggle()" class="ms-auto inline-flex size-11 items-center justify-center rounded-full bg-white/15 backdrop-blur hover:bg-white/30"
                                x-bind:aria-label="playing ? 'Pausar carrusel' : 'Reproducir carrusel'" aria-label="Pausar carrusel">
                            <span x-show="playing"><x-ui.icon name="pause" class="size-5" /></span>
                            <span x-show="! playing" x-cloak><x-ui.icon name="play" class="size-5" /></span>
                        </button>
                    </div>
                </div>
            @endif
        </section>
    @else
        <section class="relative isolate overflow-hidden bg-linear-to-br from-earth via-[#5a2712] to-navy text-white">
            <div class="absolute -top-24 -right-24 -z-10 size-112 rounded-full bg-brand/30 blur-3xl" aria-hidden="true"></div>
            <x-ui.mountains class="absolute inset-x-0 bottom-0 -z-10 h-40 text-[#3d1a0b] sm:h-60" />

            <div class="container-content pt-20 pb-36 sm:pt-28 sm:pb-48">
                <p class="text-sm font-semibold tracking-[0.2em] text-brand uppercase">{{ $settings->organization }} · {{ $settings->city }}</p>
                <h1 class="mt-4 max-w-4xl font-display text-4xl leading-tight font-extrabold text-white sm:text-6xl lg:text-7xl">
                    {{ $settings->name }}
                    <span class="mt-2 block text-brand">«{{ $settings->theme }}»</span>
                </h1>
                <p class="mt-6 max-w-2xl text-lg text-cream/90 sm:text-xl">
                    {{ $settings->dateRangeLabel() }}{{ $mainVenue ? ' · '.$mainVenue->name : '' }}
                </p>
                <div class="mt-9 flex flex-wrap gap-3">
                    <a href="{{ route('program') }}" class="inline-flex min-h-12 items-center gap-2 rounded-control bg-brand px-6 py-3 font-bold text-earth shadow-lg transition hover:bg-white">
                        Ver programa <x-ui.icon name="arrow-right" class="size-5" />
                    </a>
                    <a href="{{ route('venue') }}" class="inline-flex min-h-12 items-center gap-2 rounded-control border border-white/40 px-6 py-3 font-bold text-white transition hover:bg-white/10">
                        Cómo llegar
                    </a>
                </div>
            </div>
        </section>
    @endif

    {{-- ===== Datos clave ===== --}}
    <section class="relative z-30 -mt-14 sm:-mt-16" aria-label="Datos clave">
        <div class="container-content">
            <dl class="grid gap-3 rounded-card bg-white p-3 shadow-xl ring-1 ring-black/5 sm:grid-cols-3 sm:p-4">
                @foreach ([
                    ['icon' => 'calendar', 'label' => 'Fechas', 'value' => $settings->dateRangeLabel()],
                    ['icon' => 'map-pin', 'label' => 'Sede', 'value' => ($mainVenue?->name ? $mainVenue->name.', ' : '').$settings->city],
                    ['icon' => 'clock', 'label' => 'Cuenta atrás', 'value' => $settings->countdownLabel()],
                ] as $fact)
                    <div class="flex items-center gap-4 rounded-control p-3">
                        <span class="flex size-12 shrink-0 items-center justify-center rounded-full bg-cream text-brand-strong">
                            <x-ui.icon :name="$fact['icon']" />
                        </span>
                        <div>
                            <dt class="text-xs font-semibold tracking-wide text-muted uppercase">{{ $fact['label'] }}</dt>
                            <dd class="font-display text-base font-bold text-earth sm:text-lg">{{ $fact['value'] }}</dd>
                        </div>
                    </div>
                @endforeach
            </dl>
        </div>
    </section>

    {{-- ===== Bienvenida ===== --}}
    @if ($settings->summary)
        <section class="container-content py-16 sm:py-20">
            <div class="grid gap-8 lg:grid-cols-[1fr_20rem] lg:items-center">
                <div>
                    <p class="text-sm font-semibold tracking-[0.2em] text-brand-strong uppercase">Bienvenidos</p>
                    <div class="mt-3 text-lg leading-relaxed text-ink whitespace-pre-line sm:text-xl">{{ $settings->summary }}</div>
                </div>
                <blockquote class="rounded-card bg-cream p-6 text-center">
                    <p class="text-xs font-semibold tracking-wide text-muted uppercase">Tema de la convención</p>
                    <p class="mt-2 font-display text-2xl font-extrabold text-earth">«{{ $settings->theme }}»</p>
                </blockquote>
            </div>
        </section>
    @else
        <div class="h-12 sm:h-16" aria-hidden="true"></div>
    @endif

    {{-- ===== Reservas de comida ===== --}}
    @if ($featuredOfferings->isNotEmpty())
        <section class="bg-cream py-16 sm:py-20" aria-labelledby="reservas-titulo">
            <div class="container-content">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold tracking-[0.2em] text-brand-strong uppercase">Alimentación</p>
                        <h2 id="reservas-titulo" class="mt-2 text-3xl font-extrabold sm:text-4xl">Reserva tu comida</h2>
                        <p class="mt-2 max-w-xl text-muted">Asegura tu plato con anticipación y recógelo con tu código QR. Lo recaudado apoya la obra.</p>
                    </div>
                    <x-ui.button href="{{ route('dishes.index') }}">Reservar ahora <x-ui.icon name="arrow-right" class="size-5" /></x-ui.button>
                </div>

                <ul class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($featuredOfferings as $offering)
                        <li class="group overflow-hidden rounded-card bg-white shadow-card transition hover:-translate-y-1 hover:shadow-xl">
                            <a href="{{ route('dishes.index') }}" class="block">
                                @if ($offering->dish->imageUrl())
                                    <img src="{{ $offering->dish->imageUrl() }}" alt="{{ $offering->dish->image_alt ?? '' }}" width="640" height="400" class="aspect-16/10 w-full object-cover" loading="lazy" decoding="async">
                                @else
                                    <div class="flex aspect-16/10 items-center justify-center bg-linear-to-br from-brand/20 to-cream text-brand-strong"><x-ui.icon name="utensils" class="size-12" /></div>
                                @endif
                                <div class="flex items-start justify-between gap-3 p-5">
                                    <div>
                                        <h3 class="font-sans text-lg font-bold text-ink group-hover:text-brand-strong">{{ $offering->dish->name }}</h3>
                                        <p class="text-sm text-muted">{{ $offering->dateLabel() }}</p>
                                    </div>
                                    <p class="shrink-0 font-display text-xl font-extrabold text-brand-strong">S/ {{ number_format((float) $offering->price, 2) }}</p>
                                </div>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    {{-- ===== Programa ===== --}}
    <section class="container-content py-16 sm:py-20" aria-labelledby="programa-titulo">
        <div class="grid gap-10 lg:grid-cols-[20rem_1fr]">
            <div>
                <p class="text-sm font-semibold tracking-[0.2em] text-brand-strong uppercase">Programa</p>
                <h2 id="programa-titulo" class="mt-2 text-3xl font-extrabold sm:text-4xl">Cuatro días de bendición</h2>
                <p class="mt-3 text-muted">Consulta horarios, predicadores y actividades de cada jornada.</p>
                <x-ui.button href="{{ route('program') }}" variant="secondary" class="mt-6">Programa completo <x-ui.icon name="arrow-right" class="size-5" /></x-ui.button>
            </div>

            @if ($nextDay)
                <div class="rounded-card border border-line bg-white">
                    <h3 class="border-b border-line px-5 py-4 font-display text-lg font-bold text-earth">{{ $nextDay->label() }}</h3>
                    <ol class="divide-y divide-line">
                        @foreach ($nextDay->items as $item)
                            <li class="flex gap-4 px-5 py-4">
                                <span class="w-24 shrink-0 font-semibold tabular-nums text-brand-strong">{{ $item->timeLabel() }}</span>
                                <span>
                                    <span class="block font-semibold">{{ $item->title }}</span>
                                    @if ($item->speaker)<span class="text-sm text-muted">{{ $item->speaker }}</span>@endif
                                </span>
                            </li>
                        @endforeach
                    </ol>
                </div>
            @else
                <div class="flex items-center gap-4 rounded-card border border-dashed border-stone-300 bg-white p-8">
                    <x-ui.icon name="list" class="size-10 text-brand-strong" />
                    <p class="text-muted">El programa detallado se publicará en cuanto la organización lo confirme.</p>
                </div>
            @endif
        </div>
    </section>

    {{-- ===== Accesos rápidos ===== --}}
    <section class="bg-stone-50 py-16 sm:py-20" aria-labelledby="accesos">
        <div class="container-content">
            <h2 id="accesos" class="text-3xl font-extrabold sm:text-4xl">Todo para tu visita</h2>
            @php
                $shortcuts = [
                    ['route' => 'venue', 'icon' => 'map-pin', 'title' => 'Sede y cómo llegar', 'text' => 'Ubicación, mapa e indicaciones.'],
                    ['route' => 'dishes.index', 'icon' => 'utensils', 'title' => 'Reservas de comida', 'text' => 'Elige tu plato y recógelo con QR.'],
                    ['route' => 'donations', 'icon' => 'heart', 'title' => 'Donaciones', 'text' => 'Métodos oficiales para tu aporte.'],
                    ['route' => 'accommodations', 'icon' => 'bed', 'title' => 'Hospedaje', 'text' => 'Opciones de alojamiento en la ciudad.'],
                    ['route' => 'tourism', 'icon' => 'mountain', 'title' => 'Descubre Huaraz', 'text' => 'Lagunas, nevados y cultura andina.'],
                    ['route' => 'contact', 'icon' => 'chat', 'title' => 'Contacto', 'text' => 'Teléfonos, WhatsApp y preguntas frecuentes.'],
                ];
            @endphp
            <ul class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($shortcuts as $shortcut)
                    <li>
                        <a href="{{ route($shortcut['route']) }}" class="group flex h-full items-start gap-4 rounded-card bg-white p-5 shadow-sm ring-1 ring-black/5 transition hover:-translate-y-0.5 hover:shadow-card">
                            <span class="flex size-12 shrink-0 items-center justify-center rounded-control bg-cream text-brand-strong transition group-hover:bg-brand-strong group-hover:text-white">
                                <x-ui.icon :name="$shortcut['icon']" />
                            </span>
                            <span>
                                <span class="block font-display text-lg font-bold text-earth">{{ $shortcut['title'] }}</span>
                                <span class="mt-1 block text-sm text-muted">{{ $shortcut['text'] }}</span>
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- ===== Donaciones ===== --}}
    <section class="relative isolate overflow-hidden bg-earth py-16 text-white sm:py-20" aria-labelledby="donar-titulo">
        <x-ui.mountains class="absolute inset-x-0 bottom-0 -z-10 h-32 text-[#5a2712]" />
        <div class="container-content flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
            <div class="max-w-2xl">
                <h2 id="donar-titulo" class="text-3xl font-extrabold text-white sm:text-4xl">Sé parte de esta obra</h2>
                <p class="mt-3 text-lg text-cream/90">Tu ofrenda ayuda a recibir a miles de familias en {{ $settings->city }}. Aporta únicamente por los medios oficiales publicados.</p>
            </div>
            <a href="{{ route('donations') }}" class="inline-flex min-h-12 shrink-0 items-center gap-2 self-start rounded-control bg-brand px-6 py-3 font-bold text-earth shadow-lg transition hover:bg-white lg:self-auto">
                <x-ui.icon name="heart" class="size-5" /> Quiero donar
            </a>
        </div>
    </section>

    {{-- ===== Turismo ===== --}}
    @if ($places->isNotEmpty())
        <section class="container-content py-16 sm:py-20" aria-labelledby="turismo-titulo">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-sm font-semibold tracking-[0.2em] text-brand-strong uppercase">Turismo</p>
                    <h2 id="turismo-titulo" class="mt-2 text-3xl font-extrabold sm:text-4xl">Descubre {{ $settings->city }}</h2>
                </div>
                <a href="{{ route('tourism') }}" class="font-semibold text-brand-strong underline-offset-4 hover:underline">Ver todos los lugares</a>
            </div>
            <ul class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($places as $place)
                    <li class="group relative overflow-hidden rounded-card bg-earth">
                        @if ($place->imageUrl())
                            <img src="{{ $place->imageUrl() }}" alt="{{ $place->image_alt ?? '' }}" width="800" height="600" class="aspect-4/3 w-full object-cover transition duration-500 group-hover:scale-105" loading="lazy" decoding="async">
                        @else
                            <div class="aspect-4/3 bg-linear-to-br from-earth to-navy"></div>
                        @endif
                        <div class="absolute inset-0 flex flex-col justify-end bg-linear-to-t from-black/75 via-black/20 to-transparent p-5 text-white">
                            <h3 class="font-display text-xl font-bold text-white">{{ $place->name }}</h3>
                            @if ($place->distance_note)<p class="text-sm text-cream/90">{{ $place->distance_note }}</p>@endif
                        </div>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    {{-- ===== Preguntas frecuentes ===== --}}
    @if ($faqs->isNotEmpty())
        <section class="bg-stone-50 py-16 sm:py-20" aria-labelledby="preguntas">
            <div class="container-content grid gap-8 lg:grid-cols-[20rem_1fr]">
                <div>
                    <h2 id="preguntas" class="text-3xl font-extrabold sm:text-4xl">Preguntas frecuentes</h2>
                    <a href="{{ route('contact') }}" class="mt-4 inline-block font-semibold text-brand-strong underline-offset-4 hover:underline">Ver todas y contactar</a>
                </div>
                @include('pages.partials.faq-list', ['faqs' => $faqs])
            </div>
        </section>
    @endif
</x-layouts.public>
