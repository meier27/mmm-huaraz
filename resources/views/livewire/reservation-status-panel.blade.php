<section class="rounded-card bg-white p-5 shadow-card" aria-labelledby="estado-reserva">
    <h2 id="estado-reserva" class="text-lg font-bold">Estado</h2>

    @if (session('reservation-status'))
        <x-ui.alert type="success" class="mt-3">{{ session('reservation-status') }}</x-ui.alert>
    @endif
    @error('action')
        <x-ui.alert type="error" class="mt-3">{{ $message }}</x-ui.alert>
    @enderror

    @php
        $badge = 'inline-flex items-center gap-1 rounded-full px-3 py-1 text-sm font-semibold';
    @endphp
    <div class="mt-3 flex flex-wrap gap-2">
        <span @class([$badge, 'bg-amber-100 text-warning' => $reservation->status->value === 'pending', 'bg-green-100 text-success' => $reservation->status->value === 'confirmed', 'bg-red-100 text-danger' => $reservation->isCancelled()])>
            {{ $reservation->status->label() }}
        </span>
        @unless ($reservation->isCancelled())
            @if ($reservation->isPaid())
                <span class="{{ $badge }} bg-green-100 text-success"><x-ui.icon name="check" class="size-4" /> {{ $reservation->payment_status->label() }}</span>
            @endif
            <span @class([$badge, 'bg-green-100 text-success' => $reservation->isDelivered(), 'bg-stone-100 text-muted' => ! $reservation->isDelivered()])>
                @if ($reservation->isDelivered())<x-ui.icon name="check" class="size-4" />@endif {{ $reservation->delivery_status->label() }}
            </span>
        @endunless
    </div>

    @if ($reservation->isCancelled())
        <p class="mt-3 text-sm text-muted">Motivo: {{ $reservation->cancellation_reason }}</p>
    @elseif (! $reservation->isPaid() && $reservation->expires_at)
        <p class="mt-3 text-sm text-muted">
            Si el pago no se verifica antes del {{ $reservation->expires_at->translatedFormat('j \d\e F, H:i') }}, la reserva se liberará automáticamente.
        </p>
    @endif

    @auth
        @php
            $canPay = auth()->user()->can('verifyPayment', $reservation) && ! $reservation->isPaid() && ! $reservation->isCancelled();
            $canDeliver = auth()->user()->can('deliver', $reservation) && $reservation->isPaid() && ! $reservation->isDelivered() && ! $reservation->isCancelled();
            $canCancel = auth()->user()->can('cancel', $reservation) && ! $reservation->isDelivered() && ! $reservation->isCancelled();
        @endphp

        @if (auth()->user()->can('view', $reservation))
            <div class="mt-5 space-y-4 border-t border-line pt-4">
                <p class="text-xs font-semibold tracking-wide text-muted uppercase">Acciones del personal · {{ auth()->user()->role->label() }}</p>

                <dl class="grid gap-1 text-sm">
                    @if ($reservation->paymentVerifier)
                        <div><dt class="inline text-muted">Pago verificado por</dt> <dd class="inline">{{ $reservation->paymentVerifier->name }} · {{ $reservation->payment_method?->label() }} · {{ $reservation->payment_verified_at->translatedFormat('d/m H:i') }}</dd></div>
                    @endif
                    @if ($reservation->deliverer)
                        <div><dt class="inline text-muted">Entregado por</dt> <dd class="inline">{{ $reservation->deliverer->name }} · {{ $reservation->delivered_at->translatedFormat('d/m H:i') }}</dd></div>
                    @endif
                    @if ($reservation->canceller)
                        <div><dt class="inline text-muted">Cancelado por</dt> <dd class="inline">{{ $reservation->canceller->name }}</dd></div>
                    @endif
                </dl>

                @if ($canPay)
                    <form wire:submit="verifyPayment" class="grid gap-3 rounded-control bg-stone-50 p-3 sm:grid-cols-2">
                        <div>
                            <label for="paymentMethod" class="block text-sm font-medium">Método de pago</label>
                            <select id="paymentMethod" wire:model="paymentMethod" class="mt-1 block min-h-11 w-full rounded-control border border-stone-300 bg-white px-3">
                                @foreach ($methods as $method)
                                    <option value="{{ $method->value }}">{{ $method->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="paymentReference" class="block text-sm font-medium">N.º de operación (opcional)</label>
                            <input id="paymentReference" type="text" wire:model="paymentReference" class="mt-1 block min-h-11 w-full rounded-control border border-stone-300 bg-white px-3">
                            @error('paymentReference')<p class="mt-1 text-sm text-danger">{{ $message }}</p>@enderror
                        </div>
                        <x-ui.button type="submit" class="sm:col-span-2" wire:loading.attr="disabled"
                                     wire:confirm="¿Confirmas que recibiste S/ {{ number_format((float) $reservation->total, 2) }}?">
                            Verificar pago de S/ {{ number_format((float) $reservation->total, 2) }}
                        </x-ui.button>
                    </form>
                @endif

                @if ($canDeliver)
                    <x-ui.button class="w-full" wire:click="deliver" wire:loading.attr="disabled" wire:confirm="¿Registrar la entrega del pedido completo?">
                        <x-ui.icon name="check" class="size-5" /> Registrar entrega
                    </x-ui.button>
                @endif

                @if ($canCancel)
                    <details class="rounded-control border border-line p-3">
                        <summary class="cursor-pointer text-sm font-semibold text-danger">Cancelar reserva</summary>
                        <form wire:submit="cancel" class="mt-3 space-y-2">
                            <label for="cancelReason" class="block text-sm font-medium">Motivo</label>
                            <input id="cancelReason" type="text" wire:model="cancelReason" class="block min-h-11 w-full rounded-control border border-stone-300 px-3">
                            @error('cancelReason')<p class="text-sm text-danger">{{ $message }}</p>@enderror
                            <x-ui.button type="submit" variant="secondary" class="text-danger!" wire:confirm="¿Cancelar la reserva y liberar el stock?">Confirmar cancelación</x-ui.button>
                        </form>
                    </details>
                @endif

                @if (! $canPay && ! $canDeliver && ! $canCancel)
                    <p class="text-sm text-muted">No hay acciones disponibles para tu rol en el estado actual.</p>
                @endif
            </div>
        @endif
    @endauth
</section>
