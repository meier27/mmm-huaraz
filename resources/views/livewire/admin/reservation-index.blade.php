<div>
    <x-admin.page-header title="Reservas" description="Busca por código, nombre o celular. Las reservas «Por verificar» esperan que revises la captura del adelanto.">
        <x-slot:actions>
            <x-ui.button href="{{ route('admin.reservations.verify') }}" wire:navigate>
                <x-ui.icon name="search" class="size-5" /> Verificar código
            </x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    @php
        $activeFilters = collect([$event, $status, $payment, $delivery])->filter()->count();
    @endphp
    <div class="mb-4 grid gap-3 rounded-card bg-white p-4 shadow-card sm:grid-cols-2 lg:grid-cols-5" x-data="{ open: false }">
        <div class="sm:col-span-2 lg:col-span-1">
            <label for="search" class="block text-sm font-medium">Buscar</label>
            <div class="mt-1 flex gap-2">
                <input id="search" type="search" wire:model.live.debounce.400ms="search" placeholder="Código, nombre o celular"
                       class="block min-h-11 w-full min-w-0 rounded-control border border-stone-300 px-3">
                {{-- En móvil los filtros se pliegan para que la lista quede a la vista. --}}
                <button type="button" x-on:click="open = ! open" x-bind:aria-expanded="open.toString()" aria-controls="filtros-reservas"
                        class="inline-flex min-h-11 shrink-0 items-center gap-1.5 rounded-control border border-stone-300 px-3 text-sm font-semibold sm:hidden">
                    <x-ui.icon name="list" class="size-4" /> Filtros
                    @if ($activeFilters)
                        <span class="inline-flex size-5 items-center justify-center rounded-full bg-brand-strong text-xs text-white">{{ $activeFilters }}</span>
                    @endif
                </button>
            </div>
        </div>
        <div id="filtros-reservas" x-bind:class="{ 'grid!': open }" class="hidden gap-3 sm:contents">
        <div>
            <label for="event" class="block text-sm font-medium">Evento</label>
            <select id="event" wire:model.live="event" class="mt-1 block min-h-11 w-full rounded-control border border-stone-300 bg-white px-3">
                <option value="">Todos</option>
                @foreach ($events as $id => $eventTitle)
                    <option value="{{ $id }}">{{ $eventTitle }}</option>
                @endforeach
            </select>
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
    </div>

    <div class="overflow-hidden rounded-card bg-white shadow-card" wire:loading.class="opacity-60">
        <ul class="divide-y divide-line">
            @forelse ($reservations as $reservation)
                <li wire:key="res-{{ $reservation->id }}">
                    <a href="{{ route('admin.reservations.show', $reservation) }}" wire:navigate class="flex flex-col gap-2 p-4 hover:bg-stone-50 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0">
                            <p class="font-mono font-bold tracking-wider text-brand-strong">{{ $reservation->code }}</p>
                            <p class="truncate">{{ $reservation->customer_name }} <span class="text-sm text-muted">· {{ $reservation->customer_phone }}</span></p>
                            <p class="text-xs text-muted">{{ $reservation->created_at->translatedFormat('d/m H:i') }} · {{ (int) $reservation->portions }} porciones
                                @if ($reservation->awaitsDepositReview())
                                    · <span class="font-semibold text-warning">captura por revisar</span>
                                @endif
                            </p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2 text-xs font-semibold">
                            <span class="font-display text-base font-bold text-primary tabular-nums">S/ {{ number_format((float) $reservation->total, 2) }}</span>
                            <span @class(['rounded-full px-2 py-1', 'bg-amber-100 text-warning' => $reservation->status->value === 'pending', 'bg-green-100 text-success' => $reservation->status->value === 'confirmed', 'bg-red-100 text-danger' => $reservation->isCancelled()])>{{ $reservation->status->label() }}</span>
                            @unless ($reservation->isCancelled())
                                <span @class(['rounded-full px-2 py-1', 'bg-green-100 text-success' => $reservation->isFullyPaid(), 'bg-sky-100 text-primary' => $reservation->payment_status->value === 'partial', 'bg-stone-100 text-muted' => ! $reservation->hasPayments()])>{{ $reservation->payment_status->label() }}</span>
                                <span @class(['rounded-full px-2 py-1', 'bg-green-100 text-success' => $reservation->isDelivered(), 'bg-violet-100 text-violet-800' => $reservation->isDonated(), 'bg-stone-100 text-muted' => $reservation->delivery_status->value === 'pending'])>{{ $reservation->delivery_status->label() }}</span>
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
