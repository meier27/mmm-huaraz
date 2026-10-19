<x-layouts.public title="Reserva {{ $reservation->code }}" :noindex="true" :event="$event">
    <div class="bg-cream">
        <div class="container-content py-10">
            <div class="mx-auto grid max-w-4xl gap-6 md:grid-cols-[18rem_1fr]">
                <section class="self-start overflow-hidden rounded-card bg-white text-center shadow-card" aria-labelledby="codigo-titulo" x-data="{ copied: false }">
                    <div class="bg-primary-deep px-5 py-6 text-white">
                        <h1 id="codigo-titulo" class="text-sm font-semibold tracking-[0.14em] text-cream/80 uppercase">Tu código de reserva</h1>
                        <p class="mt-2 font-mono text-4xl font-extrabold tracking-[0.2em] text-accent-light">{{ $reservation->code }}</p>
                    </div>
                    <div class="p-5">
                        <p class="text-sm text-muted">Muéstralo al recoger tu pedido. Anótalo o guarda esta página.</p>
                        <x-ui.button variant="secondary" class="mt-4 w-full"
                                     x-on:click="navigator.clipboard?.writeText(@js($reservation->code)).then(() => { copied = true; setTimeout(() => copied = false, 2000) })">
                            <span x-show="!copied">Copiar código</span><span x-show="copied" x-cloak role="status">Copiado</span>
                        </x-ui.button>
                    </div>
                </section>

                <div class="space-y-6">
                    @if (session('reservation-notice'))
                        <x-ui.alert type="success">{{ session('reservation-notice') }}</x-ui.alert>
                    @endif

                    <livewire:reservation-status-panel :reservation-id="$reservation->id" />

                    <livewire:reservation-customer-actions :token="$token" />

                    <section class="rounded-card bg-white p-5 shadow-card" aria-labelledby="detalle">
                        <h2 id="detalle" class="text-lg font-bold">Detalle</h2>
                        <p class="mt-1 text-sm text-muted">A nombre de {{ $reservation->customer_name }} · Celular {{ $reservation->maskedPhone() }}</p>

                        <ul class="mt-4 divide-y divide-line">
                            @foreach ($reservation->items as $item)
                                <li class="flex items-start justify-between gap-4 py-3">
                                    <div>
                                        <p class="font-semibold">{{ $item->quantity }} × {{ $item->offering->dish->name }}</p>
                                        <p class="text-sm text-muted">{{ $item->offering->dateLabel() }} · {{ $item->offering->slotLabel() }}</p>
                                    </div>
                                    <p class="shrink-0 font-semibold tabular-nums">S/ {{ number_format((float) $item->line_total, 2) }}</p>
                                </li>
                            @endforeach
                        </ul>

                        <div class="mt-2 flex items-baseline justify-between border-t border-line pt-3">
                            <span class="font-semibold">Total</span>
                            <span class="font-display text-2xl font-extrabold text-primary tabular-nums">S/ {{ number_format((float) $reservation->total, 2) }}</span>
                        </div>

                        @if ($reservation->notes)
                            <p class="mt-3 text-sm"><span class="font-semibold">Observaciones:</span> {{ $reservation->notes }}</p>
                        @endif
                    </section>

                    @unless ($reservation->isClosed())
                        <section class="rounded-card border border-brand/40 bg-white p-5" aria-labelledby="como-funciona">
                            <h2 id="como-funciona" class="text-lg font-bold">Cómo funciona</h2>
                            <div class="mt-3">@include('reservations.partials.rules')</div>
                            @if ($event->reservation_instructions && ! $reservation->isFullyPaid())
                                <p class="mt-4 rounded-control bg-cream p-3 text-sm whitespace-pre-line">{{ $event->reservation_instructions }}</p>
                            @endif
                        </section>
                    @endunless
                </div>
            </div>
        </div>
    </div>
</x-layouts.public>
