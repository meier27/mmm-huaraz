<div>
    <x-admin.page-header title="Lemas" description="Lema anual del movimiento. La portada muestra el lema publicado del año más reciente. Usa solo lemas y textos oficiales.">
        <x-slot:actions>
            <x-ui.button wire:click="create">Nuevo lema</x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    @if ($showForm)
        <x-admin.form-panel :title="$editingId ? 'Editar lema' : 'Nuevo lema'">
            <x-admin.input label="Año" model="form.year" type="number" min="1960" max="2100" />
            <x-admin.input label="Lema" model="form.title" hint="Por ejemplo, «Familia»." />
            <x-admin.textarea label="Descripción (opcional)" model="form.description" rows="6" class="sm:col-span-2" hint="Deja una línea en blanco entre párrafos." />
            <x-admin.input label="Versículo (opcional)" model="form.verse_text" class="sm:col-span-2" hint="Por ejemplo, «Yo y mi casa serviremos a Jehová»." />
            <x-admin.input label="Cita del versículo" model="form.verse_reference" hint="Por ejemplo, «Josué 24:15»." />
            <div class="sm:col-span-2">
                <x-admin.input label="Imagen del lema (JPG, PNG o WebP, máx. 4 MB)" model="form.image" type="file" accept="image/jpeg,image/png,image/webp" />
                <div wire:loading wire:target="form.image" class="mt-1 text-sm text-muted">Subiendo imagen…</div>
                @if ($editing?->imageUrl() && ! $form->image)
                    <div class="mt-3 flex items-center gap-3">
                        <img src="{{ $editing->imageUrl() }}" alt="" class="size-16 rounded-control object-cover" width="64" height="64">
                        <x-ui.button variant="ghost" class="text-sm text-danger!" wire:click="removeImage({{ $editing->id }})" wire:confirm="¿Quitar la imagen actual?">Quitar imagen</x-ui.button>
                    </div>
                @endif
            </div>
            <x-admin.input label="Descripción de la imagen" model="form.image_alt" class="sm:col-span-2" hint="Obligatoria si subes imagen. Para lectores de pantalla." />
            <x-admin.checkbox label="Publicado" model="form.is_published" />
        </x-admin.form-panel>
    @endif

    <ul class="divide-y divide-line rounded-card bg-white shadow-card">
        @forelse ($records as $record)
            <li wire:key="motto-{{ $record->id }}" class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex min-w-0 items-center gap-3">
                    @if ($record->imageUrl())
                        <img src="{{ $record->imageUrl() }}" alt="" class="size-12 shrink-0 rounded-control object-cover" width="48" height="48" loading="lazy">
                    @else
                        <span class="flex size-12 shrink-0 items-center justify-center rounded-control bg-cream text-sm font-bold text-brand-strong">{{ $record->year }}</span>
                    @endif
                    <div class="min-w-0">
                        <p class="font-semibold">{{ $record->year }} · {{ $record->title }}
                            @if ($record->id === $currentId)
                                <span class="ms-1 rounded-full bg-brand/15 px-2 py-0.5 text-xs font-semibold text-brand-strong">En la portada</span>
                            @endif
                        </p>
                        <p class="truncate text-sm text-muted">{{ $record->verse_text ? '«'.$record->verse_text.'» '.$record->verse_reference : 'Sin versículo' }}</p>
                    </div>
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    <x-admin.publish-toggle :record="$record" />
                    <x-admin.row-actions :id="$record->id" />
                </div>
            </li>
        @empty
            <li class="p-6 text-center text-muted">Aún no hay lemas registrados.</li>
        @endforelse
    </ul>
</div>
