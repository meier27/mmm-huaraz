<div>
    <x-admin.page-header title="Ofertas y stock" description="Cada oferta es un plato en una fecha y horario, con su precio y porciones disponibles.">
        <x-slot:actions>
            <x-ui.button variant="secondary" href="{{ route('admin.dishes') }}" wire:navigate>Platos</x-ui.button>
            <x-ui.button wire:click="create" :disabled="$dishes->isEmpty()">Nueva oferta</x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    @error('delete')<x-ui.alert type="error" class="mb-6">{{ $message }}</x-ui.alert>@enderror

    @if ($showForm)
        <x-admin.form-panel :title="$editingId ? 'Editar oferta' : 'Nueva oferta'">
            <x-admin.select label="Plato" model="form.dish_id" placeholder="Selecciona un plato" :options="$dishes->pluck('name', 'id')" class="sm:col-span-2" />
            <x-admin.input label="Fecha" model="form.serve_date" type="date" />
            <div class="grid grid-cols-2 gap-3">
                <x-admin.input label="Desde" model="form.slot_start" type="time" />
                <x-admin.input label="Hasta (opcional)" model="form.slot_end" type="time" />
            </div>
            <x-admin.input label="Precio (S/)" model="form.price" type="number" step="0.10" min="0.10" inputmode="decimal"
                           hint="Cambiarlo no afecta a las reservas ya hechas." />
            <x-admin.input label="Porciones disponibles (stock)" model="form.stock" type="number" min="0" inputmode="numeric" />
            <x-admin.checkbox label="Activa (visible para reservar)" model="form.is_active" />
        </x-admin.form-panel>
    @endif

    <div class="space-y-6">
        @forelse ($groups as $date => $offerings)
            <section wire:key="offer-date-{{ $date }}" class="overflow-hidden rounded-card bg-white shadow-card">
                <h2 class="border-b border-line px-4 py-3 text-lg font-bold">{{ $offerings->first()->dateLabel() }}</h2>
                <ul class="divide-y divide-line">
                    @foreach ($offerings as $offering)
                        @php($percent = $offering->stock > 0 ? min(100, round($offering->reserved / $offering->stock * 100)) : 0)
                        <li wire:key="offering-{{ $offering->id }}" class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                            <div class="min-w-0 flex-1">
                                <p class="font-semibold">{{ $offering->dish->name }} <span class="text-sm font-normal text-muted">· {{ $offering->slotLabel() }} · S/ {{ number_format((float) $offering->price, 2) }}</span></p>
                                <div class="mt-2 flex items-center gap-3">
                                    <div class="h-2 w-40 overflow-hidden rounded-full bg-stone-100" role="progressbar" aria-valuenow="{{ $offering->reserved }}" aria-valuemin="0" aria-valuemax="{{ $offering->stock }}" aria-label="Porciones reservadas">
                                        <div @class(['h-full rounded-full', 'bg-success' => $percent < 80, 'bg-warning' => $percent >= 80 && $percent < 100, 'bg-danger' => $percent >= 100]) style="width: {{ $percent }}%"></div>
                                    </div>
                                    <span class="text-sm tabular-nums text-muted">{{ $offering->reserved }} / {{ $offering->stock }} reservadas</span>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" wire:click="toggleActive({{ $offering->id }})"
                                        @class(['inline-flex min-h-9 items-center rounded-full px-3 text-xs font-semibold', 'bg-green-100 text-success' => $offering->is_active, 'bg-stone-100 text-muted' => ! $offering->is_active])>
                                    {{ $offering->is_active ? 'Activa' : 'Inactiva' }}
                                </button>
                                <x-admin.row-actions :id="$offering->id" />
                            </div>
                        </li>
                    @endforeach
                </ul>
            </section>
        @empty
            <x-ui.empty-state>Aún no hay ofertas. Crea primero los platos y luego sus ofertas por fecha y horario.</x-ui.empty-state>
        @endforelse
    </div>
</div>
