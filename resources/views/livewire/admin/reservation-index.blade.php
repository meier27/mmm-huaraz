<div>
    <x-admin.page-header title="Reservas" description="Busca por código, nombre o celular. Escanear el QR con la cámara del móvil abre directamente la reserva." />

    <div class="mb-4 grid gap-3 rounded-card bg-white p-4 shadow-card sm:grid-cols-2 lg:grid-cols-4">
        <div class="sm:col-span-2 lg:col-span-1">
            <label for="search" class="block text-sm font-medium">Buscar</label>
            <input id="search" type="search" wire:model.live.debounce.400ms="search" placeholder="Código, nombre o celular"
                   class="mt-1 block min-h-11 w-full rounded-control border border-stone-300 px-3">
        </div>
        @foreach (['status' => ['Estado', $statuses], 'payment' => ['Pago', $payments], 'delivery' => ['Entrega', $deliveries]] as $field => [$fieldLabel, $cases])
            <div>
                <label for="{{ $field }}" class="block text-sm font-medium">{{ $fieldLabel }}</label>
                <select id="{{ $field }}" wire:model.live="{{ $field }}" class="mt-1 block min-h-11 w-full rounded-control border border-stone-300 bg-white px-3">
                    <option value="">Todos</option>
                    @foreach ($cases as $case)
                        <option value="{{ $case->value }}">{{ $case->label() }}</option>
                    @endforeach
                </select>
            </div>
        @endforeach
    </div>

    <div class="overflow-hidden rounded-card bg-white shadow-card" wire:loading.class="opacity-60">
        <ul class="divide-y divide-line">
            @forelse ($reservations as $reservation)
                <li wire:key="res-{{ $reservation->id }}">
                    <a href="{{ route('admin.reservations.show', $reservation) }}" wire:navigate class="flex flex-col gap-2 p-4 hover:bg-stone-50 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0">
                            <p class="font-mono font-bold tracking-wider text-brand-strong">{{ $reservation->code }}</p>
                            <p class="truncate">{{ $reservation->customer_name }} <span class="text-sm text-muted">· {{ $reservation->customer_phone }}</span></p>
                            <p class="text-xs text-muted">{{ $reservation->created_at->translatedFormat('d/m H:i') }} · {{ (int) $reservation->portions }} porciones</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2 text-xs font-semibold">
                            <span class="font-display text-base font-bold text-earth tabular-nums">S/ {{ number_format((float) $reservation->total, 2) }}</span>
                            <span @class(['rounded-full px-2 py-1', 'bg-amber-100 text-warning' => $reservation->status->value === 'pending', 'bg-green-100 text-success' => $reservation->status->value === 'confirmed', 'bg-red-100 text-danger' => $reservation->isCancelled()])>{{ $reservation->status->label() }}</span>
                            @unless ($reservation->isCancelled())
                                <span @class(['rounded-full px-2 py-1', 'bg-green-100 text-success' => $reservation->isDelivered(), 'bg-stone-100 text-muted' => ! $reservation->isDelivered()])>{{ $reservation->delivery_status->label() }}</span>
                            @endunless
                        </div>
                    </a>
                </li>
            @empty
                <li class="p-6 text-center text-muted">No se encontraron reservas.</li>
            @endforelse
        </ul>
    </div>

    <div class="mt-4">{{ $reservations->links() }}</div>
</div>
