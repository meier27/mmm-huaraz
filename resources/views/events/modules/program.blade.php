{{--
    Programa: un día por página. Las pestañas y los botones anterior/siguiente son
    enlaces normales (?dia=AAAA-MM-DD): funcionan sin JavaScript y se pueden compartir.
--}}
<x-layouts.public :title="'Programa · '.$event->title" :event="$event">
    <x-ui.page-intro title="Programa" :eyebrow="collect([$event->title, $event->dateLabel()])->filter()->join(' · ')">
        Actividades del evento día por día.
    </x-ui.page-intro>

    <div class="container-content pb-6">
        @if (! $day || $days->every(fn ($d) => $d->items->isEmpty()))
            <x-ui.empty-state>El programa se publicará aquí en cuanto la organización lo confirme.</x-ui.empty-state>
        @else
            {{-- Días --}}
            <nav aria-label="Días del programa" class="-mx-4 overflow-x-auto px-4 pb-2 sm:mx-0 sm:px-0">
                <ul class="flex gap-2">
                    @foreach ($days as $tab)
                        @php
                            $current = $tab->is($day);
                        @endphp
                        <li class="shrink-0">
                            <a href="{{ route('events.program', $event) }}?dia={{ $tab->date->toDateString() }}#dia"
                               @if ($current) aria-current="page" @endif
                               @class(['flex min-w-28 flex-col rounded-card px-4 py-3 ring-1 transition',
                                       'bg-primary text-white ring-primary shadow-lg' => $current,
                                       'bg-white text-primary ring-sand-dark hover:ring-brand-strong hover:text-brand-strong' => ! $current])>
                                <span @class(['text-xs font-semibold tracking-wide uppercase', 'text-accent-light' => $current, 'text-primary-soft' => ! $current])>{{ $tab->date->translatedFormat('l') }}</span>
                                <span class="font-display text-xl leading-tight font-bold">{{ $tab->date->translatedFormat('j M') }}</span>
                                <span @class(['mt-1 text-xs', 'text-cream/80' => $current, 'text-muted' => ! $current])>{{ trans_choice('{0} Sin actividades|{1} 1 actividad|[2,*] :count actividades', $tab->items->count()) }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>

            {{-- Día seleccionado --}}
            <section id="dia" class="mt-8 scroll-mt-40" aria-labelledby="dia-titulo">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <h2 id="dia-titulo" class="text-3xl font-bold">{{ $day->label() }}</h2>
                        @if ($day->title)
                            <p class="mt-1 font-semibold text-brand-strong">{{ $day->title }}</p>
                        @endif
                    </div>
                    <p class="text-sm text-muted">Día {{ $days->search(fn ($d) => $d->is($day)) + 1 }} de {{ $days->count() }}</p>
                </div>

                @if ($day->items->isEmpty())
                    <p class="mt-6 rounded-card bg-white p-6 text-muted ring-1 ring-sand-dark">Aún no hay actividades publicadas para este día.</p>
                @else
                    @php
                        $isToday = $day->date->isToday();
                        $now = now()->format('H:i:s');
                    @endphp
                    <ol class="mt-6 space-y-4" aria-label="Actividades del {{ $day->label() }}">
                        @foreach ($day->items as $item)
                            @php
                                $live = $isToday && $item->starts_at <= $now && ($item->ends_at ? $now < $item->ends_at : false);
                            @endphp
                            <li class="grid gap-3 sm:grid-cols-[8rem_1fr] sm:gap-6">
                                <div class="flex items-baseline gap-2 sm:flex-col sm:items-end sm:gap-0 sm:pt-5 sm:text-right">
                                    <span class="font-display text-xl font-bold tabular-nums text-primary">{{ substr($item->starts_at, 0, 5) }}</span>
                                    @if ($item->ends_at)
                                        <span class="text-sm tabular-nums text-muted"><span class="sr-only">hasta las </span><span aria-hidden="true">– </span>{{ substr($item->ends_at, 0, 5) }}</span>
                                    @endif
                                </div>
                                <article @class(['relative rounded-card bg-white p-5 shadow-[0_14px_30px_-22px_rgb(57_49_45/0.5)] ring-1 sm:p-6',
                                                 'ring-brand-strong' => $live, 'ring-black/5' => ! $live])>
                                    <span @class(['absolute top-0 bottom-0 left-0 w-1 rounded-l-card', 'bg-brand-strong' => $live, 'bg-accent-light/70' => ! $live]) aria-hidden="true"></span>
                                    @if ($live)
                                        <span class="mb-2 inline-flex items-center gap-1.5 rounded-full bg-brand-strong px-2.5 py-0.5 text-xs font-bold text-white">
                                            <span class="size-1.5 rounded-full bg-white motion-safe:animate-pulse" aria-hidden="true"></span> En curso
                                        </span>
                                    @endif
                                    <h3 class="font-display text-lg font-bold text-primary">{{ $item->title }}</h3>
                                    @if ($item->speaker || $item->venue)
                                        <ul class="mt-2 flex flex-wrap gap-x-5 gap-y-1 text-sm text-muted">
                                            @if ($item->speaker)
                                                <li class="inline-flex items-center gap-1.5"><x-ui.icon name="user" class="size-4 text-brand-strong" /><span class="sr-only">Predica: </span>{{ $item->speaker }}</li>
                                            @endif
                                            @if ($item->venue)
                                                <li class="inline-flex items-center gap-1.5"><x-ui.icon name="map-pin" class="size-4 text-brand-strong" /><span class="sr-only">Lugar: </span>{{ $item->venue->name }}</li>
                                            @endif
                                        </ul>
                                    @endif
                                    @if ($item->description)
                                        <p class="mt-3 text-sm leading-relaxed whitespace-pre-line text-ink/80">{{ $item->description }}</p>
                                    @endif
                                </article>
                            </li>
                        @endforeach
                    </ol>
                @endif

                {{-- Día anterior / siguiente --}}
                @if ($previousDay || $nextDay)
                    <nav aria-label="Cambiar de día" class="mt-10 flex flex-wrap justify-between gap-3 border-t border-sand-dark pt-6">
                        @if ($previousDay)
                            <a href="{{ route('events.program', $event) }}?dia={{ $previousDay->date->toDateString() }}#dia" rel="prev"
                               class="group inline-flex min-h-12 items-center gap-3 rounded-control px-4 py-2 ring-1 ring-sand-dark transition hover:bg-white">
                                <x-ui.icon name="chevron-left" class="size-5 text-brand-strong transition group-hover:-translate-x-0.5" />
                                <span class="text-left"><span class="block text-xs text-muted">Día anterior</span><span class="font-semibold text-primary">{{ $previousDay->label() }}</span></span>
                            </a>
                        @else
                            <span></span>
                        @endif
                        @if ($nextDay)
                            <a href="{{ route('events.program', $event) }}?dia={{ $nextDay->date->toDateString() }}#dia" rel="next"
                               class="group inline-flex min-h-12 items-center gap-3 rounded-control bg-primary px-4 py-2 text-white transition hover:bg-brand-strong">
                                <span class="text-right"><span class="block text-xs text-cream/80">Día siguiente</span><span class="font-semibold">{{ $nextDay->label() }}</span></span>
                                <x-ui.icon name="chevron-right" class="size-5 transition group-hover:translate-x-0.5" />
                            </a>
                        @endif
                    </nav>
                @endif
            </section>
        @endif
    </div>
</x-layouts.public>
