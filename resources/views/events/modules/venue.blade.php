<x-layouts.public :event="$event" title="Sede y cómo llegar">
    <x-ui.page-intro title="Sede y cómo llegar" :eyebrow="$event->title">
        Ubicación de la convención e indicaciones para llegar.
    </x-ui.page-intro>

    <div class="container-content space-y-10 pb-6">
        @forelse ($venues as $venue)
            <article class="overflow-hidden rounded-card bg-white shadow-[0_24px_50px_-30px_rgb(57_49_45/0.45)] ring-1 ring-black/5" aria-labelledby="sede-{{ $venue->id }}">
                @if ($venue->imageUrl())
                    <img src="{{ $venue->imageUrl() }}" alt="{{ $venue->image_alt ?? '' }}" width="1600" height="600"
                         class="aspect-16/7 w-full object-cover" @if ($loop->first) fetchpriority="high" @else loading="lazy" @endif decoding="async">
                @endif

                <div class="grid lg:grid-cols-[minmax(0,5fr)_minmax(0,7fr)]">
                    <div class="p-6 sm:p-8">
                        <x-ui.section-label>{{ $venue->kind->label() }}</x-ui.section-label>
                        <h2 id="sede-{{ $venue->id }}" class="mt-3 text-3xl font-bold">{{ $venue->name }}</h2>
                        <p class="mt-4 flex items-start gap-2 text-lg">
                            <x-ui.icon name="map-pin" class="mt-1 size-5 shrink-0 text-brand-strong" />
                            <span>{{ $venue->address ?: 'Dirección exacta por confirmar.' }}</span>
                        </p>

                        @if ($venue->directions)
                            <h3 class="mt-6 font-sans text-base font-bold text-primary">Cómo llegar</h3>
                            <p class="mt-2 leading-relaxed whitespace-pre-line text-muted">{{ $venue->directions }}</p>
                        @endif

                        @if ($venue->mapsLink())
                            <a href="{{ $venue->mapsLink() }}" target="_blank" rel="noopener"
                               class="mt-7 inline-flex min-h-12 items-center gap-2 rounded-control bg-brand-strong px-6 py-3 font-bold text-white transition hover:bg-primary">
                                <x-ui.icon name="map-pin" class="size-5" /> Abrir en Google Maps<span class="sr-only"> (se abre en una pestaña nueva)</span>
                            </a>
                        @endif
                    </div>

                    @if ($venue->mapEmbedUrl())
                        <iframe src="{{ $venue->mapEmbedUrl() }}" title="Mapa de {{ $venue->name }}"
                                class="h-80 w-full border-0 lg:h-full lg:min-h-96" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
                    @else
                        <div class="flex min-h-72 items-center justify-center gap-3 bg-cream p-8 text-muted">
                            <x-ui.icon name="map-pin" class="size-6 text-brand-strong" /> El mapa se mostrará cuando se confirme la dirección.
                        </div>
                    @endif
                </div>
            </article>
        @empty
            <x-ui.empty-state>La ubicación se publicará aquí en cuanto la organización la confirme.</x-ui.empty-state>
        @endforelse
    </div>
</x-layouts.public>
