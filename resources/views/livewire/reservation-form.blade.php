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

                    @include("livewire.partials.offering-picker")
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

                    <div class="mt-5 rounded-control bg-cream p-4">
                        <p class="mb-2 text-sm font-bold text-earth">Antes de reservar</p>
                        @include('reservations.partials.rules')
                    </div>

                    @if ($settings->reservation_instructions)
                        <p class="mt-3 rounded-control border border-line p-3 text-sm whitespace-pre-line">{{ $settings->reservation_instructions }}</p>
                    @endif
                </aside>
            </form>
        @endif
    </div>
</div>
