<div>
    <x-admin.page-header title="Líderes" description="Pastores y líderes que aparecen en «Nosotros». Publica fotos solo con autorización de la persona.">
        <x-slot:actions>
            <x-ui.button wire:click="create">Nuevo líder</x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    @if ($showForm)
        <x-admin.form-panel :title="$editingId ? 'Editar líder' : 'Nuevo líder'">
            <x-admin.input label="Nombre" model="form.name" />
            <x-admin.input label="Cargo" model="form.role" hint="Por ejemplo, «Pastor de la iglesia»." />
            <x-admin.textarea label="Reseña (opcional)" model="form.bio" rows="4" class="sm:col-span-2" />
            <div class="sm:col-span-2">
                <x-admin.input label="Foto (opcional, JPG, PNG o WebP, máx. 2 MB)" model="form.image" type="file" accept="image/jpeg,image/png,image/webp" />
                <div wire:loading wire:target="form.image" class="mt-1 text-sm text-muted">Subiendo foto…</div>
                @if ($editing?->imageUrl() && ! $form->image)
                    <div class="mt-3 flex items-center gap-3">
                        <img src="{{ $editing->imageUrl() }}" alt="" class="size-16 rounded-full object-cover" width="64" height="64">
                        <x-ui.button variant="ghost" class="text-sm text-danger!" wire:click="removeImage({{ $editing->id }})" wire:confirm="¿Quitar la foto actual?">Quitar foto</x-ui.button>
                    </div>
                @endif
            </div>
            <x-admin.input label="Orden" model="form.sort_order" type="number" min="0" hint="Menor número aparece primero." />
            <x-admin.checkbox label="Publicado" model="form.is_published" />
        </x-admin.form-panel>
    @endif

    <ul class="divide-y divide-line rounded-card bg-white shadow-card">
        @forelse ($records as $record)
            <li wire:key="leader-{{ $record->id }}" class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex min-w-0 items-center gap-3">
                    @if ($record->imageUrl())
                        <img src="{{ $record->imageUrl() }}" alt="" class="size-12 shrink-0 rounded-full object-cover" width="48" height="48" loading="lazy">
                    @else
                        <span class="flex size-12 shrink-0 items-center justify-center rounded-full bg-cream font-display font-bold text-brand-strong" aria-hidden="true">{{ mb_substr($record->name, 0, 1) }}</span>
                    @endif
                    <div class="min-w-0">
                        <p class="font-semibold">{{ $record->name }}</p>
                        <p class="truncate text-sm text-muted">{{ $record->role }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <x-admin.publish-toggle :record="$record" />
                    <x-admin.row-actions :id="$record->id" />
                </div>
            </li>
        @empty
            <li class="p-6 text-center text-muted">Aún no hay líderes registrados.</li>
        @endforelse
    </ul>
</div>
