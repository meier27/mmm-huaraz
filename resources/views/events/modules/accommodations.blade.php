<x-layouts.public :event="$event" title="Hospedaje">
    <x-ui.page-intro title="Hospedaje" :eyebrow="$event->title">
        Opciones de alojamiento para tu estadía durante la convención.
    </x-ui.page-intro>

    <div class="container-content py-10">
        @if ($accommodations->isEmpty())
            <x-ui.empty-state>Las opciones de hospedaje se publicarán aquí en cuanto la organización las confirme.</x-ui.empty-state>
        @else
            <ul class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($accommodations as $accommodation)
                    <li class="flex flex-col rounded-card border border-line bg-white p-5">
                        <p class="text-sm font-semibold text-brand-strong">{{ $accommodation->type->label() }}</p>
                        <h2 class="mt-1 text-xl font-bold">{{ $accommodation->name }}</h2>
                        @if ($accommodation->address)
                            <p class="mt-2 text-sm">{{ $accommodation->address }}</p>
                        @endif
                        @if ($accommodation->price_range)
                            <p class="mt-1 text-sm font-semibold">{{ $accommodation->price_range }}</p>
                        @endif
                        @if ($accommodation->notes)
                            <p class="mt-2 text-sm text-muted whitespace-pre-line">{{ $accommodation->notes }}</p>
                        @endif
                        <div class="mt-auto flex flex-wrap gap-x-4 gap-y-2 pt-4 text-sm font-semibold text-brand-strong">
                            @if ($accommodation->phone)
                                <a href="tel:{{ preg_replace('/[^0-9+]/', '', $accommodation->phone) }}" class="underline-offset-4 hover:underline">Llamar</a>
                            @endif
                            @if ($accommodation->maps_url)
                                <a href="{{ $accommodation->maps_url }}" class="underline-offset-4 hover:underline" target="_blank" rel="noopener">Ver mapa</a>
                            @endif
                            @if ($accommodation->website_url)
                                <a href="{{ $accommodation->website_url }}" class="underline-offset-4 hover:underline" target="_blank" rel="noopener nofollow">Sitio web</a>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
            <div class="mt-8">{{ $accommodations->links() }}</div>
        @endif
    </div>
</x-layouts.public>
