<div>
    <x-admin.page-header title="Sedes y ubicaciones" description="Sede principal, iglesia anfitriona y otros puntos. Usa solo direcciones y coordenadas confirmadas.">
        <x-slot:actions>
            <x-ui.button wire:click="create">Nueva ubicación</x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    @if ($showForm)
        <x-admin.form-panel :title="$editingId ? 'Editar ubicación' : 'Nueva ubicación'">
            <x-admin.select label="Tipo" model="form.kind" :options="collect($kinds)->mapWithKeys(fn ($k) => [$k->value => $k->label()])" />
            <x-admin.input label="Nombre" model="form.name" />
            <x-admin.input label="Dirección" model="form.address" class="sm:col-span-2" />
            <div class="sm:col-span-2">
                <x-admin.textarea label="Cómo llegar" model="form.directions" hint="Referencias, transporte, accesos y estacionamiento." />
            </div>
            <x-admin.input label="Latitud" model="form.latitude" inputmode="decimal" hint="Formato decimal, p. ej. -9.1234567" />
            <x-admin.input label="Longitud" model="form.longitude" inputmode="decimal" hint="Formato decimal, p. ej. -77.1234567" />
            <x-admin.input label="Enlace de Google Maps" model="form.maps_url" type="url" class="sm:col-span-2" />
            <x-admin.input label="Orden" model="form.sort_order" type="number" min="0" />
            <x-admin.checkbox label="Publicado" model="form.is_published" />
        </x-admin.form-panel>
    @endif

    <ul class="divide-y divide-line rounded-card bg-white shadow-card">
        @forelse ($records as $record)
            <li wire:key="venue-{{ $record->id }}" class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <p class="font-semibold">{{ $record->name }} <span class="text-sm font-normal text-muted">· {{ $record->kind->label() }}</span></p>
                    <p class="mt-1 text-sm text-muted">
                        {{ $record->address ?: 'Dirección pendiente de confirmar' }}
                        · {{ $record->hasCoordinates() ? 'Con coordenadas' : 'Sin coordenadas' }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <x-admin.publish-toggle :record="$record" />
                    <x-admin.row-actions :id="$record->id" confirm="¿Eliminar esta ubicación? Las actividades asociadas quedarán sin lugar asignado." />
                </div>
            </li>
        @empty
            <li class="p-6 text-center text-muted">Aún no hay ubicaciones registradas.</li>
        @endforelse
    </ul>
</div>
