<div>
    <x-admin.event-tabs :event="$this->event" />

    <x-admin.page-header title="Platos" description="Catálogo de comidas. El precio, la fecha y el stock se definen en «Ofertas y stock».">
        <x-slot:actions>
            <x-ui.button variant="secondary" href="{{ route('admin.events.offerings', $this->event) }}" wire:navigate>Ofertas y stock</x-ui.button>
            <x-ui.button wire:click="create">Nuevo plato</x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    @error('delete')<x-ui.alert type="error" class="mb-6">{{ $message }}</x-ui.alert>@enderror

    @if ($showForm)
        <x-admin.form-panel :title="$editingId ? 'Editar plato' : 'Nuevo plato'">
            <x-admin.input label="Nombre" model="form.name" />
            <x-admin.input label="Alérgenos o ingredientes a destacar" model="form.allergens" />
            <x-admin.textarea label="Descripción" model="form.description" rows="3" class="sm:col-span-2" />
            <div class="sm:col-span-2">
                <x-admin.input label="Foto (JPG, PNG o WebP, máx. 2 MB)" model="form.image" type="file" accept="image/jpeg,image/png,image/webp" />
                <div wire:loading wire:target="form.image" class="mt-1 text-sm text-muted">Subiendo imagen…</div>
                @if ($editing?->imageUrl() && ! $form->image)
                    <div class="mt-3 flex items-center gap-3">
                        <img src="{{ $editing->imageUrl() }}" alt="" class="h-16 w-24 rounded-control object-cover" width="96" height="64">
                        <x-ui.button variant="ghost" class="text-sm text-danger!" wire:click="removeImage({{ $editing->id }})" wire:confirm="¿Quitar la foto actual?">Quitar foto</x-ui.button>
                    </div>
                @endif
            </div>
            <x-admin.input label="Texto alternativo de la foto" model="form.image_alt" class="sm:col-span-2" />
            <x-admin.input label="Orden" model="form.sort_order" type="number" min="0" />
            <x-admin.checkbox label="Publicado" model="form.is_published" />
        </x-admin.form-panel>
    @endif

    <ul class="divide-y divide-line rounded-card bg-white shadow-card">
        @forelse ($records as $record)
            <li wire:key="dish-{{ $record->id }}" class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex min-w-0 items-center gap-3">
                    @if ($record->imageUrl())
                        <img src="{{ $record->imageUrl() }}" alt="" class="h-12 w-16 shrink-0 rounded-control object-cover" width="64" height="48" loading="lazy">
                    @endif
                    <div class="min-w-0">
                        <p class="font-semibold">{{ $record->name }}</p>
                        <p class="text-sm text-muted">{{ $record->offerings_count }} {{ $record->offerings_count === 1 ? 'oferta' : 'ofertas' }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <x-admin.publish-toggle :record="$record" />
                    <x-admin.row-actions :id="$record->id" />
                </div>
            </li>
        @empty
            <li class="p-6 text-center text-muted">Aún no hay platos registrados.</li>
        @endforelse
    </ul>
</div>
