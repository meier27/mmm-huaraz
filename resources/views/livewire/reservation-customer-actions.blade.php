<div>
    @if ($reservation->customerCanChange())
        <section class="rounded-card border border-line bg-white p-5" aria-labelledby="cambios-titulo">
            <h2 id="cambios-titulo" class="text-lg font-bold">¿No podrás recogerlo?</h2>
            <p class="mt-1 text-sm text-muted">
                Puedes cambiar tu pedido por otra comida hasta el
                <strong class="text-ink">{{ $reservation->changeDeadline()->translatedFormat('l j \d\e F, H:i') }}</strong>.
                @if ($reservation->hasPayments()) Lo que ya pagaste se mantiene. @endif
            </p>

            @error('customer')<x-ui.alert type="error" class="mt-3">{{ $message }}</x-ui.alert>@enderror

            <div class="mt-4 flex flex-wrap gap-2">
                <x-ui.button href="{{ route('reservations.change', $token) }}">Cambiar mi pedido</x-ui.button>
                @unless ($reservation->hasPayments())
                    <x-ui.button variant="secondary" class="text-danger!" wire:click="cancel" wire:confirm="¿Cancelar tu reserva? Esta acción no se puede deshacer.">
                        Cancelar reserva
                    </x-ui.button>
                @endunless
            </div>
        </section>
    @elseif (! $reservation->isClosed() && $reservation->changeDeadline())
        <x-ui.alert>
            El plazo para cambiar este pedido terminó el {{ $reservation->changeDeadline()->translatedFormat('j \d\e F, H:i') }}.
            Si no lo recoges, se destinará a donación.
        </x-ui.alert>
    @endif
</div>
