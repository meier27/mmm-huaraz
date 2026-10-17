<div>
    <x-admin.page-header title="Banners de portada" description="Diapositivas del carrusel principal. Usa solo fotografías con autorización de uso (recomendado 1920 × 900 px).">
        <x-slot:actions>
            <x-ui.button wire:click="create">Nuevo banner</x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    @if ($showForm)
        <x-admin.form-panel :title="$editingId ? 'Editar banner' : 'Nuevo banner'">
            <x-admin.input label="Título (opcional)" model="form.title" class="sm:col-span-2" hint="Déjalo vacío si la imagen ya lleva su propio texto." />
            <x-admin.input label="Subtítulo (opcional)" model="form.subtitle" class="sm:col-span-2" />
            <div class="sm:col-span-2">
                <x-admin.input label="Imagen (JPG, PNG o WebP, mín. 1200 px de ancho, máx. 4 MB)" model="form.image" type="file" accept="image/jpeg,image/png,image/webp" />
                <div wire:loading wire:target="form.image" class="mt-1 text-sm text-muted">Subiendo imagen…</div>
                @if ($editing?->imageUrl() && ! $form->image)
                    <div class="mt-3 flex items-center gap-3">
                        <img src="{{ $editing->imageUrl() }}" alt="" class="h-16 w-32 rounded-control object-cover" width="128" height="64">
                        <x-ui.button variant="ghost" class="text-sm text-danger!" wire:click="removeImage({{ $editing->id }})" wire:confirm="¿Quitar la imagen actual?">Quitar imagen</x-ui.button>
                    </div>
                @endif
            </div>
            <x-admin.input label="Texto alternativo de la imagen" model="form.image_alt" class="sm:col-span-2" />
            <x-admin.input label="Texto del botón (opcional)" model="form.cta_label" hint="Por ejemplo, «Ver programa»." />
            <x-admin.input label="Enlace del botón" model="form.cta_url" hint="Ruta interna (/programa) o https://…" />
            <x-admin.input label="Orden" model="form.sort_order" type="number" min="0" />
            <x-admin.checkbox label="Publicado" model="form.is_published" />
        </x-admin.form-panel>
    @endif

    <ul class="divide-y divide-line rounded-card bg-white shadow-card">
        @forelse ($records as $record)
            <li wire:key="banner-{{ $record->id }}" class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex min-w-0 items-center gap-3">
                    @if ($record->imageUrl())
                        <img src="{{ $record->imageUrl() }}" alt="" class="h-12 w-24 shrink-0 rounded-control object-cover" width="96" height="48" loading="lazy">
                    @else
                        <div class="flex h-12 w-24 shrink-0 items-center justify-center rounded-control bg-linear-to-br from-primary to-brand-strong text-xs text-white">Sin imagen</div>
                    @endif
                    <div class="min-w-0">
                        <p class="font-semibold">{{ $record->title ?: 'Sin título (solo imagen)' }}</p>
                        <p class="truncate text-sm text-muted">{{ $record->subtitle ?: 'Sin subtítulo' }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <x-admin.publish-toggle :record="$record" />
                    <x-admin.row-actions :id="$record->id" />
                </div>
            </li>
        @empty
            <li class="p-6 text-center text-muted">Aún no hay banners. Mientras tanto, la portada muestra un diseño predeterminado con los datos de la convención.</li>
        @endforelse
    </ul>
</div>
