<div>
    <x-admin.page-header title="Página «Nosotros»" description="Bloques de foto y texto que se alternan (foto a un lado, texto al otro) y citas destacadas. Se muestran en el orden indicado.">
        <x-slot:actions>
            <x-ui.button wire:click="create">Nuevo bloque</x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    @if ($showForm)
        <x-admin.form-panel :title="$editingId ? 'Editar bloque' : 'Nuevo bloque'">
            <x-admin.select label="Estilo" model="form.type" :options="$types" class="sm:col-span-2" />
            <x-admin.textarea label="Texto" model="form.body" rows="7" class="sm:col-span-2"
                              hint="Deja una línea en blanco entre párrafos. Para resaltar palabras escríbelas entre dobles asteriscos: **el Espíritu Santo**." />
            <x-admin.input label="Autor (solo para citas)" model="form.author_name" hint="Por ejemplo, «Rev. Luis M. Ortiz»." />
            <x-admin.input label="Cargo del autor (opcional)" model="form.author_role" hint="Por ejemplo, «Fundador del M.M.M»." />
            <div class="sm:col-span-2">
                <x-admin.input label="Foto (opcional, JPG, PNG o WebP, máx. 4 MB)" model="form.image" type="file" accept="image/jpeg,image/png,image/webp" />
                <div wire:loading wire:target="form.image" class="mt-1 text-sm text-muted">Subiendo foto…</div>
                @if ($editing?->imageUrl() && ! $form->image)
                    <div class="mt-3 flex items-center gap-3">
                        <img src="{{ $editing->imageUrl() }}" alt="" class="h-16 w-20 rounded-control object-cover" width="80" height="64">
                        <x-ui.button variant="ghost" class="text-sm text-danger!" wire:click="removeImage({{ $editing->id }})" wire:confirm="¿Quitar la foto actual?">Quitar foto</x-ui.button>
                    </div>
                @endif
            </div>
            <x-admin.input label="Descripción de la foto" model="form.image_alt" class="sm:col-span-2" hint="Obligatoria si subes foto. Para lectores de pantalla." />
            <div class="sm:col-span-2">
                <x-admin.checkbox label="Añadir marco tipo polaroid a la foto" model="form.photo_frame" />
                <p class="mt-1 text-xs text-muted">Desmárcalo si la foto ya trae su propio marco o es un recorte PNG con fondo transparente (se mostrará tal cual).</p>
            </div>
            <x-admin.input label="Orden" model="form.sort_order" type="number" min="0" hint="Menor número aparece primero." />
            <x-admin.checkbox label="Publicado" model="form.is_published" />
        </x-admin.form-panel>
    @endif

    <ul class="divide-y divide-line rounded-card bg-white shadow-card">
        @forelse ($records as $record)
            <li wire:key="about-{{ $record->id }}" class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex min-w-0 items-center gap-3">
                    @if ($record->imageUrl())
                        <img src="{{ $record->imageUrl() }}" alt="" class="h-12 w-16 shrink-0 rounded-control object-cover" width="64" height="48" loading="lazy">
                    @else
                        <span @class(['flex h-12 w-16 shrink-0 items-center justify-center rounded-control text-xs font-semibold',
                                      'bg-sand text-primary-soft' => $record->type === \App\Enums\AboutBlockType::Text,
                                      'bg-brand-strong text-white' => $record->type === \App\Enums\AboutBlockType::Highlight,
                                      'bg-primary text-cream' => $record->type === \App\Enums\AboutBlockType::Quote])>Sin foto</span>
                    @endif
                    <div class="min-w-0">
                        <p class="text-xs font-semibold tracking-wide text-primary-soft uppercase">{{ $record->type->label() }} · Orden {{ $record->sort_order }}</p>
                        <p class="line-clamp-2 text-sm">{{ \App\Support\Markdown::plain($record->body, 180) }}</p>
                    </div>
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    <x-admin.publish-toggle :record="$record" />
                    <x-admin.row-actions :id="$record->id" />
                </div>
            </li>
        @empty
            <li class="p-6 text-center text-muted">Aún no hay bloques. Crea el primero con «Nuevo bloque».</li>
        @endforelse
    </ul>
</div>
