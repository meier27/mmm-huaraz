<div class="mx-auto max-w-3xl">
    <x-admin.page-header title="Verificar código" description="Escribe el código que muestra el cliente para comprobar su reserva y registrar la entrega." />

    <form wire:submit="verify" class="rounded-card bg-white p-5 shadow-card">
        <label for="code" class="block text-sm font-medium">Código de reserva</label>
        <div class="mt-1 flex flex-col gap-2 sm:flex-row">
            <input id="code" type="text" wire:model="code" autofocus autocomplete="off" autocapitalize="characters" spellcheck="false" placeholder="Ej.: K7M2QX9A"
                   @error('code') aria-invalid="true" aria-describedby="code-error" @enderror
                   class="block min-h-14 w-full rounded-control border border-stone-300 px-4 font-mono text-2xl font-bold tracking-[0.2em] uppercase focus:border-brand-strong">
            <x-ui.button type="submit" class="min-h-14 sm:px-8" wire:loading.attr="disabled" wire:target="verify">
                <x-ui.icon name="search" class="size-5" /> Verificar
            </x-ui.button>
        </div>
        @error('code')<p id="code-error" class="mt-1 text-sm text-danger">{{ $message }}</p>@enderror
    </form>

    @if ($searched)
        <div class="mt-6 space-y-6" role="status" aria-live="polite">
            @if (! $reservation)
                <div class="flex items-center gap-4 rounded-card bg-red-50 p-5 ring-2 ring-red-200">
                    <span class="inline-flex size-12 shrink-0 items-center justify-center rounded-full bg-danger text-white"><x-ui.icon name="close" class="size-6" /></span>
                    <div>
                        <p class="text-lg font-bold text-danger">Código no válido</p>
                        <p class="text-sm text-ink/80">No existe ninguna reserva con el código <span class="font-mono font-bold">{{ $code }}</span>. Revisa que esté bien escrito.</p>
                    </div>
                </div>
            @else
                @php
                    [$tone, $icon, $headline, $detail] = match (true) {
                        $reservation->isCancelled() => ['red', 'close', 'Reserva cancelada', $reservation->cancellation_reason],
                        $reservation->isDelivered() => ['amber', 'info', 'Ya fue entregada', 'Entregada el '.$reservation->delivered_at->translatedFormat('j \d\e F, H:i').'. No entregues de nuevo.'],
                        $reservation->isDonated() => ['violet', 'heart', 'Registrada como donación', 'El pedido no se recogió a tiempo.'],
                        $reservation->awaitsDepositReview() => ['amber', 'clock', 'Adelanto por verificar', 'Revisa la captura antes de entregar.'],
                        ! $reservation->hasPayments() => ['amber', 'clock', 'Sin adelanto', 'La reserva aún no tiene pagos registrados.'],
                        default => ['green', 'check', 'Código válido · Reserva confirmada', $reservation->isFullyPaid() ? 'Pagada por completo: lista para entregar.' : 'Cobra el saldo de S/ '.number_format((float) $reservation->balance(), 2).' antes de entregar.'],
                    };
                    $tones = [
                        'red' => ['bg-red-50 ring-red-200', 'bg-danger', 'text-danger'],
                        'amber' => ['bg-amber-50 ring-amber-200', 'bg-warning', 'text-warning'],
                        'violet' => ['bg-violet-50 ring-violet-200', 'bg-violet-700', 'text-violet-800'],
                        'green' => ['bg-green-50 ring-green-200', 'bg-success', 'text-success'],
                    ][$tone];
                @endphp
                <div class="flex items-center gap-4 rounded-card p-5 ring-2 {{ $tones[0] }}">
                    <span class="inline-flex size-12 shrink-0 items-center justify-center rounded-full text-white {{ $tones[1] }}"><x-ui.icon :name="$icon" class="size-6" /></span>
                    <div class="min-w-0">
                        <p class="text-lg font-bold {{ $tones[2] }}">{{ $headline }}</p>
                        @if ($detail)
                            <p class="text-sm text-ink/80">{{ $detail }}</p>
                        @endif
                    </div>
                </div>

                <div class="rounded-card bg-white p-5 shadow-card">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="font-mono text-2xl font-extrabold tracking-widest text-brand-strong">{{ $reservation->code }}</p>
                            <p class="mt-1 font-semibold">{{ $reservation->customer_name }} <span class="font-normal text-muted">· {{ $reservation->customer_phone }}</span></p>
                            <p class="text-sm text-muted">{{ $reservation->event?->title }}</p>
                        </div>
                        <a href="{{ route('admin.reservations.show', $reservation) }}" wire:navigate class="text-sm font-semibold text-brand-strong underline-offset-4 hover:underline">Abrir ficha</a>
                    </div>

                    <ul class="mt-4 divide-y divide-line">
                        @foreach ($reservation->items as $item)
                            <li class="flex justify-between gap-4 py-2">
                                <span><strong>{{ $item->quantity }} ×</strong> {{ $item->offering->dish->name }} <span class="text-sm text-muted">· {{ $item->offering->dateLabel() }} · {{ $item->offering->slotLabel() }}</span></span>
                                <span class="tabular-nums">S/ {{ number_format((float) $item->line_total, 2) }}</span>
                            </li>
                        @endforeach
                    </ul>
                    @if ($reservation->notes)
                        <p class="mt-3 text-sm"><span class="font-semibold">Observaciones:</span> {{ $reservation->notes }}</p>
                    @endif
                </div>

                <livewire:reservation-status-panel :reservation-id="$reservation->id" :key="'verify-'.$reservation->id" />
            @endif

            <div class="text-center">
                <x-ui.button variant="secondary" wire:click="clear">Verificar otro código</x-ui.button>
            </div>
        </div>
    @endif
</div>
