<div>
    <x-admin.page-header title="Juegos bíblicos" description="Preguntas para la trivia y palabras para el crucigrama y la sopa de letras. Cada una lleva su cita bíblica. «Completa el versículo» usa Panel › Versículos y el rompecabezas, las fotos de Panel › Fotos.">
        <x-slot:actions>
            <x-ui.button wire:click="create">{{ $type === \App\Enums\GameItemType::Question ? 'Nueva pregunta' : 'Nueva palabra' }}</x-ui.button>
            <x-ui.button href="{{ route('games.index') }}" variant="secondary" target="_blank" rel="noopener">Ver juegos<span class="sr-only"> (pestaña nueva)</span></x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    {{-- Pestañas por tipo --}}
    <div class="mb-6 flex flex-wrap gap-2" role="tablist" aria-label="Tipo de contenido">
        @foreach (\App\Enums\GameItemType::cases() as $case)
            <button type="button" role="tab" wire:click="$set('tab', '{{ $case->value }}')" aria-selected="{{ $type === $case ? 'true' : 'false' }}"
                    @class(['inline-flex min-h-11 items-center gap-2 rounded-full px-4 text-sm font-semibold transition-colors',
                            'bg-primary text-white' => $type === $case, 'bg-white text-primary ring-1 ring-line hover:bg-cream' => $type !== $case])>
                {{ $case === \App\Enums\GameItemType::Question ? 'Preguntas' : 'Palabras' }}
                <span @class(['rounded-full px-2 text-xs', 'bg-white/20' => $type === $case, 'bg-cream' => $type !== $case])>{{ $counts[$case->value] ?? 0 }} publicadas</span>
            </button>
        @endforeach
    </div>

    @if ($showForm)
        <x-admin.form-panel :title="$editingId ? 'Editar' : ($form->type === 'question' ? 'Nueva pregunta' : 'Nueva palabra')">
            <x-admin.select label="Tipo" model="form.type" :live="true" class="sm:col-span-2"
                            :options="collect(\App\Enums\GameItemType::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all()" />

            @if ($form->type === 'question')
                <x-admin.input label="Pregunta" model="form.prompt" class="sm:col-span-2" hint="Por ejemplo, «¿En qué ciudad nació Jesús?»." />
                <x-admin.input label="Respuesta correcta" model="form.answer" />
                <x-admin.input label="Cita bíblica" model="form.reference" hint="Donde se encuentra la respuesta, p. ej. «Mateo 2:1»." />
                <x-admin.input label="Respuesta incorrecta 1" model="form.wrong_1" />
                <x-admin.input label="Respuesta incorrecta 2" model="form.wrong_2" />
                <x-admin.input label="Respuesta incorrecta 3" model="form.wrong_3" />
            @else
                <x-admin.input label="Palabra" model="form.answer" hint="De 3 a 12 letras. Las tildes se quitan en la cuadrícula; la Ñ se mantiene." />
                <x-admin.input label="Cita bíblica" model="form.reference" hint="P. ej. «Génesis 6:14»." />
                <x-admin.input label="Pista (crucigrama)" model="form.prompt" class="sm:col-span-2" hint="Por ejemplo, «Construyó el arca»." />
            @endif

            <x-admin.input label="Orden" model="form.sort_order" type="number" min="0" hint="Menor número aparece primero en esta lista. En los juegos salen al azar." />
            <x-admin.checkbox label="Publicado" model="form.is_published" />
        </x-admin.form-panel>
    @endif

    <ul class="divide-y divide-line rounded-card bg-white shadow-card">
        @forelse ($records as $record)
            <li wire:key="game-{{ $record->id }}" class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    @if ($record->type === \App\Enums\GameItemType::Question)
                        <p class="font-semibold">{{ $record->prompt }}</p>
                        <p class="mt-1 text-sm text-muted">
                            <span class="font-semibold text-success">✓ {{ $record->answer }}</span>
                            · {{ implode(' · ', $record->options ?? []) }}
                        </p>
                    @else
                        <p class="font-semibold tracking-wide">{{ \App\Models\GameItem::gridWord($record->answer) }}</p>
                        <p class="mt-1 text-sm text-muted">{{ $record->prompt }}</p>
                    @endif
                    <p class="mt-1 text-xs text-brand-strong">{{ $record->reference }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <x-admin.publish-toggle :record="$record" />
                    <x-admin.row-actions :id="$record->id" />
                </div>
            </li>
        @empty
            <li class="p-6 text-center text-muted">Aún no hay {{ $type === \App\Enums\GameItemType::Question ? 'preguntas' : 'palabras' }}.</li>
        @endforelse
    </ul>
</div>
