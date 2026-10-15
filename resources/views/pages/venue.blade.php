<x-layouts.public title="Sede y cómo llegar">
    <x-ui.page-intro title="Sede y cómo llegar" :eyebrow="$settings->city">
        Ubicación de la convención e indicaciones para llegar.
    </x-ui.page-intro>

    <div class="container-content space-y-8 py-10">
        @forelse ($venues as $venue)
            <article class="overflow-hidden rounded-card border border-line bg-white" aria-labelledby="sede-{{ $venue->id }}">
                <div class="grid lg:grid-cols-2">
                    <div class="p-5 sm:p-6">
                        <p class="text-sm font-semibold text-brand-strong">{{ $venue->kind->label() }}</p>
                        <h2 id="sede-{{ $venue->id }}" class="mt-1 text-2xl font-bold">{{ $venue->name }}</h2>
                        <p class="mt-2">{{ $venue->address ?: 'Dirección exacta por confirmar.' }}</p>

                        @if ($venue->directions)
                            <h3 class="mt-5 font-sans text-base font-semibold text-ink">Cómo llegar</h3>
                            <p class="mt-1 text-muted whitespace-pre-line">{{ $venue->directions }}</p>
                        @endif

                        @if ($venue->mapsLink())
                            <x-ui.button href="{{ $venue->mapsLink() }}" class="mt-6" target="_blank" rel="noopener">
                                Abrir en Google Maps<span class="sr-only"> (se abre en una pestaña nueva)</span>
                            </x-ui.button>
                        @endif
                    </div>

                    @if ($venue->openStreetMapEmbedUrl())
                        {{-- El mapa solo se carga si el visitante lo pide: ahorra datos móviles y evita rastreo. --}}
                        <div x-data="{ show: false }" class="min-h-72 bg-stone-100">
                            <template x-if="show">
                                <iframe src="{{ $venue->openStreetMapEmbedUrl() }}" title="Mapa de {{ $venue->name }}"
                                        class="h-full min-h-72 w-full border-0" loading="lazy" referrerpolicy="no-referrer"></iframe>
                            </template>
                            <div x-show="!show" class="flex h-full min-h-72 items-center justify-center p-6">
                                <x-ui.button variant="secondary" x-on:click="show = true">Mostrar mapa</x-ui.button>
                            </div>
                        </div>
                    @endif
                </div>
            </article>
        @empty
            <x-ui.empty-state>La ubicación se publicará aquí en cuanto la organización la confirme.</x-ui.empty-state>
        @endforelse
    </div>
</x-layouts.public>
