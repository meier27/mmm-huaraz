<x-layouts.public title="Buscar en la Biblia" noindex>
    <x-ui.page-intro title="Buscar en la Biblia" :eyebrow="$versionName" />

    <div class="container-content max-w-3xl! py-10">
        @include('bible.partials.search-form')

        <div class="mt-8">
            @if ($tooShort)
                <x-ui.alert type="warning">Escribe al menos 3 letras para buscar.</x-ui.alert>
            @elseif ($results === null)
                <x-ui.alert type="error">La búsqueda no está disponible en este momento. Inténtalo de nuevo en unos minutos.</x-ui.alert>
            @elseif ($results === [])
                <x-ui.empty-state>No encontramos versículos con «{{ $query }}».</x-ui.empty-state>
            @else
                <p class="text-muted">{{ count($results) }} {{ count($results) === 1 ? 'versículo encontrado' : 'versículos encontrados' }} con «{{ $query }}».</p>
                <ul class="mt-4 grid gap-2 sm:grid-cols-2">
                    @foreach ($results as $result)
                        <li>
                            <a href="{{ route('bible.chapter', [$result['book']['slug'], $result['chapter']]) }}?v={{ $result['verse'] }}#v{{ $result['verse'] }}"
                               class="flex min-h-11 items-center justify-between rounded-control border border-line bg-white px-4 py-2 font-medium hover:border-brand hover:bg-cream">
                                {{ $result['book']['name'] }} {{ $result['chapter'] }}:{{ $result['verse'] }}
                                <x-ui.icon name="chevron-right" class="size-4 text-muted" />
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</x-layouts.public>
