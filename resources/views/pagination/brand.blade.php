{{--
    Paginador del sitio público: anterior / números / siguiente, con aria-current,
    etiquetas para lectores de pantalla y destino táctil de 44 px.
--}}
@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Paginación" class="flex flex-col items-center gap-3 sm:flex-row sm:justify-between">
        <p class="text-sm text-muted">
            Página <span class="font-semibold text-primary">{{ $paginator->currentPage() }}</span> de <span class="font-semibold text-primary">{{ $paginator->lastPage() }}</span>
        </p>

        <ul class="flex flex-wrap items-center gap-1.5">
            <li>
                @if ($paginator->onFirstPage())
                    <span class="inline-flex min-h-11 items-center gap-1 rounded-control px-3 text-sm font-semibold text-stone-400" aria-disabled="true">
                        <x-ui.icon name="chevron-left" class="size-4" /> Anterior
                    </span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex min-h-11 items-center gap-1 rounded-control px-3 text-sm font-semibold text-primary ring-1 ring-sand-dark transition hover:bg-white hover:text-brand-strong">
                        <x-ui.icon name="chevron-left" class="size-4" /> Anterior
                    </a>
                @endif
            </li>

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li><span class="inline-flex min-h-11 min-w-11 items-center justify-center text-sm text-muted" aria-hidden="true">{{ $element }}</span></li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <li class="hidden sm:block">
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page" class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-control bg-primary text-sm font-bold text-white">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" aria-label="Ir a la página {{ $page }}" class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-control text-sm font-semibold text-primary transition hover:bg-white hover:text-brand-strong">{{ $page }}</a>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach

            <li>
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex min-h-11 items-center gap-1 rounded-control px-3 text-sm font-semibold text-primary ring-1 ring-sand-dark transition hover:bg-white hover:text-brand-strong">
                        Siguiente <x-ui.icon name="chevron-right" class="size-4" />
                    </a>
                @else
                    <span class="inline-flex min-h-11 items-center gap-1 rounded-control px-3 text-sm font-semibold text-stone-400" aria-disabled="true">
                        Siguiente <x-ui.icon name="chevron-right" class="size-4" />
                    </span>
                @endif
            </li>
        </ul>
    </nav>
@endif
