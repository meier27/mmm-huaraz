@props(['event'])

@php
    // Pestañas de un evento: «Datos» y una por módulo activo. Reservas y
    // aportes van a sus listados filtrados por el evento, si el rol los ve.
    $tabs = collect([
        ['label' => 'Datos', 'icon' => 'settings', 'url' => route('admin.events.edit', $event), 'active' => 'admin.events.edit'],
        ['label' => 'Programa', 'icon' => 'list', 'url' => route('admin.events.program', $event), 'active' => 'admin.events.program', 'module' => 'program'],
        ['label' => 'Días', 'icon' => 'calendar', 'url' => route('admin.events.program-days', $event), 'active' => 'admin.events.program-days', 'module' => 'program'],
        ['label' => 'Sedes', 'icon' => 'map-pin', 'url' => route('admin.events.venues', $event), 'active' => 'admin.events.venues', 'module' => 'venues'],
        ['label' => 'Platos', 'icon' => 'utensils', 'url' => route('admin.events.dishes', $event), 'active' => 'admin.events.dishes', 'module' => 'food'],
        ['label' => 'Ofertas', 'icon' => 'clock', 'url' => route('admin.events.offerings', $event), 'active' => 'admin.events.offerings', 'module' => 'food'],
        ['label' => 'Reservas', 'icon' => 'utensils', 'url' => route('admin.reservations', ['evento' => $event->id]), 'active' => null, 'module' => 'food', 'can' => 'manage-reservations'],
        ['label' => 'Aportes', 'icon' => 'coins', 'url' => route('admin.donations', ['evento' => $event->id, 'status' => 'all']), 'active' => null, 'module' => 'donations', 'can' => 'manage-donations'],
        ['label' => 'Hospedaje', 'icon' => 'bed', 'url' => route('admin.events.accommodations', $event), 'active' => 'admin.events.accommodations', 'module' => 'accommodations'],
        ['label' => 'Turismo', 'icon' => 'mountain', 'url' => route('admin.events.tourism', $event), 'active' => 'admin.events.tourism', 'module' => 'tourism'],
    ])->filter(fn ($tab) => (! isset($tab['module']) || $event->hasModule($tab['module'])) && (! isset($tab['can']) || Gate::allows($tab['can'])));
@endphp

<div class="mb-6">
    <a href="{{ route('admin.events') }}" wire:navigate class="inline-flex items-center gap-1 text-sm font-semibold text-brand-strong hover:underline">
        <x-ui.icon name="chevron-left" class="size-4" /> Eventos
    </a>
    <div class="mt-2 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-extrabold">{{ $event->title }}</h1>
            <p class="text-sm text-muted">
                {{ $event->dateLabel() ?: 'Sin fecha' }} ·
                @if ($event->is_published) <span class="text-success">Publicado</span> @else <span>Borrador</span> @endif
                @if ($event->is_featured) · <span class="text-brand-strong">Destacado en portada</span> @endif
            </p>
        </div>
        @if ($event->is_published)
            <a href="{{ route('events.show', $event) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-sm font-semibold text-brand-strong hover:underline">
                Ver en la web <x-ui.icon name="external" class="size-4" /><span class="sr-only"> (pestaña nueva)</span>
            </a>
        @endif
    </div>

    {{-- En móvil se desplaza en horizontal; en pantallas grandes pasa a otra línea. --}}
    <nav aria-label="Secciones del evento" class="mt-4 -mx-1 overflow-x-auto lg:overflow-visible" x-init="$nextTick(() => { const a = $el.querySelector('[aria-current]'); if (a) $el.scrollLeft = a.offsetLeft - ($el.clientWidth - a.offsetWidth) / 2 })">
        <ul class="flex gap-1 border-b border-line px-1 lg:flex-wrap">
            @foreach ($tabs as $tab)
                @php
                    $current = $tab['active'] && request()->routeIs($tab['active']);
                @endphp
                <li class="shrink-0">
                    <a href="{{ $tab['url'] }}" wire:navigate
                       @class(['-mb-px flex min-h-11 items-center gap-2 border-b-2 px-3 text-sm font-semibold', 'border-brand-strong text-brand-strong' => $current, 'border-transparent text-muted hover:text-primary' => ! $current])
                       @if ($current) aria-current="page" @endif>
                        <x-ui.icon :name="$tab['icon']" class="size-4" /> {{ $tab['label'] }}
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>
</div>
