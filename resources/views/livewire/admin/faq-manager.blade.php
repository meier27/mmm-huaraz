<div>
    <x-admin.page-header title="Preguntas frecuentes" description="Se muestran en la página de contacto.">
        <x-slot:actions>
            <x-ui.button wire:click="create">Nueva pregunta</x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    @if ($showForm)
        <x-admin.form-panel :title="$editingId ? 'Editar pregunta' : 'Nueva pregunta'">
            <x-admin.input label="Pregunta" model="form.question" class="sm:col-span-2" />
            <div class="sm:col-span-2">
                <x-admin.textarea label="Respuesta" model="form.answer" rows="5" />
            </div>
            <x-admin.input label="Orden" model="form.sort_order" type="number" min="0" hint="Menor número aparece primero." />
            <x-admin.checkbox label="Publicado" model="form.is_published" />
        </x-admin.form-panel>
    @endif

    <ul class="divide-y divide-line rounded-card bg-white shadow-card">
        @forelse ($records as $record)
            <li wire:key="faq-{{ $record->id }}" class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <p class="font-semibold">{{ $record->question }}</p>
                    <p class="mt-1 line-clamp-2 text-sm text-muted">{{ $record->answer }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <x-admin.publish-toggle :record="$record" />
                    <x-admin.row-actions :id="$record->id" />
                </div>
            </li>
        @empty
            <li class="p-6 text-center text-muted">Aún no hay preguntas registradas.</li>
        @endforelse
    </ul>
</div>
