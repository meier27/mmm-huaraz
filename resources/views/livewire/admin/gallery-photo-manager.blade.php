<div>
    <x-admin.page-header title="Fotos de «Nuestra familia»" description="Collage de la portada: se muestran las {{ $slots }} primeras fotos publicadas según su orden (la primera es la grande). Usa solo fotografías con autorización de uso.">
        <x-slot:actions>
            <x-ui.button wire:click="create">Nueva foto</x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    @if ($showForm)
        <x-admin.form-panel :title="$editingId ? 'Cambiar foto' : 'Nueva foto'">
            <div class="sm:col-span-2">
                <x-admin.input :label="($editingId ? 'Reemplazar foto' : 'Foto').' (JPG, PNG o WebP, mín. 600 px de ancho, máx. 4 MB)'" model="form.image" type="file" accept="image/jpeg,image/png,image/webp" />
                <div wire:loading wire:target="form.image" class="mt-1 text-sm text-muted">Subiendo foto…</div>
                @if ($editing?->imageUrl() && ! $form->image)
                    <img src="{{ $editing->imageUrl() }}" alt="" class="mt-3 size-32 rounded-card object-cover" width="128" height="128">
                @endif
            </div>
            <x-admin.input label="Descripción de la foto" model="form.image_alt" class="sm:col-span-2" hint="Para lectores de pantalla, p. ej. «Familias en el culto de jóvenes»." />
            <x-admin.input label="Orden" model="form.sort_order" type="number" min="0" hint="El menor va en el hueco grande." />
            <x-admin.checkbox label="Publicada" model="form.is_published" />
        </x-admin.form-panel>
    @endif

    <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @forelse ($records as $record)
            <li wire:key="photo-{{ $record->id }}" class="flex flex-col overflow-hidden rounded-card bg-white shadow-card">
                @if ($record->imageUrl())
                    <img src="{{ $record->imageUrl() }}" alt="" class="aspect-4/3 w-full object-cover" width="320" height="240" loading="lazy">
                @else
                    <div class="flex aspect-4/3 items-center justify-center bg-sand text-sm text-muted">Sin foto</div>
                @endif
                <div class="flex flex-1 flex-col gap-3 p-4">
                    <div>
                        <p class="line-clamp-2 text-sm font-semibold">{{ $record->image_alt }}</p>
                        <p class="mt-1 text-xs text-muted">Orden {{ $record->sort_order }} · {{ $onHome->contains($record->id) ? 'En la portada' : 'No se muestra' }}</p>
                    </div>
                    <div class="mt-auto flex flex-wrap items-center gap-2">
                        <x-admin.publish-toggle :record="$record" />
                        <x-admin.row-actions :id="$record->id" />
                    </div>
                </div>
            </li>
        @empty
            <li class="rounded-card bg-white p-6 text-center text-muted shadow-card sm:col-span-2 lg:col-span-4">
                Aún no hay fotos. Mientras tanto, la portada usa las imágenes de los eventos, las enseñanzas y los banners.
            </li>
        @endforelse
    </ul>
</div>
