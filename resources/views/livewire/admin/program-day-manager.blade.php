<div>
    <x-admin.event-tabs :event="$this->event" />

    <x-admin.page-header title="Días del programa" description="Cada día agrupa las actividades del programa.">
        <x-slot:actions>
            <x-ui.button wire:click="create">Nuevo día</x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    @if ($showForm)
        <x-admin.form-panel :title="$editingId ? 'Editar día' : 'Nuevo día'">
            <x-admin.input label="Fecha" model="form.date" type="date" />
            <x-admin.input label="Título (opcional)" model="form.title" hint="Por ejemplo, el tema del día si está confirmado." />
        </x-admin.form-panel>
    @endif

    <ul class="divide-y divide-line rounded-card bg-white shadow-card">
        @forelse ($records as $record)
            <li wire:key="day-{{ $record->id }}" class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="font-semibold">{{ $record->label() }}</p>
                    <p class="mt-1 text-sm text-muted">{{ $record->title ?: 'Sin título' }} · {{ $record->items_count }} {{ $record->items_count === 1 ? 'actividad' : 'actividades' }}</p>
                </div>
                <x-admin.row-actions :id="$record->id" confirm="¿Eliminar este día? También se eliminarán sus actividades." />
            </li>
        @empty
            <li class="p-6 text-center text-muted">Aún no hay días registrados.</li>
        @endforelse
    </ul>
</div>
