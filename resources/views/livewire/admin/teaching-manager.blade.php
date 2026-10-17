<div>
    <x-admin.page-header title="Enseñanzas" description="Predicaciones, estudios y devocionales. Solo se muestran las publicadas cuya fecha ya llegó.">
        <x-slot:actions>
            <x-ui.button wire:click="create">Nueva enseñanza</x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    @if ($showForm)
        <x-admin.form-panel :title="$editingId ? 'Editar enseñanza' : 'Nueva enseñanza'">
            <x-admin.input label="Título" model="form.title" class="sm:col-span-2" />
            <x-admin.select label="Categoría" model="form.category" :options="$categories" placeholder="Elige una categoría" />
            <x-admin.input label="Fecha de publicación" model="form.published_on" type="date" />
            <x-admin.input label="Predicador o autor (opcional)" model="form.author_name" />
            <x-admin.input label="Vídeo de YouTube (opcional)" model="form.video_url" type="url" hint="Pega el enlace del vídeo." />
            <x-admin.textarea label="Resumen (opcional)" model="form.excerpt" rows="2" class="sm:col-span-2" hint="Si se deja vacío, se usa el inicio del contenido." />
            <x-admin.textarea label="Contenido" model="form.body" rows="12" class="sm:col-span-2" hint="Admite Markdown: ## subtítulos, **negrita**, listas con «-» y > citas." />
            <div class="sm:col-span-2">
                <x-admin.input label="Imagen (opcional, JPG, PNG o WebP, máx. 2 MB)" model="form.image" type="file" accept="image/jpeg,image/png,image/webp" />
                <div wire:loading wire:target="form.image" class="mt-1 text-sm text-muted">Subiendo imagen…</div>
                @if ($editing?->imageUrl() && ! $form->image)
                    <div class="mt-3 flex items-center gap-3">
                        <img src="{{ $editing->imageUrl() }}" alt="" class="h-16 w-28 rounded-control object-cover" width="112" height="64">
                        <x-ui.button variant="ghost" class="text-sm text-danger!" wire:click="removeImage({{ $editing->id }})" wire:confirm="¿Quitar la imagen actual?">Quitar imagen</x-ui.button>
                    </div>
                @endif
            </div>
            <x-admin.input label="Texto alternativo de la imagen" model="form.image_alt" class="sm:col-span-2" />
            <x-admin.checkbox label="Publicado" model="form.is_published" />
            @if ($editing)
                <p class="self-center text-xs text-muted">Dirección: /ensenanzas/{{ $editing->slug }}</p>
            @endif
        </x-admin.form-panel>
    @endif

    <ul class="divide-y divide-line rounded-card bg-white shadow-card">
        @forelse ($records as $record)
            <li wire:key="teaching-{{ $record->id }}" class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <p class="font-semibold">{{ $record->title }}</p>
                    <p class="text-sm text-muted">
                        {{ $record->category->label() }} · {{ $record->published_on->translatedFormat('j M Y') }}
                        @if ($record->published_on->isFuture()) · <span class="text-brand-strong">programada</span>@endif
                        @if ($record->video_id) · con vídeo @endif
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <x-admin.publish-toggle :record="$record" />
                    <x-admin.row-actions :id="$record->id" />
                </div>
            </li>
        @empty
            <li class="p-6 text-center text-muted">Aún no hay enseñanzas registradas.</li>
        @endforelse
    </ul>

    <div class="mt-4">{{ $records->links() }}</div>
</div>
