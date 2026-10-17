{{--
    Selector de platos por fecha. Requiere: $groups (ofertas agrupadas por fecha),
    $maxPerItem y, opcionalmente, $held (porciones que la reserva ya tiene por
    oferta y que se liberarán al cambiar). Enlaza con la propiedad "quantities".
--}}
@php
    $held ??= [];
    $perPage = 6;
    $firstDate = $groups->keys()->first();
    // Ids por fecha: para contar en cada pestaña cuántas porciones se eligieron.
    $idsByDate = $groups->map(fn ($offerings) => $offerings->pluck('id')->values());
@endphp

{{--
    Paginación en el navegador (Alpine): una fecha a la vez y 6 platos por página.
    Todos los platos siguen en el formulario, así que las cantidades elegidas se
    conservan al cambiar de página o de fecha.
--}}
<div x-cloak x-data="{
        day: @js($firstDate),
        page: 1,
        ids: @js($idsByDate),
        selected(date) { return this.ids[date].reduce((sum, id) => sum + (parseInt($wire.quantities[id]) || 0), 0) },
        go(date, page) { this.day = date; this.page = page; this.$nextTick(() => this.$refs.top.scrollIntoView({ behavior: 'smooth', block: 'start' })) },
     }">
    <div x-ref="top" class="scroll-mt-40"></div>

    @if ($groups->count() > 1)
        <div class="-mx-4 mb-8 overflow-x-auto px-4 pb-1 sm:mx-0 sm:px-0" role="tablist" aria-label="Fechas con platos">
            <div class="flex gap-2">
                @foreach ($groups as $date => $offerings)
                    <button type="button" role="tab" id="tab-fecha-{{ $date }}" aria-controls="fecha-{{ $date }}"
                            x-on:click="go(@js($date), 1)"
                            x-bind:aria-selected="(day === @js($date)).toString()"
                            class="relative flex min-w-32 shrink-0 flex-col rounded-card px-4 py-3 text-left ring-1 transition"
                            x-bind:class="day === @js($date) ? 'bg-primary text-white ring-primary shadow-lg' : 'bg-white text-primary ring-sand-dark hover:ring-brand-strong'">
                        <span class="text-xs font-semibold tracking-wide uppercase opacity-80">{{ $offerings->first()->serve_date->translatedFormat('l') }}</span>
                        <span class="font-display text-xl leading-tight font-bold">{{ $offerings->first()->serve_date->translatedFormat('j M') }}</span>
                        <span class="mt-1 text-xs opacity-80">{{ trans_choice('{1} 1 plato|[2,*] :count platos', $offerings->count()) }}</span>
                        <span x-show="selected(@js($date)) > 0" x-cloak
                              class="absolute -top-2 -right-2 inline-flex min-w-6 items-center justify-center rounded-full bg-brand-strong px-1.5 text-xs font-bold text-white ring-2 ring-white"
                              x-text="selected(@js($date))" aria-label="porciones elegidas"></span>
                    </button>
                @endforeach
            </div>
        </div>
    @endif

@foreach ($groups as $date => $offerings)
    @php
        $pages = (int) ceil($offerings->count() / $perPage);
    @endphp
    <section id="fecha-{{ $date }}" role="tabpanel" aria-labelledby="titulo-fecha-{{ $date }}"
             x-show="day === @js($date)">
        <h2 id="titulo-fecha-{{ $date }}" class="flex items-center gap-2 text-2xl font-bold">
            <x-ui.icon name="calendar" class="text-brand-strong" />
            {{ $offerings->first()->dateLabel() }}
        </h2>

        <ul class="mt-4 grid gap-4 sm:grid-cols-2">
            @foreach ($offerings as $offering)
                @php
                    $available = $offering->available() + ($held[$offering->id] ?? 0);
                    $pageOf = intdiv($loop->index, $perPage) + 1;
                @endphp
                <li wire:key="offering-{{ $offering->id }}" x-show="page === {{ $pageOf }}"
                    x-transition:enter="transition duration-300 ease-out-soft" x-transition:enter-start="opacity-0 translate-y-3" x-transition:enter-end="opacity-100 translate-y-0"
                    @class(['flex flex-col overflow-hidden rounded-card border bg-white transition-shadow', 'border-line hover:shadow-card' => $available > 0, 'border-line opacity-60' => $available === 0])
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
                                    <button type="button" class="inline-flex size-11 items-center justify-center rounded-full border border-line text-xl font-bold text-primary hover:bg-cream"
                                            x-on:click="$wire.$set('quantities.{{ $offering->id }}', Math.max(0, (parseInt($wire.quantities[{{ $offering->id }}]) || 0) - 1), false)"
                                            aria-label="Quitar una porción">−</button>
                                    <input type="number" min="0" max="{{ $limit }}" inputmode="numeric"
                                           wire:model="quantities.{{ $offering->id }}"
                                           class="w-14 rounded-control border border-stone-300 py-2 text-center font-semibold tabular-nums"
                                           aria-label="Porciones de {{ $offering->dish->name }}">
                                    <button type="button" class="inline-flex size-11 items-center justify-center rounded-full bg-brand-strong text-xl font-bold text-white hover:bg-primary"
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

        @if ($pages > 1)
            <nav aria-label="Páginas de platos del {{ $offerings->first()->dateLabel() }}" class="mt-6 flex flex-col items-center gap-3 sm:flex-row sm:justify-between">
                <p class="text-sm text-muted">Página <span class="font-semibold text-primary" x-text="page">1</span> de <span class="font-semibold text-primary">{{ $pages }}</span></p>
                <div class="flex flex-wrap items-center gap-1.5">
                    <button type="button" x-on:click="go(@js($date), page - 1)" x-bind:disabled="page === 1"
                            class="inline-flex min-h-11 items-center gap-1 rounded-control px-3 text-sm font-semibold text-primary ring-1 ring-sand-dark transition hover:bg-white disabled:cursor-not-allowed disabled:text-stone-400 disabled:ring-transparent">
                        <x-ui.icon name="chevron-left" class="size-4" /> Anterior
                    </button>
                    @for ($n = 1; $n <= $pages; $n++)
                        <button type="button" x-on:click="go(@js($date), {{ $n }})" aria-label="Página {{ $n }}"
                                x-bind:aria-current="page === {{ $n }} ? 'page' : null"
                                x-bind:class="page === {{ $n }} ? 'bg-primary text-white' : 'text-primary hover:bg-white'"
                                class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-control text-sm font-bold transition">{{ $n }}</button>
                    @endfor
                    <button type="button" x-on:click="go(@js($date), page + 1)" x-bind:disabled="page === {{ $pages }}"
                            class="inline-flex min-h-11 items-center gap-1 rounded-control px-3 text-sm font-semibold text-primary ring-1 ring-sand-dark transition hover:bg-white disabled:cursor-not-allowed disabled:text-stone-400 disabled:ring-transparent">
                        Siguiente <x-ui.icon name="chevron-right" class="size-4" />
                    </button>
                </div>
            </nav>
        @endif
    </section>
@endforeach
</div>
