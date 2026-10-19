<x-layouts.admin title="Reserva {{ $reservation->code }}">
    <a href="{{ route('admin.reservations') }}" wire:navigate class="inline-flex items-center gap-1 text-sm font-semibold text-brand-strong">
        <x-ui.icon name="chevron-left" class="size-4" /> Reservas
    </a>

    <div class="mt-3 grid grid-cols-1 gap-6 lg:grid-cols-[1fr_20rem]">
        <div class="space-y-6">
            <div class="rounded-card bg-white p-5 shadow-card">
                <p class="text-sm text-muted">Código</p>
                <h1 class="font-mono text-3xl font-extrabold tracking-widest text-brand-strong">{{ $reservation->code }}</h1>
                <p class="mt-2">{{ $reservation->customer_name }} · <a href="tel:{{ preg_replace('/[^0-9+]/', '', $reservation->customer_phone) }}" class="font-semibold text-brand-strong">{{ $reservation->customer_phone }}</a></p>
                <p class="text-sm text-muted">Creada el {{ $reservation->created_at->translatedFormat('d/m/Y H:i') }}</p>

                <ul class="mt-4 divide-y divide-line">
                    @foreach ($reservation->items as $item)
                        <li class="flex justify-between gap-4 py-2">
                            <span><strong>{{ $item->quantity }} ×</strong> {{ $item->offering->dish->name }} <span class="text-sm text-muted">· {{ $item->offering->dateLabel() }} · {{ $item->offering->slotLabel() }}</span></span>
                            <span class="tabular-nums">S/ {{ number_format((float) $item->line_total, 2) }}</span>
                        </li>
                    @endforeach
                </ul>
                <p class="mt-2 flex justify-between border-t border-line pt-2 font-bold"><span>Total</span><span class="tabular-nums">S/ {{ number_format((float) $reservation->total, 2) }}</span></p>
                @if ($reservation->notes)
                    <p class="mt-3 text-sm"><span class="font-semibold">Observaciones:</span> {{ $reservation->notes }}</p>
                @endif
            </div>

            <livewire:reservation-status-panel :reservation-id="$reservation->id" />
        </div>

        <aside class="self-start rounded-card bg-white p-5 shadow-card" x-data="{ copied: false }">
            <h2 class="font-bold">Enlace del cliente</h2>
            @if ($publicUrl)
                <p class="mt-1 text-sm text-muted">Compártelo solo con el titular si perdió el enlace de su reserva.</p>
                <x-ui.button variant="secondary" class="mt-3 w-full"
                             x-on:click="navigator.clipboard?.writeText(@js($publicUrl)).then(() => { copied = true; setTimeout(() => copied = false, 2000) })">
                    <span x-show="!copied">Copiar enlace</span><span x-show="copied" x-cloak role="status">Copiado</span>
                </x-ui.button>
            @else
                <p class="mt-1 text-sm text-warning">No se puede recuperar el enlace: la reserva se cifró con otra clave del servidor (APP_KEY). El cliente puede identificarse con su código.</p>
            @endif
        </aside>
    </div>
</x-layouts.admin>
