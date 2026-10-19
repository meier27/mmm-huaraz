<x-layouts.public :overlay="true">
    {{-- ===== 1. Portada: carrusel de banners o portada predeterminada ===== --}}
    @if ($banners->isNotEmpty())
        <h1 class="sr-only">{{ $site->church_name }}</h1>
        @include('pages.partials.banner-carousel', ['banners' => $banners])
    @else
        <section class="relative isolate overflow-hidden bg-primary text-white">
            <div class="absolute -top-32 -right-24 -z-10 size-112 rounded-full bg-brand/20 blur-3xl" aria-hidden="true"></div>
            <x-ui.mountains class="absolute inset-x-0 bottom-0 -z-10 h-40 text-primary-deep/50 sm:h-56" />

            <div class="container-content pt-40 pb-44 sm:pt-48 sm:pb-52">
                <p class="text-sm font-semibold tracking-[0.2em] text-brand uppercase">Bienvenidos</p>
                <h1 class="mt-4 max-w-3xl font-display text-4xl leading-tight font-bold text-white sm:text-6xl">{{ $site->church_name }}</h1>
                @if ($site->tagline)
                    <p class="mt-6 max-w-2xl text-lg text-cream/90 sm:text-xl">{{ $site->tagline }}</p>
                @endif
                <div class="mt-9 flex flex-wrap gap-3">
                    <a href="{{ route('teachings.index') }}" class="inline-flex min-h-12 items-center rounded-full bg-brand-strong px-6 py-3 text-sm font-bold tracking-wide text-white uppercase shadow-lg transition hover:bg-brand hover:text-primary">
                        Ver enseñanzas
                    </a>
                    <a href="#visitanos" class="inline-flex min-h-12 items-center rounded-full border border-white/50 px-6 py-3 text-sm font-semibold text-white transition hover:bg-white/10">
                        Planifica tu visita
                    </a>
                </div>
            </div>
        </section>
    @endif

    {{-- ===== 2. Accesos destacados: dos accesos y el próximo evento resaltado ===== --}}
    @php
        $highlightCards = [
            ['url' => route('teachings.index'), 'icon' => 'book', 'label' => 'Palabra', 'title' => 'Enseñanzas', 'text' => 'Predicaciones y estudios'],
            ['url' => route('bethel'), 'icon' => 'tv', 'label' => 'En vivo', 'title' => 'Bethel TV', 'text' => 'Señal del canal oficial'],
        ];
        $upcoming = $featured && ! $featured->isOver() ? $featured : null;
        // Días que faltan (solo con fecha de inicio futura; nada inventado).
        $daysLeft = $upcoming?->starts_on?->isFuture() ? (int) today()->diffInDays($upcoming->starts_on) : null;
    @endphp
    {{-- Va debajo del carrusel, sin montarse encima: los banners (afiches) se ven completos. --}}
    <section class="relative z-30 bg-cream pt-6 sm:pt-8" aria-label="Accesos destacados">
        <div class="container-content">
            <ul class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_1.45fr] lg:gap-4">
                @foreach ($highlightCards as $card)
                    <li>
                        <a href="{{ $card['url'] }}" class="group flex h-full items-center gap-4 rounded-card bg-white px-5 py-5 shadow-[0_18px_40px_-24px_rgb(57_49_45/0.4)] ring-1 ring-black/5 transition hover:-translate-y-0.5 hover:shadow-[0_24px_44px_-22px_rgb(57_49_45/0.45)] sm:px-6 sm:py-6">
                            <span class="inline-flex size-12 shrink-0 items-center justify-center rounded-2xl bg-cream text-brand-strong transition-colors group-hover:bg-brand-strong group-hover:text-white">
                                <x-ui.icon :name="$card['icon']" class="size-6" />
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-[0.7rem] font-bold tracking-[0.16em] text-brand-strong uppercase">{{ $card['label'] }}</span>
                                <span class="mt-0.5 block font-display text-xl leading-snug font-bold text-primary">{{ $card['title'] }}</span>
                                <span class="block text-sm text-muted">{{ $card['text'] }}</span>
                            </span>
                            <x-ui.icon name="arrow-right" class="size-5 shrink-0 text-primary-soft transition group-hover:translate-x-1 group-hover:text-brand-strong" />
                        </a>
                    </li>
                @endforeach

                <li class="sm:col-span-2 lg:col-span-1">
                    @if ($upcoming)
                        <a href="{{ route('events.show', $upcoming) }}"
                           class="group relative isolate flex h-full items-center gap-4 overflow-hidden rounded-card bg-linear-to-br from-primary-deep via-primary to-gradient-end px-5 py-5 text-white shadow-[0_24px_50px_-24px_rgb(57_49_45/0.8)] transition hover:-translate-y-0.5 sm:px-6 sm:py-6">
                            <span class="absolute -top-16 -right-10 -z-10 size-48 rounded-full bg-brand/30 blur-3xl" aria-hidden="true"></span>
                            <x-ui.mountains class="absolute inset-x-0 bottom-0 -z-10 h-12 text-white/5" />
                            <span class="inline-flex size-12 shrink-0 items-center justify-center rounded-2xl bg-accent-light text-primary-deep shadow-lg">
                                <x-ui.icon name="calendar" class="size-6" />
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="flex flex-wrap items-center gap-x-2 gap-y-1 text-[0.7rem] font-bold tracking-[0.16em] text-accent-light uppercase">
                                    Próximo evento
                                    @if ($daysLeft !== null)
                                        <span class="rounded-full bg-white/15 px-2 py-0.5 tracking-normal normal-case text-white">
                                            {{ $daysLeft === 1 ? 'Mañana' : "Faltan {$daysLeft} días" }}
                                        </span>
                                    @endif
                                </span>
                                <span class="mt-0.5 block font-display text-xl leading-tight font-bold text-white sm:text-2xl">
                                    {{ $upcoming->title }}@if ($upcoming->city)<span class="text-accent-light"> · {{ $upcoming->city }}</span>@endif
                                </span>
                                <span class="mt-0.5 block text-sm text-cream/80">{{ $upcoming->dateLabel() ?: 'Conoce los detalles' }}@if ($upcoming->location) · {{ $upcoming->location }}@endif</span>
                            </span>
                            <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-full bg-white/10 ring-1 ring-white/20 transition group-hover:bg-accent-light group-hover:text-primary-deep">
                                <x-ui.icon name="arrow-right" class="size-5 transition group-hover:translate-x-0.5" />
                            </span>
                        </a>
                    @else
                        <a href="{{ route('events.index') }}" class="group flex h-full items-center gap-4 rounded-card bg-white px-5 py-5 shadow-[0_18px_40px_-24px_rgb(57_49_45/0.4)] ring-1 ring-black/5 transition hover:-translate-y-0.5 sm:px-6 sm:py-6">
                            <span class="inline-flex size-12 shrink-0 items-center justify-center rounded-2xl bg-cream text-brand-strong"><x-ui.icon name="calendar" class="size-6" /></span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-[0.7rem] font-bold tracking-[0.16em] text-brand-strong uppercase">Agenda</span>
                                <span class="mt-0.5 block font-display text-xl leading-snug font-bold text-primary">Eventos</span>
                                <span class="block text-sm text-muted">Actividades de la iglesia</span>
                            </span>
                            <x-ui.icon name="arrow-right" class="size-5 shrink-0 text-primary-soft transition group-hover:translate-x-1 group-hover:text-brand-strong" />
                        </a>
                    @endif
                </li>
            </ul>
        </div>
    </section>

    {{-- ===== 3. Nuestra familia ===== --}}
    <section class="bg-cream pt-16 pb-20 sm:pt-20 sm:pb-24" aria-labelledby="familia-titulo">
        <div class="container-content grid gap-12 lg:grid-cols-2 lg:items-center">
            <div>
                <x-ui.section-label>Nuestra familia</x-ui.section-label>
                <h2 id="familia-titulo" class="mt-3 text-3xl leading-tight font-bold sm:text-4xl">{{ $site->tagline ?: 'Una iglesia que te espera con las puertas abiertas' }}</h2>
                @if ($site->welcome_text)
                    <div class="mt-6 text-lg leading-relaxed whitespace-pre-line text-ink/80">{{ $site->welcome_text }}</div>
                @endif
                @if ($verse)
                    <figure class="mt-8 border-s-4 border-brand ps-5">
                        <blockquote class="font-display text-lg leading-relaxed font-semibold text-primary">«{{ $verse->text }}»</blockquote>
                        <figcaption class="mt-2 text-sm text-muted">{{ $verse->reference }}@if ($verse->version) · {{ $verse->version }}@endif</figcaption>
                    </figure>
                @endif
                <a href="{{ route('about') }}" class="mt-8 inline-flex min-h-11 items-center gap-2 rounded-full bg-primary px-6 text-sm font-semibold text-white transition hover:bg-brand-strong">
                    Conócenos <x-ui.icon name="arrow-right" class="size-4" />
                </a>
            </div>

            {{-- Collage de formas orgánicas (fotos de Panel › Fotos «Nuestra familia») --}}
            @if ($gallery->count() === 1)
                <div class="mx-auto aspect-square w-full max-w-md overflow-hidden rounded-[58%_42%_47%_53%/52%_44%_56%_48%] bg-primary/10 shadow-lg">
                    <img src="{{ $gallery[0]['url'] }}" alt="{{ $gallery[0]['alt'] }}" class="size-full object-cover" loading="lazy" decoding="async">
                </div>
            @elseif ($gallery->count() > 1)
                @php
                    $shapes = [
                        'rounded-[58%_42%_47%_53%/52%_44%_56%_48%] col-span-2 row-span-2',
                        'rounded-[46%_54%_60%_40%/55%_45%_55%_45%]',
                        'rounded-[62%_38%_44%_56%/48%_58%_42%_52%]',
                        'rounded-[50%_50%_38%_62%/60%_40%_60%_40%] col-span-2',
                    ];
                @endphp
                <div class="grid grid-cols-4 grid-rows-3 gap-3 sm:gap-4">
                    @foreach ($gallery as $image)
                        <div @class(['overflow-hidden bg-primary/10 shadow-lg', $shapes[$loop->index]])>
                            <img src="{{ $image['url'] }}" alt="{{ $image['alt'] }}" class="size-full min-h-28 object-cover" loading="lazy" decoding="async">
                        </div>
                    @endforeach
                </div>
            @else
                <div class="relative mx-auto aspect-square w-full max-w-md" aria-hidden="true">
                    <div class="absolute inset-0 rounded-[58%_42%_47%_53%/52%_44%_56%_48%] bg-primary"></div>
                    <div class="absolute inset-8 rounded-[46%_54%_60%_40%/55%_45%_55%_45%] bg-brand-strong/80"></div>
                    <x-ui.mountains class="absolute inset-x-8 bottom-16 h-32 text-primary-deep/70" />
                    <span class="absolute inset-0 flex items-center justify-center font-display text-7xl font-bold text-cream">MMM</span>
                </div>
            @endif
        </div>

        {{-- Lema del año, discreto, en el espacio final de la sección --}}
        @if ($motto)
            <div class="container-content">
                @include('pages.partials.motto', ['motto' => $motto])
            </div>
        @endif
    </section>

    {{-- ===== 4. Da el siguiente paso: texto a la izquierda, pasos numerados a la derecha ===== --}}
    <section class="bg-sand py-20 sm:py-28" aria-labelledby="pasos-titulo">
        <div class="container-content grid gap-12 lg:grid-cols-[minmax(0,5fr)_minmax(0,7fr)] lg:items-center lg:gap-16">
            <div>
                <x-ui.section-label>Tus próximos pasos</x-ui.section-label>
                <h2 id="pasos-titulo" class="mt-3 text-4xl leading-tight font-bold sm:text-5xl">Da el siguiente paso</h2>
                <p class="mt-5 max-w-md text-lg leading-relaxed text-muted">
                    Acércate a la Palabra, vive con nosotros cada actividad y escríbenos cuando lo necesites. Hay un lugar para ti y tu familia.
                </p>
                <a href="#visitanos" class="mt-8 inline-flex min-h-12 items-center gap-2 rounded-full bg-primary px-6 py-3 text-sm font-bold text-white transition hover:bg-brand-strong">
                    Planifica tu visita <x-ui.icon name="arrow-right" class="size-4" />
                </a>
            </div>

            <ol class="divide-y divide-sand-dark overflow-hidden rounded-card bg-white shadow-[0_24px_50px_-30px_rgb(57_49_45/0.45)] ring-1 ring-black/5">
                @foreach ([
                    ['url' => route('bible.index'), 'icon' => 'book', 'title' => 'Lee la Biblia', 'text' => 'Los 66 libros, capítulo por capítulo, y búsqueda por palabras.'],
                    ['url' => route('events.index'), 'icon' => 'calendar', 'title' => 'Participa', 'text' => 'Convenciones, campañas, vigilias y actividades de la iglesia.'],
                    ['url' => route('contact'), 'icon' => 'chat', 'title' => 'Escríbenos', 'text' => 'Contacto, horarios de culto y preguntas frecuentes.'],
                ] as $step)
                    <li>
                        <a href="{{ $step['url'] }}" class="group flex items-center gap-5 p-6 transition-colors hover:bg-cream-light sm:gap-7 sm:p-8">
                            <span class="hidden font-display text-5xl leading-none font-black text-sand-dark tabular-nums transition-colors group-hover:text-brand sm:block" aria-hidden="true">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                            <span class="flex size-12 shrink-0 items-center justify-center rounded-full bg-cream text-brand-strong transition-colors group-hover:bg-brand-strong group-hover:text-white">
                                <x-ui.icon :name="$step['icon']" class="size-5" />
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block font-display text-xl font-bold text-primary">{{ $step['title'] }}</span>
                                <span class="mt-1 block text-sm leading-relaxed text-muted">{{ $step['text'] }}</span>
                            </span>
                            <span class="flex size-10 shrink-0 items-center justify-center rounded-full text-primary-soft ring-1 ring-sand-dark transition group-hover:translate-x-1 group-hover:bg-primary group-hover:text-white group-hover:ring-primary">
                                <x-ui.icon name="arrow-right" class="size-4" />
                            </span>
                        </a>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- ===== 5. Enseñanzas recientes (sección oscura con tarjetas de imagen) ===== --}}
    @if ($teachings->isNotEmpty())
        <section class="relative bg-primary py-24 text-white sm:py-32" aria-labelledby="ensenanzas-titulo">
            <x-ui.curve position="top" class="text-sand" />
            <x-ui.curve position="bottom" class="text-cream" />
            <div class="container-content">
                <div class="text-center">
                    <x-ui.section-label :dark="true">Palabra</x-ui.section-label>
                    <h2 id="ensenanzas-titulo" class="mt-3 text-3xl font-bold text-white sm:text-4xl">Enseñanzas recientes</h2>
                    <p class="mx-auto mt-3 max-w-xl text-cream/80">Predicaciones, estudios bíblicos y devocionales para tu crecimiento espiritual.</p>
                </div>
                <ul class="mt-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($teachings as $teaching)
                        @php
                            $cover = $teaching->imageUrl() ?? ($teaching->video_id ? "https://i.ytimg.com/vi/{$teaching->video_id}/hqdefault.jpg" : null);
                        @endphp
                        <li>
                            <a href="{{ route('teachings.show', $teaching->slug) }}" class="group relative flex aspect-16/10 overflow-hidden rounded-card bg-black/30 ring-1 ring-white/10">
                                @if ($cover)
                                    <img src="{{ $cover }}" alt="" class="absolute inset-0 size-full object-cover opacity-60 transition duration-500 group-hover:scale-105 group-hover:opacity-75" loading="lazy" decoding="async">
                                @else
                                    <x-ui.mountains class="absolute inset-x-0 bottom-0 h-24 text-primary-deep/60" />
                                @endif
                                <div class="absolute inset-0 bg-linear-to-t from-black/80 via-black/40 to-transparent"></div>
                                <div class="relative mt-auto flex w-full items-end justify-between gap-3 p-5">
                                    <span>
                                        <span class="block text-xs font-semibold tracking-wide text-brand uppercase">{{ $teaching->category->label() }} · {{ $teaching->published_on->translatedFormat('j M Y') }}</span>
                                        <span class="mt-1 block font-display text-lg leading-snug font-bold text-white">{{ $teaching->title }}</span>
                                    </span>
                                    <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-brand-strong text-white transition group-hover:bg-brand group-hover:text-primary">
                                        <x-ui.icon name="chevron-right" class="size-5" />
                                    </span>
                                </div>
                            </a>
                        </li>
                    @endforeach
                </ul>
                <div class="mt-10 text-center">
                    <a href="{{ route('teachings.index') }}" class="inline-flex min-h-11 items-center gap-2 rounded-full px-6 text-sm font-bold tracking-wide text-brand uppercase transition hover:bg-white/10">
                        Ver más enseñanzas <x-ui.icon name="arrow-right" class="size-4" />
                    </a>
                </div>
            </div>
        </section>
    @endif

    {{-- ===== 6. Visítanos: horarios y contacto + evento ===== --}}
    @php
        $spotlight = ($featured && ! $featured->isOver()) ? $featured : $nextEvent;
    @endphp
    <section id="visitanos" class="scroll-mt-20 bg-cream py-20 sm:py-24" aria-labelledby="visitanos-titulo">
        <div class="container-content grid gap-12 lg:grid-cols-2 lg:items-center">
            <div>
                <h2 id="visitanos-titulo" class="text-3xl leading-tight font-bold sm:text-5xl">Visítanos</h2>

                <h3 class="mt-8 font-display text-lg font-bold text-primary">Horario de cultos</h3>
                @if ($site->scheduleLines())
                    <ul class="mt-3 space-y-2">
                        @foreach ($site->scheduleLines() as $line)
                            <li class="flex items-start gap-2"><x-ui.icon name="clock" class="mt-0.5 size-5 text-brand-strong" /> {{ $line }}</li>
                        @endforeach
                    </ul>
                @else
                    <p class="mt-3 text-muted">Los horarios se publicarán aquí en cuanto la iglesia los confirme.</p>
                @endif

                <div class="mt-8">
                    @include('pages.partials.church-location')
                </div>

                <h3 class="mt-8 font-display text-lg font-bold text-primary">¿Tienes preguntas?</h3>
                <p class="mt-2 text-muted">Escríbenos y te ayudamos a planificar tu visita.</p>
                <div class="mt-5 flex flex-wrap gap-3">
                    @if ($site->whatsappUrl())
                        <a href="{{ $site->whatsappUrl() }}" target="_blank" rel="noopener" class="inline-flex min-h-11 items-center gap-2 rounded-full bg-primary px-6 text-sm font-semibold text-white transition hover:bg-brand-strong">
                            Escríbenos por WhatsApp<span class="sr-only"> (pestaña nueva)</span>
                        </a>
                    @endif
                    <a href="{{ route('contact') }}" class="inline-flex min-h-11 items-center gap-2 rounded-full border border-primary px-6 text-sm font-semibold text-primary transition hover:bg-white">
                        Contacto
                    </a>
                </div>
            </div>

            @if ($spotlight)
                <a href="{{ route('events.show', $spotlight) }}" class="group block overflow-hidden rounded-card bg-white shadow-xl ring-1 ring-black/5 transition hover:-translate-y-1">
                    <div class="relative aspect-video bg-primary">
                        @if ($spotlight->imageUrl())
                            <img src="{{ $spotlight->imageUrl() }}" alt="{{ $spotlight->image_alt ?? '' }}" class="size-full object-cover" loading="lazy" decoding="async">
                        @else
                            <div class="absolute inset-0 bg-linear-to-br from-primary to-primary-deep"></div>
                            <x-ui.mountains class="absolute inset-x-0 bottom-0 h-28 text-primary-deep" />
                        @endif
                        @if ($spotlight->countdownLabel())
                            <span class="absolute top-4 left-4 rounded-full bg-white/95 px-3 py-1 text-xs font-bold text-primary">{{ $spotlight->countdownLabel() }}</span>
                        @endif
                    </div>
                    <div class="p-6">
                        <p class="text-xs font-semibold tracking-wide text-brand-strong uppercase">Próximo evento</p>
                        <p class="mt-1 font-display text-2xl font-bold text-primary">{{ $spotlight->title }}</p>
                        <p class="mt-1 text-muted">{{ collect([$spotlight->dateLabel(), $spotlight->location ?? $spotlight->city])->filter()->join(' · ') }}</p>
                        <span class="mt-5 inline-flex items-center gap-1 text-sm font-semibold text-brand-strong">
                            Ver el evento <x-ui.icon name="arrow-right" class="size-4 transition group-hover:translate-x-1" />
                        </span>
                    </div>
                </a>
            @endif
        </div>
    </section>

    {{-- ===== 7. Medios oficiales del movimiento ===== --}}
    <section class="relative bg-sand pt-24 pb-16 sm:pt-32" aria-labelledby="medios-titulo">
        <x-ui.curve position="top" class="text-cream" />
        <div class="container-content">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <x-ui.section-label>Medios</x-ui.section-label>
                    <h2 id="medios-titulo" class="mt-3 text-3xl font-bold sm:text-4xl">Mantente conectado</h2>
                </div>
            </div>

            @if ($media->isNotEmpty())
                <ul class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($media as $medium)
                        <li>
                            <a href="{{ $medium->href() }}" @if ($medium->isExternal()) target="_blank" rel="noopener" @endif class="group block text-center">
                                <span class="flex aspect-3/2 items-center justify-center overflow-hidden rounded-card bg-primary shadow-card ring-1 ring-black/5 transition duration-300 group-hover:-translate-y-1 group-hover:shadow-xl"
                                      @if ($medium->background_color) style="background-color: {{ $medium->background_color }}" @endif>
                                    @if ($medium->imageUrl())
                                        {{-- Logotipo a su tamaño (sin ampliar): el color de fondo rellena la tarjeta. --}}
                                        <img src="{{ $medium->imageUrl() }}" alt="{{ $medium->image_alt ?? '' }}" width="250" height="250"
                                             class="h-full max-h-64 w-auto object-contain transition duration-300 group-hover:scale-105" loading="lazy" decoding="async">
                                    @else
                                        <span class="font-display text-2xl font-bold text-white">{{ $medium->name }}</span>
                                    @endif
                                </span>
                                <span class="mt-4 inline-flex items-center gap-1.5 font-display text-lg font-bold text-primary transition group-hover:text-brand-strong">
                                    {{ $medium->name }}
                                    @if ($medium->isExternal())
                                        <x-ui.icon name="external" class="size-4 opacity-60" /><span class="sr-only"> (sitio oficial, pestaña nueva)</span>
                                    @endif
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif

            @if ($site->socialLinks())
                <ul class="mt-10 flex flex-wrap items-center gap-3 border-t border-sand-dark pt-8">
                    <li class="me-2 text-sm text-muted">Síguenos:</li>
                    @foreach ($site->socialLinks() as $network => $url)
                        <li>
                            <a href="{{ $url }}" target="_blank" rel="noopener" class="inline-flex min-h-10 items-center rounded-full border border-primary/30 px-4 text-sm font-semibold text-primary transition hover:bg-white">
                                {{ $network }}<span class="sr-only"> (pestaña nueva)</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </section>
</x-layouts.public>
