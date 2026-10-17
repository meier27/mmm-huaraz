{{-- Dirección y mapa del templo (Panel › Datos del sitio › Dirección del templo). --}}
@if ($site->address)
    <div>
        <h3 class="font-display text-lg font-bold text-primary">Dónde estamos</h3>
        <p class="mt-2 flex items-start gap-2">
            <x-ui.icon name="map-pin" class="mt-0.5 size-5 shrink-0 text-brand-strong" />
            <span>{{ $site->address }}</span>
        </p>
        @if ($site->mapEmbedUrl())
            <iframe src="{{ $site->mapEmbedUrl() }}" title="Mapa: {{ $site->address }}"
                    class="mt-4 h-60 w-full rounded-card border-0 shadow-card ring-1 ring-black/5" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
        @endif
        <a href="{{ $site->mapsLink() }}" target="_blank" rel="noopener" class="mt-3 inline-flex items-center gap-1.5 text-sm font-semibold text-brand-strong hover:text-primary">
            Abrir en Google Maps <x-ui.icon name="external" class="size-3.5" /><span class="sr-only"> (pestaña nueva)</span>
        </a>
    </div>
@endif
