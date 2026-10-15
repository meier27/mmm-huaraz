{{--
    Selector de platos por fecha. Requiere: $groups (ofertas agrupadas por fecha),
    $maxPerItem y, opcionalmente, $held (porciones que la reserva ya tiene por
    oferta y que se liberarán al cambiar). Enlaza con la propiedad "quantities".
--}}
@php($held ??= [])

@foreach ($groups as $date => $offerings)
    <section aria-labelledby="fecha-{{ $date }}">
        <h2 id="fecha-{{ $date }}" class="flex items-center gap-2 text-2xl font-bold">
            <x-ui.icon name="calendar" class="text-brand-strong" />
            {{ $offerings->first()->dateLabel() }}
        </h2>

        <ul class="mt-4 grid gap-4 sm:grid-cols-2">
            @foreach ($offerings as $offering)
                @php($available = $offering->available() + ($held[$offering->id] ?? 0))
                <li wire:key="offering-{{ $offering->id }}" @class(['flex flex-col overflow-hidden rounded-card border bg-white transition-shadow', 'border-line hover:shadow-card' => $available > 0, 'border-line opacity-60' => $available === 0])
                    :class="($wire.quantities[{{ $offering->id }}] ?? 0) > 0 && 'ring-2 ring-brand border-brand'">
                    @if ($offering->dish->imageUrl())
                        <img src="{{ $offering->dish->imageUrl() }}" alt="{{ $offering->dish->image_alt ?? '' }}" width="640" height="400"
                             class="aspect-16/10 w-full object-cover" loading="lazy" decoding="async">
                    @endif
                    <div class="flex flex-1 flex-col p-4">
                        <div class="flex items-start justify-between gap-3">
                            <h3 class="font-sans text-lg font-bold text-ink">{{ $offering->dish->name }}</h3>
                            <p class="shrink-0 font-display text-lg font-extrabold text-brand-strong">S/ {{ number_format((float) $offering->price, 2) }}</p>
                        </div>
                        <p class="mt-1 flex items-center gap-1 text-sm text-muted">
                            <x-ui.icon name="clock" class="size-4" /> {{ $offering->slotLabel() }}
                        </p>
                        @if ($offering->dish->description)
                            <p class="mt-2 text-sm">{{ $offering->dish->description }}</p>
                        @endif
                        @if ($offering->dish->allergens)
                            <p class="mt-2 text-xs text-muted"><span class="font-semibold">Contiene:</span> {{ $offering->dish->allergens }}</p>
                        @endif

                        <div class="mt-auto flex items-center justify-between gap-3 pt-4">
                            <p @class(['text-sm font-semibold', 'text-success' => $available > 10, 'text-warning' => $available > 0 && $available <= 10, 'text-danger' => $available === 0])>
                                {{ $available === 0 ? 'Agotado' : ($available <= 10 ? "¡Quedan {$available}!" : "{$available} disponibles") }}
                            </p>

                            @if ($available > 0)
                                @php($limit = min($available, $maxPerItem))
                                <div class="flex items-center gap-1" role="group" aria-label="Cantidad de {{ $offering->dish->name }}">
                                    <button type="button" class="inline-flex size-11 items-center justify-center rounded-full border border-line text-xl font-bold text-earth hover:bg-cream"
                                            x-on:click="$wire.$set('quantities.{{ $offering->id }}', Math.max(0, (parseInt($wire.quantities[{{ $offering->id }}]) || 0) - 1), false)"
                                            aria-label="Quitar una porción">−</button>
                                    <input type="number" min="0" max="{{ $limit }}" inputmode="numeric"
                                           wire:model="quantities.{{ $offering->id }}"
                                           class="w-14 rounded-control border border-stone-300 py-2 text-center font-semibold tabular-nums"
                                           aria-label="Porciones de {{ $offering->dish->name }}">
                                    <button type="button" class="inline-flex size-11 items-center justify-center rounded-full bg-brand-strong text-xl font-bold text-white hover:bg-earth"
                                            x-on:click="$wire.$set('quantities.{{ $offering->id }}', Math.min({{ $limit }}, (parseInt($wire.quantities[{{ $offering->id }}]) || 0) + 1), false)"
                                            aria-label="Añadir una porción">+</button>
                                </div>
                            @endif
                        </div>
                        @error("quantities.{$offering->id}")
                            <p class="mt-2 text-sm text-danger" role="alert">{{ $message }}</p>
                        @enderror
                    </div>
                </li>
            @endforeach
        </ul>
    </section>
@endforeach
