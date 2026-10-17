<div>
    <x-admin.page-header title="Medios" description="Tarjetas de la sección «Medios» de la portada (logotipo + nombre). Usa solo logotipos oficiales.">
        <x-slot:actions>
            <x-ui.button wire:click="create">Nuevo medio</x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    @if ($showForm)
        <x-admin.form-panel :title="$editingId ? 'Editar medio' : 'Nuevo medio'">
            <x-admin.input label="Nombre" model="form.name" hint="Por ejemplo, «Bethel Radio»." />
            <x-admin.input label="Enlace" model="form.url" hint="Ruta interna (/bethel-tv) o https://…" />
            <div class="sm:col-span-2">
                <x-admin.input label="Logotipo o imagen (JPG, PNG o WebP, máx. 4 MB)" model="form.image" type="file" accept="image/jpeg,image/png,image/webp" />
                <div wire:loading wire:target="form.image" class="mt-1 text-sm text-muted">Subiendo imagen…</div>
                @if ($editing?->imageUrl() && ! $form->image)
                    <div class="mt-3 flex items-center gap-3">
                        <img src="{{ $editing->imageUrl() }}" alt="" class="size-16 rounded-control object-cover" width="64" height="64">
                        <x-ui.button variant="ghost" class="text-sm text-danger!" wire:click="removeImage({{ $editing->id }})" wire:confirm="¿Quitar la imagen actual?">Quitar imagen</x-ui.button>
                    </div>
                @endif
            </div>
            <x-admin.input label="Descripción de la imagen" model="form.image_alt" hint="Obligatoria si subes imagen, p. ej. «Logotipo de Bethel Radio»." />
            <x-admin.input label="Color de fondo (opcional)" model="form.background_color" placeholder="#7F31D2" hint="Rellena la tarjeta alrededor del logotipo. Usa el color de fondo de la imagen." />
            <x-admin.input label="Orden" model="form.sort_order" type="number" min="0" />
            <x-admin.checkbox label="Publicado" model="form.is_published" />
        </x-admin.form-panel>
    @endif

    <ul class="divide-y divide-line rounded-card bg-white shadow-card">
        @forelse ($records as $record)
            <li wire:key="medio-{{ $record->id }}" class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex min-w-0 items-center gap-3">
                    <span class="flex h-12 w-16 shrink-0 items-center justify-center overflow-hidden rounded-control bg-sand" @if ($record->background_color) style="background-color: {{ $record->background_color }}" @endif>
                        @if ($record->imageUrl())
                            <img src="{{ $record->imageUrl() }}" alt="" class="h-full w-auto object-contain" width="48" height="48" loading="lazy">
                        @endif
                    </span>
                    <div class="min-w-0">
                        <p class="font-semibold">{{ $record->name }}</p>
                        <p class="truncate text-sm text-muted">{{ $record->url }}</p>
                    </div>
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    <x-admin.publish-toggle :record="$record" />
                    <x-admin.row-actions :id="$record->id" />
                </div>
            </li>
        @empty
            <li class="p-6 text-center text-muted">Aún no hay medios registrados.</li>
        @endforelse
    </ul>
</div>
