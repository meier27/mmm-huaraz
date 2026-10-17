<div>
    <x-admin.page-header title="Eventos" description="Convenciones, campañas, vigilias y demás actividades. Cada evento puede activar módulos: programa, sedes, comidas con reserva, donaciones, hospedaje y turismo.">
        <x-slot:actions>
            <x-ui.button wire:click="create">Nuevo evento</x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    @error('delete')
        <x-ui.alert type="error" class="mb-6">{{ $message }}</x-ui.alert>
    @enderror

    @if ($showForm)
        <x-admin.form-panel title="Nuevo evento">
            <x-admin.input label="Título" model="form.title" class="sm:col-span-2" />
            <x-admin.input label="Fecha de inicio (opcional)" model="form.starts_on" type="date" hint="Si la dejas vacía, el evento se muestra sin fecha." />
            <x-admin.input label="Fecha de fin (opcional)" model="form.ends_on" type="date" hint="Solo si dura más de un día." />
            <p class="text-sm text-muted sm:col-span-2">Después podrás completar la descripción, la imagen y activar los módulos que necesite el evento.</p>
        </x-admin.form-panel>
    @endif

    <ul class="divide-y divide-line rounded-card bg-white shadow-card">
        @forelse ($records as $record)
            <li wire:key="event-{{ $record->id }}" class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <p class="font-semibold">
                        <a href="{{ route('admin.events.edit', $record) }}" wire:navigate class="hover:text-brand-strong hover:underline">{{ $record->title }}</a>
                        @if ($record->is_featured)
                            <span class="ms-1 inline-flex items-center gap-1 rounded-full bg-brand/20 px-2 py-0.5 text-xs font-semibold text-primary"><x-ui.icon name="star" class="size-3" /> Destacado</span>
                        @endif
                    </p>
                    <p class="text-sm text-muted">
                        {{ $record->dateLabel() ?: 'Sin fecha' }}@if ($record->isPast()) · finalizado @endif
                    </p>
                    @if ($record->hasAnyModule())
                        <ul class="mt-2 flex flex-wrap gap-1.5" aria-label="Módulos activos">
                            @foreach ($record->enabledModules() as $module)
                                <li class="inline-flex items-center gap-1 rounded-full bg-stone-100 px-2 py-0.5 text-xs text-muted">
                                    <x-ui.icon :name="\App\Models\ChurchEvent::MODULES[$module]['icon']" class="size-3" /> {{ \App\Models\ChurchEvent::MODULES[$module]['label'] }}
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
                <div class="flex items-center gap-2">
                    <x-admin.publish-toggle :record="$record" />
                    <x-ui.button href="{{ route('admin.events.edit', $record) }}" wire:navigate variant="secondary" class="min-h-9! px-3! py-1! text-sm">Gestionar</x-ui.button>
                    <x-ui.button variant="ghost" class="min-h-9! px-3! py-1! text-sm text-danger!" wire:click="delete({{ $record->id }})" wire:confirm="¿Eliminar este evento? Esta acción no se puede deshacer.">Eliminar</x-ui.button>
                </div>
            </li>
        @empty
            <li class="p-6 text-center text-muted">Aún no hay eventos registrados.</li>
        @endforelse
    </ul>

    <div class="mt-4">{{ $records->links() }}</div>
</div>
