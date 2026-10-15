<div>
    <x-ui.page-intro title="Reserva tu comida" eyebrow="Alimentación durante la convención">
        Elige tus platos y horario. Recibirás un código QR para recogerlos. Lo recaudado apoya la obra de la iglesia anfitriona.
    </x-ui.page-intro>

    <div class="container-content py-10">
        @if (! $settings->reservations_open)
            <x-ui.empty-state>Las reservas aún no están abiertas. Vuelve pronto: publicaremos aquí el menú en cuanto esté confirmado.</x-ui.empty-state>
        @elseif ($groups->isEmpty())
            <x-ui.empty-state>No hay platos disponibles para reservar en este momento.</x-ui.empty-state>
        @else
            <form wire:submit="submit" novalidate
                  x-data="{ prices: @js($prices), get total() { return Object.entries($wire.quantities).reduce((sum, [id, qty]) => sum + (this.prices[id] ?? 0) * (parseInt(qty) || 0), 0) } }"
                  class="grid gap-8 lg:grid-cols-[1fr_22rem] lg:items-start">

                <div class="space-y-10">
                    @error('quantities')
                        <x-ui.alert type="error">{{ $message }}</x-ui.alert>
                    @enderror

                    @foreach ($groups as $date => $offerings)
                        <section aria-labelledby="fecha-{{ $date }}">
                            <h2 id="fecha-{{ $date }}" class="flex items-center gap-2 text-2xl font-bold">
                                <x-ui.icon name="calendar" class="text-brand-strong" />
                                {{ $offerings->first()->dateLabel() }}
                            </h2>

                            <ul class="mt-4 grid gap-4 sm:grid-cols-2">
                                @foreach ($offerings as $offering)
                                    @php($available = $offering->available())
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
                </div>

                <aside class="rounded-card bg-white p-5 shadow-card lg:sticky lg:top-24" aria-labelledby="tus-datos">
                    <h2 id="tus-datos" class="text-xl font-bold">Tus datos</h2>
                    <p class="mt-1 text-sm text-muted">Solo los usamos para identificar tu reserva.</p>

                    <div class="mt-4 space-y-4">
                        <div>
                            <label for="customer_name" class="block text-sm font-medium">Nombre y apellido</label>
                            <input id="customer_name" type="text" wire:model="customer_name" autocomplete="name" required
                                   @error('customer_name') aria-invalid="true" aria-describedby="customer_name-error" @enderror
                                   class="mt-1 block min-h-11 w-full rounded-control border border-stone-300 px-3 py-2 focus:border-navy">
                            @error('customer_name')<p id="customer_name-error" class="mt-1 text-sm text-danger">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="customer_phone" class="block text-sm font-medium">Celular</label>
                            <input id="customer_phone" type="tel" wire:model="customer_phone" autocomplete="tel" inputmode="tel" required
                                   @error('customer_phone') aria-invalid="true" aria-describedby="customer_phone-error" @enderror
                                   class="mt-1 block min-h-11 w-full rounded-control border border-stone-300 px-3 py-2 focus:border-navy">
                            @error('customer_phone')<p id="customer_phone-error" class="mt-1 text-sm text-danger">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="notes" class="block text-sm font-medium">Observaciones (opcional)</label>
                            <textarea id="notes" rows="2" wire:model="notes" class="mt-1 block w-full rounded-control border border-stone-300 px-3 py-2 focus:border-navy"></textarea>
                            @error('notes')<p class="mt-1 text-sm text-danger">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="mt-6 flex items-baseline justify-between border-t border-line pt-4">
                        <span class="text-muted">Total estimado</span>
                        <span class="font-display text-2xl font-extrabold text-earth" aria-live="polite">S/ <span x-text="total.toFixed(2)">0.00</span></span>
                    </div>
                    <p class="mt-1 text-xs text-muted">El importe final lo calcula el sistema al confirmar.</p>

                    <x-ui.button type="submit" class="mt-4 w-full" wire:loading.attr="disabled" wire:target="submit" x-bind:disabled="total <= 0">
                        <span wire:loading.remove wire:target="submit">Confirmar reserva</span>
                        <span wire:loading wire:target="submit">Reservando…</span>
                    </x-ui.button>

                    @if ($settings->reservation_instructions)
                        <p class="mt-4 rounded-control bg-cream p-3 text-sm whitespace-pre-line">{{ $settings->reservation_instructions }}</p>
                    @endif
                </aside>
            </form>
        @endif
    </div>
</div>
