<div>
    <x-admin.page-header title="Programa" description="Actividades por día. Solo las publicadas se muestran en la web.">
        <x-slot:actions>
            <x-ui.button variant="secondary" href="{{ route('admin.program-days') }}" wire:navigate>Gestionar días</x-ui.button>
            <x-ui.button wire:click="create" :disabled="$days->isEmpty()">Nueva actividad</x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    @if ($showForm)
        <x-admin.form-panel :title="$editingId ? 'Editar actividad' : 'Nueva actividad'">
            <x-admin.select label="Día" model="form.program_day_id" placeholder="Selecciona un día" :options="$days->mapWithKeys(fn ($d) => [$d->id => $d->label()])" />
            <x-admin.select label="Lugar (opcional)" model="form.venue_id" placeholder="Sin lugar específico" :options="$venues->pluck('name', 'id')" />
            <x-admin.input label="Hora de inicio" model="form.starts_at" type="time" />
            <x-admin.input label="Hora de fin (opcional)" model="form.ends_at" type="time" />
            <x-admin.input label="Título" model="form.title" />
            <x-admin.input label="Predicador o responsable (opcional)" model="form.speaker" />
            <div class="sm:col-span-2">
                <x-admin.textarea label="Descripción (opcional)" model="form.description" rows="3" />
            </div>
            <x-admin.checkbox label="Publicado" model="form.is_published" />
        </x-admin.form-panel>
    @endif

    <div class="space-y-6">
        @forelse ($days as $day)
            <section wire:key="program-day-{{ $day->id }}" class="rounded-card bg-white shadow-card">
                <h2 class="border-b border-line px-4 py-3 text-lg font-bold">
                    {{ $day->label() }}
                    @if ($day->title)<span class="text-sm font-normal text-muted">· {{ $day->title }}</span>@endif
                </h2>
                <ul class="divide-y divide-line">
                    @forelse ($day->items as $item)
                        <li wire:key="program-item-{{ $item->id }}" class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                            <div class="min-w-0">
                                <p class="font-semibold"><span class="tabular-nums text-brand-strong">{{ $item->timeLabel() }}</span> · {{ $item->title }}</p>
                                <p class="mt-1 text-sm text-muted">{{ collect([$item->speaker, $item->venue?->name])->filter()->join(' · ') ?: 'Sin detalles adicionales' }}</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <x-admin.publish-toggle :record="$item" />
                                <x-admin.row-actions :id="$item->id" />
                            </div>
                        </li>
                    @empty
                        <li class="p-4 text-sm text-muted">Sin actividades registradas para este día.</li>
                    @endforelse
                </ul>
            </section>
        @empty
            <x-ui.alert>Primero registra los días del programa.</x-ui.alert>
        @endforelse
    </div>
</div>
