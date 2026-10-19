<section class="rounded-card bg-white p-5 shadow-card" aria-labelledby="estado-reserva">
    <h2 id="estado-reserva" class="text-lg font-bold">Estado</h2>

    @if (session('reservation-status'))
        <x-ui.alert type="success" class="mt-3">{{ session('reservation-status') }}</x-ui.alert>
    @endif
    @error('action')
        <x-ui.alert type="error" class="mt-3">{{ $message }}</x-ui.alert>
    @enderror

    {{-- Usar siempre la forma de bloque: mezclarla con la forma en línea rompe la compilación de Blade. --}}
    @php
        $badge = 'inline-flex items-center gap-1 rounded-full px-3 py-1 text-sm font-semibold';
    @endphp
    <div class="mt-3 flex flex-wrap gap-2">
        <span @class([$badge, 'bg-amber-100 text-warning' => $reservation->status->value === 'pending', 'bg-green-100 text-success' => $reservation->status->value === 'confirmed', 'bg-red-100 text-danger' => $reservation->isCancelled()])>
            {{ $reservation->status->label() }}
        </span>
        @unless ($reservation->isCancelled())
            @if ($reservation->hasPayments())
                <span @class([$badge, 'bg-green-100 text-success' => $reservation->isFullyPaid(), 'bg-sky-100 text-primary' => ! $reservation->isFullyPaid()])>
                    @if ($reservation->isFullyPaid())<x-ui.icon name="check" class="size-4" />@endif {{ $reservation->payment_status->label() }}
                </span>
            @endif
            <span @class([$badge, 'bg-green-100 text-success' => $reservation->isDelivered(), 'bg-violet-100 text-violet-800' => $reservation->isDonated(), 'bg-stone-100 text-muted' => $reservation->delivery_status->value === 'pending'])>
                @if ($reservation->isDelivered())<x-ui.icon name="check" class="size-4" />@endif {{ $reservation->delivery_status->label() }}
            </span>
        @endunless
    </div>

    @unless ($reservation->isCancelled())
        <dl class="mt-4 grid grid-cols-3 gap-2 rounded-control bg-stone-50 p-3 text-center">
            <div><dt class="text-xs text-muted">Total</dt><dd class="font-bold tabular-nums">S/ {{ number_format((float) $reservation->total, 2) }}</dd></div>
            <div><dt class="text-xs text-muted">Pagado</dt><dd class="font-bold tabular-nums text-success">S/ {{ number_format((float) $reservation->amount_paid, 2) }}</dd></div>
            <div><dt class="text-xs text-muted">Saldo</dt><dd @class(['font-bold tabular-nums', 'text-warning' => (float) $reservation->balance() > 0])>S/ {{ number_format((float) $reservation->balance(), 2) }}</dd></div>
        </dl>
    @endunless

    @if ($reservation->isCancelled())
        <p class="mt-3 text-sm text-muted">Motivo: {{ $reservation->cancellation_reason }}</p>
    @elseif ($reservation->isDonated())
        <p class="mt-3 text-sm text-muted">El pedido no se recogió a tiempo y se destinó a donación.</p>
    @elseif ($reservation->awaitsDepositReview())
        <p class="mt-3 flex gap-2 rounded-control bg-amber-50 p-3 text-sm text-ink">
            <x-ui.icon name="clock" class="mt-0.5 size-4 shrink-0 text-warning" />
            Recibimos la captura de tu adelanto. El equipo la verificará y tu reserva quedará confirmada.
        </p>
    @elseif (! $reservation->hasPayments() && $reservation->expires_at)
        <p class="mt-3 text-sm text-muted">
            Paga tu adelanto antes del {{ $reservation->expires_at->translatedFormat('j \d\e F, H:i') }}; si no, la reserva se liberará automáticamente.
        </p>
    @endif

    @auth
        @if (auth()->user()->can('view', $reservation))
            @php
                $user = auth()->user();
                $canReview = $user->can('registerPayment', $reservation) && $reservation->awaitsDepositReview();
                $canPay = $user->can('registerPayment', $reservation) && ! $reservation->isClosed() && (float) $reservation->balance() > 0 && ! $canReview;
                $canDeliver = $user->can('deliver', $reservation) && ! $reservation->isClosed();
                $canDonate = $user->can('markDonated', $reservation) && ! $reservation->isClosed() && $reservation->hasPayments() && ! $reservation->customerCanChange();
                $canCancel = $user->can('cancel', $reservation) && ! $reservation->isClosed();
            @endphp

            <div class="mt-5 space-y-4 border-t border-line pt-4">
                <p class="text-xs font-semibold tracking-wide text-muted uppercase">Acciones del personal · {{ $user->role->label() }}</p>

                @if ($reservation->payments->isNotEmpty())
                    <div>
                        <p class="text-sm font-semibold">Pagos registrados</p>
                        <ul class="mt-1 divide-y divide-line text-sm">
                            @foreach ($reservation->payments as $payment)
                                <li class="flex justify-between gap-3 py-1.5">
                                    <span>{{ $payment->created_at->translatedFormat('d/m H:i') }} · {{ $payment->method->label() }}@if ($payment->reference) · {{ $payment->reference }}@endif <span class="text-muted">· {{ $payment->receiver?->name ?? '—' }}</span></span>
                                    <span class="font-semibold tabular-nums">S/ {{ number_format((float) $payment->amount, 2) }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <dl class="grid gap-1 text-sm">
                    @if ($reservation->deliverer)
                        <div><dt class="inline text-muted">Entregado por</dt> <dd class="inline">{{ $reservation->deliverer->name }} · {{ $reservation->delivered_at->translatedFormat('d/m H:i') }}</dd></div>
                    @endif
                    @if ($reservation->isDonated())
                        <div><dt class="inline text-muted">Donación registrada</dt> <dd class="inline">{{ $reservation->donor?->name ?? 'automáticamente' }} · {{ $reservation->donated_at->translatedFormat('d/m H:i') }}</dd></div>
                    @endif
                    @if ($reservation->canceller)
                        <div><dt class="inline text-muted">Cancelado por</dt> <dd class="inline">{{ $reservation->canceller->name }}</dd></div>
                    @endif
                </dl>

                @if ($canReview)
                    @php
                        $expected = $reservation->event->minimumDeposit((string) $reservation->total);
                    @endphp
                    <div class="space-y-3 rounded-control border-2 border-amber-300 bg-amber-50 p-3">
                        <p class="text-sm font-bold">Adelanto por verificar · S/ {{ number_format((float) $expected, 2) }}</p>
                        <a href="{{ route('admin.reservations.proof', $reservation) }}" target="_blank" rel="noopener" class="block">
                            <img src="{{ route('admin.reservations.proof', $reservation) }}" alt="Captura del adelanto de la reserva {{ $reservation->code }}"
                                 class="max-h-80 w-full rounded-control bg-white object-contain ring-1 ring-line">
                            <span class="mt-1 inline-block text-xs font-semibold text-brand-strong">Abrir en tamaño completo (pestaña nueva)</span>
                        </a>
                        <form wire:submit="approveDeposit" class="flex flex-wrap items-end gap-2">
                            <div class="min-w-32 flex-1">
                                <label for="depositMethod" class="block text-sm font-medium">Llegó por</label>
                                <select id="depositMethod" wire:model="depositMethod" class="mt-1 block min-h-11 w-full rounded-control border border-stone-300 bg-white px-3">
                                    @foreach ($methods as $method)
                                        <option value="{{ $method->value }}">{{ $method->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <x-ui.button type="submit" wire:loading.attr="disabled" wire:confirm="¿Confirmas que el adelanto llegó a la cuenta?">
                                <x-ui.icon name="check" class="size-5" /> Aceptar
                            </x-ui.button>
                        </form>
                        <details>
                            <summary class="cursor-pointer text-sm font-semibold text-danger">Rechazar captura</summary>
                            <form wire:submit="rejectDeposit" class="mt-2 space-y-2">
                                <label for="rejectReason" class="block text-sm font-medium">Motivo (lo verá el cliente)</label>
                                <input id="rejectReason" type="text" wire:model="rejectReason" placeholder="Ej.: el pago no llegó a la cuenta" class="block min-h-11 w-full rounded-control border border-stone-300 bg-white px-3">
                                @error('rejectReason')<p class="text-sm text-danger">{{ $message }}</p>@enderror
                                <x-ui.button type="submit" variant="secondary" class="text-danger!" wire:confirm="¿Rechazar la captura y cancelar la reserva?">Rechazar y cancelar</x-ui.button>
                            </form>
                        </details>
                    </div>
                @endif

                @if ($canPay)
                    <form wire:submit="registerPayment" class="grid gap-3 rounded-control bg-stone-50 p-3 sm:grid-cols-3">
                        <p class="text-sm font-semibold sm:col-span-3">{{ $reservation->hasPayments() ? 'Cobrar saldo' : 'Registrar adelanto' }}</p>
                        <div>
                            <label for="paymentAmount" class="block text-sm font-medium">Monto (S/)</label>
                            <input id="paymentAmount" type="number" step="0.01" min="0.01" inputmode="decimal" wire:model="paymentAmount" class="mt-1 block min-h-11 w-full rounded-control border border-stone-300 bg-white px-3">
                            @error('paymentAmount')<p class="mt-1 text-sm text-danger">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="paymentMethod" class="block text-sm font-medium">Método</label>
                            <select id="paymentMethod" wire:model="paymentMethod" class="mt-1 block min-h-11 w-full rounded-control border border-stone-300 bg-white px-3">
                                @foreach ($methods as $method)
                                    <option value="{{ $method->value }}">{{ $method->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="paymentReference" class="block text-sm font-medium">N.º de operación</label>
                            <input id="paymentReference" type="text" wire:model="paymentReference" class="mt-1 block min-h-11 w-full rounded-control border border-stone-300 bg-white px-3">
                            @error('paymentReference')<p class="mt-1 text-sm text-danger">{{ $message }}</p>@enderror
                        </div>
                        <x-ui.button type="submit" class="sm:col-span-3" wire:loading.attr="disabled" wire:confirm="¿Confirmas que verificaste este pago?">
                            Registrar pago
                        </x-ui.button>
                    </form>
                @endif

                @if ($canDeliver)
                    <x-ui.button class="w-full" wire:click="deliver" wire:loading.attr="disabled" wire:confirm="¿Registrar la entrega del pedido completo?" :disabled="! $reservation->isFullyPaid()">
                        <x-ui.icon name="check" class="size-5" /> Registrar entrega
                    </x-ui.button>
                    @unless ($reservation->isFullyPaid())
                        <p class="-mt-2 text-xs text-muted">Se habilita cuando el saldo esté cobrado.</p>
                    @endunless
                @endif

                @if ($canDonate)
                    <x-ui.button variant="secondary" class="w-full" wire:click="markDonated" wire:confirm="¿Registrar este pedido no recogido como donación?">
                        Registrar como donación
                    </x-ui.button>
                @endif

                @if ($canCancel && ! $canReview)
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

                @if (! $canReview && ! $canPay && ! $canDeliver && ! $canDonate && ! $canCancel)
                    <p class="text-sm text-muted">No hay acciones disponibles para tu rol en el estado actual.</p>
                @endif
            </div>
        @endif
    @endauth
</section>
