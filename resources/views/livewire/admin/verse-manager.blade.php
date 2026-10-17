<div>
    <x-admin.page-header title="Versículos destacados" description="La portada y la sección Biblia muestran el primer versículo publicado según el orden. Cambia el orden para rotarlos.">
        <x-slot:actions>
            <x-ui.button wire:click="create">Nuevo versículo</x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    @if ($showForm)
        <x-admin.form-panel :title="$editingId ? 'Editar versículo' : 'Nuevo versículo'">
            <x-admin.input label="Cita" model="form.reference" hint="Por ejemplo, «Juan 3:16»." />
            <x-admin.input label="Versión (opcional)" model="form.version" hint="Por ejemplo, «RVR1960»." />
            <x-admin.textarea label="Texto" model="form.text" rows="4" class="sm:col-span-2" hint="Cópialo literalmente de la versión indicada." />
            <x-admin.input label="Orden" model="form.sort_order" type="number" min="0" hint="Menor número aparece primero." />
            <x-admin.checkbox label="Publicado" model="form.is_published" />
        </x-admin.form-panel>
    @endif

    <ul class="divide-y divide-line rounded-card bg-white shadow-card">
        @forelse ($records as $record)
            <li wire:key="verse-{{ $record->id }}" class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <p class="font-semibold">{{ $record->reference }} @if ($record->version)<span class="font-normal text-muted">({{ $record->version }})</span>@endif</p>
                    <p class="mt-1 line-clamp-2 text-sm text-muted">{{ $record->text }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <x-admin.publish-toggle :record="$record" />
                    <x-admin.row-actions :id="$record->id" />
                </div>
            </li>
        @empty
            <li class="p-6 text-center text-muted">Aún no hay versículos registrados.</li>
        @endforelse
    </ul>
</div>
