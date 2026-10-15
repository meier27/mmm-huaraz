<x-layouts.public title="Descubre Huaraz">
    <x-ui.page-intro title="Descubre {{ $settings->city }}" eyebrow="Turismo">
        Lugares para conocer durante tu visita.
    </x-ui.page-intro>

    <div class="container-content py-10">
        @if ($places->isEmpty())
            <x-ui.empty-state>Las recomendaciones turísticas se publicarán aquí próximamente.</x-ui.empty-state>
        @else
            <ul class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($places as $place)
                    <li class="flex flex-col overflow-hidden rounded-card border border-line bg-white">
                        @if ($place->imageUrl())
                            <img src="{{ $place->imageUrl() }}" alt="{{ $place->image_alt ?? '' }}" width="800" height="600"
                                 class="aspect-[4/3] w-full object-cover" loading="lazy" decoding="async">
                        @endif
                        <div class="flex flex-1 flex-col p-5">
                            <h2 class="text-xl font-bold">{{ $place->name }}</h2>
                            @if ($place->distance_note)
                                <p class="mt-1 text-sm font-semibold text-brand-strong">{{ $place->distance_note }}</p>
                            @endif
                            @if ($place->description)
                                <p class="mt-2 text-sm text-muted whitespace-pre-line">{{ $place->description }}</p>
                            @endif
                            @if ($place->maps_url)
                                <a href="{{ $place->maps_url }}" class="mt-auto pt-4 text-sm font-semibold text-brand-strong underline-offset-4 hover:underline" target="_blank" rel="noopener">Ver en el mapa</a>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</x-layouts.public>
