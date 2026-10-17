<div>
    <x-admin.event-tabs :event="$this->event" />

    <x-admin.page-header title="Hospedaje" description="Opciones de alojamiento aprobadas por la organización.">
        <x-slot:actions>
            <x-ui.button wire:click="create">Nuevo hospedaje</x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    @if ($showForm)
        <x-admin.form-panel :title="$editingId ? 'Editar hospedaje' : 'Nuevo hospedaje'">
            <x-admin.select label="Tipo" model="form.type" :options="collect($types)->mapWithKeys(fn ($t) => [$t->value => $t->label()])" />
            <x-admin.input label="Nombre" model="form.name" />
            <x-admin.input label="Dirección" model="form.address" class="sm:col-span-2" />
            <x-admin.input label="Teléfono" model="form.phone" type="tel" />
            <x-admin.input label="Rango de precios" model="form.price_range" hint="Texto libre, p. ej. «Desde S/ …»." />
            <x-admin.input label="Sitio web" model="form.website_url" type="url" />
            <x-admin.input label="Enlace de Google Maps" model="form.maps_url" type="url" />
            <div class="sm:col-span-2">
                <x-admin.textarea label="Notas" model="form.notes" rows="3" />
            </div>
            <x-admin.input label="Orden" model="form.sort_order" type="number" min="0" />
            <x-admin.checkbox label="Publicado" model="form.is_published" />
        </x-admin.form-panel>
    @endif

    <ul class="divide-y divide-line rounded-card bg-white shadow-card">
        @forelse ($records as $record)
            <li wire:key="accommodation-{{ $record->id }}" class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <p class="font-semibold">{{ $record->name }} <span class="text-sm font-normal text-muted">· {{ $record->type->label() }}</span></p>
                    <p class="mt-1 text-sm text-muted">{{ collect([$record->address, $record->phone, $record->price_range])->filter()->join(' · ') ?: 'Sin detalles' }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <x-admin.publish-toggle :record="$record" />
                    <x-admin.row-actions :id="$record->id" />
                </div>
            </li>
        @empty
            <li class="p-6 text-center text-muted">Aún no hay opciones de hospedaje registradas.</li>
        @endforelse
    </ul>
</div>
