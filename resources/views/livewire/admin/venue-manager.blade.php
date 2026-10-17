<div>
    <x-admin.event-tabs :event="$this->event" />

    <x-admin.page-header title="Sedes y ubicaciones" description="Sede principal, iglesia anfitriona y otros puntos. Usa solo direcciones y coordenadas confirmadas.">
        <x-slot:actions>
            <x-ui.button wire:click="create">Nueva ubicación</x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    @if ($showForm)
        <x-admin.form-panel :title="$editingId ? 'Editar ubicación' : 'Nueva ubicación'">
            <x-admin.select label="Tipo" model="form.kind" :options="collect($kinds)->mapWithKeys(fn ($k) => [$k->value => $k->label()])" />
            <x-admin.input label="Nombre" model="form.name" />
            <x-admin.input label="Dirección" model="form.address" class="sm:col-span-2" hint="Incluye la ciudad, p. ej. «Av. Luzuriaga 234, Huaraz»: el mapa la busca si no hay coordenadas." />
            <div class="sm:col-span-2">
                <x-admin.textarea label="Cómo llegar" model="form.directions" hint="Referencias, transporte, accesos y estacionamiento." />
            </div>
            <x-admin.input label="Enlace de Google Maps" model="form.maps_url" type="url" class="sm:col-span-2"
                           hint="En Google Maps, busca el lugar › Compartir › Copiar enlace, y pégalo aquí. Si dejas la latitud y longitud vacías, se toman de este enlace cuando es posible." />
            <x-admin.input label="Latitud (opcional)" model="form.latitude" inputmode="decimal" hint="Formato decimal, p. ej. -9.5277000" />
            <x-admin.input label="Longitud (opcional)" model="form.longitude" inputmode="decimal" hint="Formato decimal, p. ej. -77.5278000" />
            <p class="-mt-2 text-xs text-muted sm:col-span-2">El mapa de la web marca las coordenadas exactas; si no hay, busca la dirección.</p>
            <div class="sm:col-span-2">
                <x-admin.input label="Foto del lugar (opcional, JPG, PNG o WebP, máx. 4 MB)" model="form.image" type="file" accept="image/jpeg,image/png,image/webp" />
                <div wire:loading wire:target="form.image" class="mt-1 text-sm text-muted">Subiendo foto…</div>
                @if ($editing?->imageUrl() && ! $form->image)
                    <div class="mt-3 flex items-center gap-3">
                        <img src="{{ $editing->imageUrl() }}" alt="" class="h-16 w-28 rounded-control object-cover" width="112" height="64">
                        <x-ui.button variant="ghost" class="text-sm text-danger!" wire:click="removeImage({{ $editing->id }})" wire:confirm="¿Quitar la foto actual?">Quitar foto</x-ui.button>
                    </div>
                @endif
            </div>
            <x-admin.input label="Descripción de la foto" model="form.image_alt" class="sm:col-span-2" hint="Obligatoria si subes foto, p. ej. «Entrada principal del estadio»." />
            <x-admin.input label="Orden" model="form.sort_order" type="number" min="0" />
            <x-admin.checkbox label="Publicado" model="form.is_published" />
        </x-admin.form-panel>
    @endif

    <ul class="divide-y divide-line rounded-card bg-white shadow-card">
        @forelse ($records as $record)
            <li wire:key="venue-{{ $record->id }}" class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex min-w-0 items-center gap-3">
                    @if ($record->imageUrl())
                        <img src="{{ $record->imageUrl() }}" alt="" class="h-12 w-16 shrink-0 rounded-control object-cover" width="64" height="48" loading="lazy">
                    @else
                        <span class="flex h-12 w-16 shrink-0 items-center justify-center rounded-control bg-cream text-brand-strong"><x-ui.icon name="map-pin" class="size-5" /></span>
                    @endif
                <div class="min-w-0">
                    <p class="font-semibold">{{ $record->name }} <span class="text-sm font-normal text-muted">· {{ $record->kind->label() }}</span></p>
                    <p class="mt-1 text-sm text-muted">
                        {{ $record->address ?: 'Dirección pendiente de confirmar' }}
                        · {{ $record->hasCoordinates() ? 'Con coordenadas' : 'Sin coordenadas' }}
                    </p>
                </div>
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
