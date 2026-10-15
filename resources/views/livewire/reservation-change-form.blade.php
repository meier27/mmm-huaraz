<div>
    <x-ui.page-intro title="Cambiar mi pedido" :eyebrow="'Reserva '.$reservation->code">
        Elige la nueva comida (por ejemplo, para la noche o el día siguiente). Tu código y tu QR no cambian.
    </x-ui.page-intro>

    <div class="container-content py-10">
        <form wire:submit="save" novalidate
              x-data="{ prices: @js($prices), get total() { return Object.entries($wire.quantities).reduce((sum, [id, qty]) => sum + (this.prices[id] ?? 0) * (parseInt(qty) || 0), 0) } }"
              class="grid gap-8 lg:grid-cols-[1fr_22rem] lg:items-start">

            <div class="space-y-10">
                @error('quantities')
                    <x-ui.alert type="error">{{ $message }}</x-ui.alert>
                @enderror

                @if ($groups->isEmpty())
                    <x-ui.empty-state>No hay otras comidas disponibles para reservar en este momento.</x-ui.empty-state>
                @else
                    @include('livewire.partials.offering-picker')
                @endif
            </div>

            <aside class="rounded-card bg-white p-5 shadow-card lg:sticky lg:top-24" aria-labelledby="resumen-cambio">
                <h2 id="resumen-cambio" class="text-xl font-bold">Tu pedido actual</h2>
                <ul class="mt-3 space-y-1 text-sm">
                    @foreach ($reservation->items as $item)
                        <li>{{ $item->quantity }} × {{ $item->offering->dish->name }} <span class="text-muted">· {{ $item->offering->dateLabel() }} {{ $item->offering->slotLabel() }}</span></li>
                    @endforeach
                </ul>

                <dl class="mt-4 space-y-1 border-t border-line pt-3 text-sm">
                    <div class="flex justify-between"><dt class="text-muted">Ya pagado</dt><dd class="font-semibold tabular-nums">S/ {{ number_format((float) $reservation->amount_paid, 2) }}</dd></div>
                    <div class="flex items-baseline justify-between"><dt class="text-muted">Nuevo total</dt><dd class="font-display text-2xl font-extrabold text-earth tabular-nums" aria-live="polite">S/ <span x-text="total.toFixed(2)">0.00</span></dd></div>
                </dl>
                @if ($reservation->hasPayments())
                    <p class="mt-1 text-xs text-muted">El nuevo pedido debe costar al menos lo que ya pagaste; la diferencia se paga al recoger.</p>
                @endif

                <x-ui.button type="submit" class="mt-4 w-full" wire:loading.attr="disabled" wire:target="save" x-bind:disabled="total <= 0"
                             wire:confirm="¿Reemplazar tu pedido actual por esta selección?">
                    <span wire:loading.remove wire:target="save">Guardar cambio</span>
                    <span wire:loading wire:target="save">Guardando…</span>
                </x-ui.button>
                <x-ui.button href="{{ route('reservations.show', $token) }}" variant="ghost" class="mt-2 w-full">Volver sin cambiar</x-ui.button>

                <p class="mt-4 text-xs text-muted">
                    Plazo para cambios: hasta el {{ $reservation->changeDeadline()?->translatedFormat('j \d\e F, H:i') }}.
                </p>
            </aside>
        </form>
    </div>
</div>
