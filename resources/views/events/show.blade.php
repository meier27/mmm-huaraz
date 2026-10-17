<x-layouts.public :title="$event->title" :description="$event->summary ?: collect([$event->title, $event->dateLabel()])->filter()->join(' · ')">
    <article>
        <x-ui.page-intro :title="$event->title" :eyebrow="$event->isPast() ? 'Evento finalizado' : 'Próximo evento'">
            {{ $event->summary }}
        </x-ui.page-intro>

        <div class="container-content grid gap-10 py-10 lg:grid-cols-[1fr_20rem] lg:items-start">
            <div>
                @if ($event->imageUrl())
                    <img src="{{ $event->imageUrl() }}" alt="{{ $event->image_alt ?? '' }}" width="1200" height="675" class="mb-8 aspect-video w-full rounded-card object-cover" decoding="async">
                @endif

                @if ($event->body)
                    <div class="rich-text">{{ $event->renderedBody() }}</div>
                @elseif (! $event->imageUrl())
                    <p class="text-muted">Más detalles en cuanto la iglesia los confirme.</p>
                @endif
            </div>

            <aside class="rounded-card bg-cream p-6 lg:sticky lg:top-24">
                <dl class="space-y-4">
                    @foreach ([
                        ['icon' => 'calendar', 'label' => 'Fecha', 'value' => $event->dateLabel()],
                        ['icon' => 'clock', 'label' => 'Horario', 'value' => $event->time_label],
                        ['icon' => 'map-pin', 'label' => 'Lugar', 'value' => $event->location],
                    ] as $fact)
                        @if ($fact['value'])
                            <div class="flex gap-3">
                                <x-ui.icon :name="$fact['icon']" class="mt-0.5 size-5 text-brand-strong" />
                                <div>
                                    <dt class="text-xs font-semibold tracking-wide text-muted uppercase">{{ $fact['label'] }}</dt>
                                    <dd class="font-semibold">{{ $fact['value'] }}</dd>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </dl>
                @if ($event->external_url)
                    <x-ui.button href="{{ $event->external_url }}" target="_blank" rel="noopener" class="mt-6 w-full">
                        Más información<span class="sr-only"> (se abre en una pestaña nueva)</span>
                    </x-ui.button>
                @endif
            </aside>
        </div>

        <p class="container-content pb-12">
            <a href="{{ route('events.index') }}" class="inline-flex items-center gap-2 font-semibold text-brand-strong underline-offset-4 hover:underline">
                <x-ui.icon name="chevron-left" class="size-5" /> Todos los eventos
            </a>
        </p>
    </article>
</x-layouts.public>
