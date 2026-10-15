<div>
    <x-admin.page-header title="Turismo" description="Lugares para conocer Huaraz. Usa solo fotografías con autorización de uso.">
        <x-slot:actions>
            <x-ui.button wire:click="create">Nuevo lugar</x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    @if ($showForm)
        <x-admin.form-panel :title="$editingId ? 'Editar lugar' : 'Nuevo lugar'">
            <x-admin.input label="Nombre" model="form.name" />
            <x-admin.input label="Distancia o tiempo desde Huaraz" model="form.distance_note" />
            <div class="sm:col-span-2">
                <x-admin.textarea label="Descripción" model="form.description" />
            </div>
            <x-admin.input label="Enlace de Google Maps" model="form.maps_url" type="url" class="sm:col-span-2" />

            <div class="sm:col-span-2">
                <x-admin.input label="Imagen (JPG, PNG o WebP, máx. 2 MB)" model="form.image" type="file" accept="image/jpeg,image/png,image/webp" />
                <div wire:loading wire:target="form.image" class="mt-1 text-sm text-muted">Subiendo imagen…</div>
                @if ($editing?->imageUrl() && ! $form->image)
                    <div class="mt-3 flex items-center gap-3">
                        <img src="{{ $editing->imageUrl() }}" alt="" class="h-16 w-24 rounded-control object-cover" width="96" height="64">
                        <x-ui.button variant="ghost" class="text-sm text-danger!" wire:click="removeImage({{ $editing->id }})" wire:confirm="¿Quitar la imagen actual?">Quitar imagen</x-ui.button>
                    </div>
                @endif
            </div>
            <x-admin.input label="Texto alternativo de la imagen" model="form.image_alt" class="sm:col-span-2" hint="Describe la imagen para personas que usan lector de pantalla." />

            <x-admin.input label="Orden" model="form.sort_order" type="number" min="0" />
            <x-admin.checkbox label="Publicado" model="form.is_published" />
        </x-admin.form-panel>
    @endif

    <ul class="divide-y divide-line rounded-card bg-white shadow-card">
        @forelse ($records as $record)
            <li wire:key="place-{{ $record->id }}" class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex min-w-0 items-center gap-3">
                    @if ($record->imageUrl())
                        <img src="{{ $record->imageUrl() }}" alt="" class="h-12 w-16 shrink-0 rounded-control object-cover" width="64" height="48" loading="lazy">
                    @endif
                    <div class="min-w-0">
                        <p class="font-semibold">{{ $record->name }}</p>
                        <p class="mt-1 text-sm text-muted">{{ $record->distance_note ?: 'Sin distancia indicada' }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <x-admin.publish-toggle :record="$record" />
                    <x-admin.row-actions :id="$record->id" />
                </div>
            </li>
        @empty
            <li class="p-6 text-center text-muted">Aún no hay lugares turísticos registrados.</li>
        @endforelse
    </ul>
</div>
