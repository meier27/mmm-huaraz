<x-layouts.public :title="$event->title" :event="$event" :description="$event->summary ?: collect([$event->title, $event->dateLabel()])->filter()->join(' · ')">
    {{--
        ===== Portada =====
        Fondo: la imagen del evento (Panel › Eventos) o, si no hay, el degradado con
        montañas. Título, texto y botones se muestran u ocultan por separado; fechas y
        lugar van en la barra de datos.
    --}}
    @php
        $heroImage = $event->imageUrl();
        $heroButton = $event->hero_button_label && $event->hero_button_url
            ? ['label' => $event->hero_button_label, 'external' => str_starts_with($event->hero_button_url, 'https://'),
               'url' => str_starts_with($event->hero_button_url, 'https://') ? $event->hero_button_url : url($event->hero_button_url)]
            : null;
        // Fechas y lugar no van sobre la imagen: ya están en la barra de datos de abajo.
        $hasHeroText = $event->hero_show_title || $event->hero_text;
        $moduleButtons = $event->hero_show_buttons && ($event->has_program || $event->has_food || $event->has_venues);
    @endphp
    <section @class(['relative isolate overflow-hidden text-white', 'bg-primary-deep' => $heroImage, 'bg-linear-to-br from-primary via-primary-soft to-primary-deep' => ! $heroImage])>
        @if ($heroImage)
            <img src="{{ $heroImage }}" alt="{{ $event->image_alt ?? '' }}" width="1920" height="900" fetchpriority="high" decoding="async"
                 class="absolute inset-0 -z-20 size-full object-cover">
            @if ($hasHeroText)
                {{-- Velo solo detrás del texto (izquierda) --}}
                <div class="absolute inset-0 -z-10 bg-linear-to-r from-black/65 via-black/30 to-transparent" aria-hidden="true"></div>
            @endif
            <div class="absolute inset-x-0 bottom-0 -z-10 h-40 bg-linear-to-t from-black/40 to-transparent" aria-hidden="true"></div>
        @else
            <div class="absolute -top-24 -right-24 -z-10 size-112 rounded-full bg-brand/30 blur-3xl" aria-hidden="true"></div>
            <x-ui.mountains class="absolute inset-x-0 bottom-0 -z-10 h-40 text-primary-deep sm:h-60" />
        @endif

        <div @class(['container-content flex flex-col justify-center pt-20 pb-36 sm:pt-28 sm:pb-48', 'min-h-136 sm:min-h-160' => $heroImage])>
            @if ($event->hero_show_title)
                @if ($event->organization || $event->city)
                    <p class="mb-4 text-sm font-semibold tracking-[0.2em] text-brand uppercase">{{ collect([$event->organization, $event->city])->filter()->join(' · ') }}</p>
                @endif
                <h1 class="max-w-4xl font-display text-4xl leading-tight font-extrabold text-white [text-shadow:0_2px_16px_rgb(0_0_0/0.35)] sm:text-6xl lg:text-7xl">
                    {{ $event->title }}
                    @if ($event->theme)<span class="mt-2 block text-brand">«{{ $event->theme }}»</span>@endif
                </h1>
            @else
                <h1 class="sr-only">{{ $event->title }}@if ($event->theme) «{{ $event->theme }}»@endif @if ($event->hasDates())· {{ $event->dateLabel() }}@endif</h1>
            @endif

            @if ($event->hero_text)
                <p @class(['max-w-2xl text-xl font-semibold text-white [text-shadow:0_1px_10px_rgb(0_0_0/0.4)] sm:text-2xl', 'mt-5' => $event->hero_show_title])>{{ $event->hero_text }}</p>
            @endif

            @if ($heroButton || $moduleButtons)
                <div @class(['flex flex-wrap gap-3', 'mt-9' => $hasHeroText, 'mt-auto pt-40 sm:pt-64' => ! $hasHeroText])>
                    @if ($heroButton)
                        <a href="{{ $heroButton['url'] }}" @if ($heroButton['external']) target="_blank" rel="noopener" @endif
                           class="inline-flex min-h-12 items-center gap-2 rounded-control bg-brand px-6 py-3 font-bold text-primary-deep shadow-lg transition hover:bg-white">
                            {{ $heroButton['label'] }} <x-ui.icon :name="$heroButton['external'] ? 'external' : 'arrow-right'" class="size-5" />
                        </a>
                    @endif
                    @if ($event->hero_show_buttons)
                        @if ($event->has_program)
                            <a href="{{ route('events.program', $event) }}" @class(['inline-flex min-h-12 items-center gap-2 rounded-control px-6 py-3 font-bold transition',
                                    'bg-brand text-primary-deep shadow-lg hover:bg-white' => ! $heroButton, 'border border-white/50 text-white hover:bg-white/10' => $heroButton])>
                                Ver programa <x-ui.icon name="arrow-right" class="size-5" />
                            </a>
                        @elseif ($event->has_food)
                            <a href="{{ route('events.reservations', $event) }}" class="inline-flex min-h-12 items-center gap-2 rounded-control border border-white/50 px-6 py-3 font-bold text-white transition hover:bg-white/10">
                                {{ $event->reservationsTitle() }} <x-ui.icon name="arrow-right" class="size-5" />
                            </a>
                        @endif
                        @if ($event->has_venues)
                            <a href="{{ route('events.venue', $event) }}" class="inline-flex min-h-12 items-center gap-2 rounded-control border border-white/50 px-6 py-3 font-bold text-white transition hover:bg-white/10">
                                Cómo llegar
                            </a>
                        @endif
                    @endif
                </div>
            @endif
        </div>
    </section>

    {{-- ===== Datos clave (solo los que tienen valor) ===== --}}
    @php
        $facts = collect([
            ['icon' => 'calendar', 'label' => 'Fechas', 'value' => $event->dateLabel()],
            ['icon' => 'map-pin', 'label' => 'Lugar', 'value' => collect([$mainVenue?->name ?? $event->location, $event->city])->filter()->join(', ')],
            ['icon' => 'clock', 'label' => 'Cuenta atrás', 'value' => $event->countdownLabel()],
        ])->filter(fn ($fact) => filled($fact['value']));
    @endphp
    @if ($facts->isNotEmpty())
    <section class="relative z-30 -mt-14 sm:-mt-16" aria-label="Datos clave">
        <div class="container-content">
            <dl @class(['grid gap-3 rounded-card bg-white p-3 shadow-xl ring-1 ring-black/5 sm:p-4', 'sm:grid-cols-3' => $facts->count() === 3, 'sm:max-w-3xl sm:grid-cols-2' => $facts->count() === 2, 'sm:max-w-md' => $facts->count() === 1])>
                @foreach ($facts as $fact)
                    <div class="flex items-center gap-4 rounded-control p-3">
                        <span class="flex size-12 shrink-0 items-center justify-center rounded-full bg-cream text-brand-strong">
                            <x-ui.icon :name="$fact['icon']" />
                        </span>
                        <div>
                            <dt class="text-xs font-semibold tracking-wide text-muted uppercase">{{ $fact['label'] }}</dt>
                            <dd class="font-display text-base font-bold text-primary sm:text-lg">{{ $fact['value'] }}</dd>
                        </div>
                    </div>
                @endforeach
            </dl>
        </div>
    </section>
    @endif

    {{-- ===== Bienvenida ===== --}}
    @if ($event->body)
        <section class="container-content py-16 sm:py-20">
            <div class="grid gap-8 lg:grid-cols-[1fr_20rem] lg:items-center">
                <div>
                    <p class="text-sm font-semibold tracking-[0.2em] text-brand-strong uppercase">Bienvenidos</p>
                    <div class="rich-text mt-3">{{ $event->renderedBody() }}</div>
                </div>
                @if ($event->theme)
                    <blockquote class="rounded-card bg-cream p-6 text-center">
                        <p class="text-xs font-semibold tracking-wide text-muted uppercase">Tema</p>
                        <p class="mt-2 font-display text-2xl font-extrabold text-primary">«{{ $event->theme }}»</p>
                    </blockquote>
                @endif
            </div>
        </section>
    @else
        <div class="h-12 sm:h-16" aria-hidden="true"></div>
    @endif

    {{-- ===== Programa (destacado: va primero) ===== --}}
    @if ($event->has_program)
    <section class="container-content py-12 sm:py-16" aria-labelledby="programa-titulo">
        <div class="relative isolate overflow-hidden rounded-card bg-linear-to-br from-primary-deep via-primary to-[#5b3b26] px-6 py-10 text-white shadow-[0_30px_60px_-30px_rgb(57_49_45/0.7)] sm:px-10 sm:py-14"
             x-data="{ day: {{ $nextDay?->id ?? 'null' }} }">
            <div class="absolute -top-24 -right-24 -z-10 size-96 rounded-full bg-brand/25 blur-3xl" aria-hidden="true"></div>
            <x-ui.mountains class="absolute inset-x-0 bottom-0 -z-10 h-28 text-white/5" />

            <div class="grid gap-10 lg:grid-cols-[18rem_1fr] lg:gap-14">
                <div>
                    <x-ui.section-label :dark="true">Programa</x-ui.section-label>
                    <h2 id="programa-titulo" class="mt-3 text-4xl leading-tight font-extrabold text-white sm:text-5xl">Días de bendición</h2>
                    <p class="mt-4 text-cream/80">Consulta horarios, predicadores y actividades de cada jornada.</p>
                    <a href="{{ route('events.program', $event) }}" class="mt-7 inline-flex min-h-12 items-center gap-2 rounded-control bg-brand px-6 py-3 font-bold text-primary-deep shadow-lg transition hover:bg-white">
                        Programa completo <x-ui.icon name="arrow-right" class="size-5" />
                    </a>
                </div>

                @if ($programDays->isNotEmpty())
                    {{-- min-w-0: la fila de pestañas desplazable no ensancha la columna --}}
                    <div class="min-w-0">
                        {{-- Pestañas por día --}}
                        @if ($programDays->count() > 1)
                            <div class="flex gap-2 overflow-x-auto pb-1" role="tablist" aria-label="Días del programa">
                                @foreach ($programDays as $day)
                                    <button type="button" role="tab" id="tab-dia-{{ $day->id }}" aria-controls="dia-{{ $day->id }}"
                                            x-on:click="day = {{ $day->id }}"
                                            x-bind:aria-selected="(day === {{ $day->id }}).toString()" aria-selected="{{ $day->is($nextDay) ? 'true' : 'false' }}"
                                            @class(['shrink-0 rounded-control px-4 py-2.5 text-left transition', 'bg-white text-primary-deep' => $day->is($nextDay), 'bg-white/10 text-cream hover:bg-white/20' => ! $day->is($nextDay)])
                                            x-bind:class="{ 'bg-white text-primary-deep': day === {{ $day->id }}, 'bg-white/10 text-cream hover:bg-white/20': day !== {{ $day->id }} }">
                                        <span class="block text-xs font-semibold tracking-wide uppercase opacity-70">{{ $day->date->translatedFormat('l') }}</span>
                                        <span class="block font-display text-lg leading-tight font-bold">{{ $day->date->translatedFormat('j M') }}</span>
                                    </button>
                                @endforeach
                            </div>
                        @endif

                        {{-- Línea de tiempo del día --}}
                        @foreach ($programDays as $day)
                            <div id="dia-{{ $day->id }}" role="tabpanel" aria-labelledby="tab-dia-{{ $day->id }}"
                                 @class(['mt-5', 'hidden' => ! $day->is($nextDay)])
                                 x-bind:class="{ 'hidden': day !== {{ $day->id }} }">
                                <p class="font-display text-xl font-bold text-white">{{ $day->label() }}@if ($day->title) <span class="font-sans text-base font-normal text-cream/70">· {{ $day->title }}</span>@endif</p>
                                <ol class="relative mt-5 space-y-3 border-s border-white/20 ps-6">
                                    @foreach ($day->items as $item)
                                        <li class="relative rounded-control bg-white/5 p-4 ring-1 ring-white/10 transition hover:bg-white/10 sm:flex sm:items-center sm:gap-5">
                                            <span class="absolute top-6 -left-[1.95rem] size-3 rounded-full bg-accent-light ring-4 ring-primary" aria-hidden="true"></span>
                                            <span class="inline-flex shrink-0 rounded-full bg-accent-light/15 px-3 py-1 text-sm font-bold tabular-nums text-accent-light sm:w-36 sm:justify-center">{{ $item->timeLabel() }}</span>
                                            <span class="mt-2 block sm:mt-0">
                                                <span class="block font-semibold text-white">{{ $item->title }}</span>
                                                @if ($item->speaker)<span class="text-sm text-cream/70">{{ $item->speaker }}</span>@endif
                                            </span>
                                        </li>
                                    @endforeach
                                </ol>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="flex items-center gap-4 self-center rounded-card bg-white/5 p-8 ring-1 ring-white/10">
                        <x-ui.icon name="list" class="size-10 shrink-0 text-accent-light" />
                        <p class="text-cream/80">El programa detallado se publicará en cuanto la organización lo confirme.</p>
                    </div>
                @endif
            </div>
        </div>
    </section>
    @endif

    {{-- ===== Actividad solidaria (venta de comidas) ===== --}}
    @if ($featuredOfferings->isNotEmpty())
        <section class="py-16 sm:py-20" aria-labelledby="reservas-titulo">
            <div class="container-content">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold tracking-[0.2em] text-brand-strong uppercase">Apoya la obra</p>
                        <h2 id="reservas-titulo" class="mt-2 text-3xl font-extrabold sm:text-4xl">{{ $event->reservationsTitle() }}</h2>
                        <p class="mt-2 max-w-xl text-muted">Platos preparados por los hermanos{{ $event->city ? ' de '.$event->city : '' }}. Reserva con anticipación y recoge tu pedido con tu código QR: lo recaudado apoya la obra.</p>
                    </div>
                    <x-ui.button href="{{ route('events.reservations', $event) }}">Reservar mi plato <x-ui.icon name="arrow-right" class="size-5" /></x-ui.button>
                </div>

                <ul class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($featuredOfferings as $offering)
                        <li class="group overflow-hidden rounded-card bg-white shadow-card transition hover:-translate-y-1 hover:shadow-xl">
                            <a href="{{ route('events.reservations', $event) }}" class="block">
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

    {{-- ===== Accesos rápidos ===== --}}
    <section class="py-16 sm:py-20" aria-labelledby="accesos">
        <div class="container-content">
            <h2 id="accesos" class="text-3xl font-extrabold sm:text-4xl">Todo para tu visita</h2>
            @php
                // Solo los módulos activos del evento.
                $shortcuts = collect([
                    ['module' => 'venues', 'url' => route('events.venue', $event), 'icon' => 'map-pin', 'title' => 'Sede y cómo llegar', 'text' => 'Ubicación, mapa e indicaciones.'],
                    ['module' => 'food', 'url' => route('events.reservations', $event), 'icon' => 'utensils', 'title' => 'Actividad solidaria', 'text' => 'Reserva platos preparados por los hermanos.'],
                    ['module' => 'donations', 'url' => route('events.donations', $event), 'icon' => 'heart', 'title' => 'Donaciones', 'text' => 'Métodos oficiales para tu aporte.'],
                    ['module' => 'accommodations', 'url' => route('events.accommodations', $event), 'icon' => 'bed', 'title' => 'Hospedaje', 'text' => 'Opciones de alojamiento en la ciudad.'],
                    ['module' => 'tourism', 'url' => route('events.tourism', $event), 'icon' => 'mountain', 'title' => $event->city ? 'Descubre '.$event->city : 'Turismo', 'text' => 'Lugares para conocer durante tu visita.'],
                    ['module' => null, 'url' => route('contact'), 'icon' => 'chat', 'title' => 'Contacto', 'text' => 'Teléfonos, WhatsApp y preguntas frecuentes.'],
                ])->filter(fn ($s) => $s['module'] === null || $event->hasModule($s['module']));
            @endphp
            <ul class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($shortcuts as $shortcut)
                    <li>
                        <a href="{{ $shortcut['url'] }}" class="group flex h-full items-start gap-4 rounded-card bg-white p-5 shadow-sm ring-1 ring-black/5 transition hover:-translate-y-0.5 hover:shadow-card">
                            <span class="flex size-12 shrink-0 items-center justify-center rounded-control bg-cream text-brand-strong transition group-hover:bg-brand-strong group-hover:text-white">
                                <x-ui.icon :name="$shortcut['icon']" />
                            </span>
                            <span>
                                <span class="block font-display text-lg font-bold text-primary">{{ $shortcut['title'] }}</span>
                                <span class="mt-1 block text-sm text-muted">{{ $shortcut['text'] }}</span>
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- ===== Donaciones ===== --}}
    @if ($event->has_donations)
    <section class="container-content py-10 sm:py-14" aria-labelledby="donar-titulo">
        <div class="relative isolate mx-auto flex max-w-5xl flex-col gap-6 overflow-hidden rounded-card bg-primary px-7 py-10 text-white shadow-[0_30px_60px_-30px_rgb(57_49_45/0.6)] sm:px-10 lg:flex-row lg:items-center lg:justify-between">
            <x-ui.mountains class="absolute inset-x-0 bottom-0 -z-10 h-24 text-primary-soft/70" />
            <div class="max-w-2xl">
                <h2 id="donar-titulo" class="text-3xl font-extrabold text-white sm:text-4xl">Sé parte de esta obra</h2>
                <p class="mt-3 text-lg text-cream/90">Tu ofrenda hace posible este evento. Aporta únicamente por los medios oficiales publicados.</p>
            </div>
            <a href="{{ route('events.donations', $event) }}" class="inline-flex min-h-12 shrink-0 items-center gap-2 self-start rounded-control bg-brand px-6 py-3 font-bold text-primary shadow-lg transition hover:bg-white lg:self-auto">
                <x-ui.icon name="heart" class="size-5" /> Quiero donar
            </a>
        </div>
    </section>
    @endif

    {{-- ===== Turismo ===== --}}
    @if ($places->isNotEmpty())
        <section class="container-content py-16 sm:py-20" aria-labelledby="turismo-titulo">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-sm font-semibold tracking-[0.2em] text-brand-strong uppercase">Turismo</p>
                    <h2 id="turismo-titulo" class="mt-2 text-3xl font-extrabold sm:text-4xl">{{ $event->city ? 'Descubre '.$event->city : 'Turismo' }}</h2>
                </div>
                <a href="{{ route('events.tourism', $event) }}" class="font-semibold text-brand-strong underline-offset-4 hover:underline">Ver todos los lugares</a>
            </div>
            <ul class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($places as $place)
                    <li class="group relative overflow-hidden rounded-card bg-primary">
                        @if ($place->imageUrl())
                            <img src="{{ $place->imageUrl() }}" alt="{{ $place->image_alt ?? '' }}" width="800" height="600" class="aspect-4/3 w-full object-cover transition duration-500 group-hover:scale-105" loading="lazy" decoding="async">
                        @else
                            <div class="aspect-4/3 bg-linear-to-br from-primary to-primary-deep"></div>
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
</x-layouts.public>
