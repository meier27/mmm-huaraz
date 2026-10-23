<div>
    <x-admin.page-header title="Premios de los juegos" description="Cada premio tiene una meta y una cantidad. Quien alcanza la meta lo reclama y recibe un código; lo entregas en Panel › Canjes.">
        <x-slot:actions>
            <x-ui.button wire:click="create">Nuevo premio</x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    @if (session('status'))
        <x-ui.alert type="info" class="mb-6">{{ session('status') }}</x-ui.alert>
    @endif

    @if ($showForm)
        <x-admin.form-panel :title="$editingId ? 'Editar premio' : 'Nuevo premio'">
            <x-admin.input label="Premio" model="form.title" class="sm:col-span-2" hint="Por ejemplo, «Biblia Reina-Valera 1960» o «Revista Impacto Evangelístico»." />
            <x-admin.textarea label="Descripción (opcional)" model="form.description" rows="2" class="sm:col-span-2" />
            <x-admin.select label="Meta" model="form.goal_type" :live="true"
                            :options="collect(\App\Enums\PrizeGoal::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all()" />
            @if ($form->goal_type === 'game_levels')
                <x-admin.select label="Juego" model="form.goal_game" placeholder="Elige un juego"
                                :options="collect(\App\Services\GameCatalog::GAMES)->map(fn ($g) => $g[1])->all()" />
            @endif
            @if ($form->goal_type === 'weekly')
                <p class="self-end rounded-control bg-cream px-3 py-2 text-sm text-primary">Se asigna en Panel › Ranking semanal, al cerrar cada semana.</p>
            @else
                <x-admin.input label="Cantidad para la meta" model="form.goal_value" type="number" min="1"
                               hint="{{ $form->goal_type === 'stars' ? 'Hay 3 estrellas por nivel: 90 por juego, 540 en total.' : 'Hay 30 niveles por juego (180 en total).' }}" />
            @endif
            <x-admin.input label="Unidades disponibles" model="form.stock" type="number" min="0" hint="Cuando se reclaman todas, el premio aparece como agotado." />
            <div>
                <x-admin.input label="Foto (opcional, JPG/PNG/WebP, máx. 4 MB)" model="form.image" type="file" accept="image/jpeg,image/png,image/webp" />
                <div wire:loading wire:target="form.image" class="mt-1 text-sm text-muted">Subiendo imagen…</div>
                @if ($editing?->imageUrl() && ! $form->image)
                    <div class="mt-2 flex items-center gap-3">
                        <img src="{{ $editing->imageUrl() }}" alt="" class="size-16 rounded-control object-cover" width="64" height="64">
                        <x-ui.button variant="ghost" class="text-sm text-danger!" wire:click="removeImage({{ $editing->id }})" wire:confirm="¿Quitar la imagen actual?">Quitar imagen</x-ui.button>
                    </div>
                @endif
            </div>
            <x-admin.input label="Descripción de la foto" model="form.image_alt" hint="Obligatoria si subes foto." />
            <x-admin.input label="Orden" model="form.sort_order" type="number" min="0" />
            <x-admin.checkbox label="Publicado (se puede reclamar)" model="form.is_published" />
        </x-admin.form-panel>
    @endif

    <ul class="divide-y divide-line rounded-card bg-white shadow-card">
        @forelse ($records as $record)
            <li wire:key="prize-{{ $record->id }}" class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex min-w-0 items-center gap-3">
                    @if ($record->imageUrl())
                        <img src="{{ $record->imageUrl() }}" alt="" class="size-12 shrink-0 rounded-control object-cover" width="48" height="48" loading="lazy">
                    @endif
                    <div class="min-w-0">
                        <p class="font-semibold">{{ $record->title }}</p>
                        <p class="text-sm text-muted">{{ $record->goalText() }}</p>
                        <p class="text-xs text-brand-strong">{{ $record->claims_count }} reclamados · {{ $record->remaining() }} de {{ $record->stock }} disponibles</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <x-admin.publish-toggle :record="$record" />
                    <x-admin.row-actions :id="$record->id" />
                </div>
            </li>
        @empty
            <li class="p-6 text-center text-muted">Aún no hay premios. Crea el primero (por ejemplo, una Biblia para quien complete 30 niveles).</li>
        @endforelse
    </ul>
</div>
