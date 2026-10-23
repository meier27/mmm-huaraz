{{-- Trivia bíblica (resources/js/games.js › triviaGame) --}}
<div x-data="triviaGame(@js($config))" x-on:keydown.window="keys($event)" class="mx-auto max-w-3xl space-y-4">
    @include('games.partials.bar', ['stats' => [['help', 'Pregunta', '`${index + 1} / ${questions.length}`'], ['check', 'Aciertos', 'score'], ['clock', 'Tiempo', 'clock()']]])

    <div x-show="! finished" class="rounded-card bg-white p-5 shadow-card ring-1 ring-black/5 sm:p-8">
        <div class="h-1.5 overflow-hidden rounded-full bg-cream" aria-hidden="true">
            <div class="h-full rounded-full bg-brand-strong transition-[width] duration-500" :style="`width: ${(index + (answer !== null ? 1 : 0)) / questions.length * 100}%`"></div>
        </div>
        <h2 x-ref="question" tabindex="-1" class="mt-6 text-xl leading-snug font-bold text-primary outline-none sm:text-2xl" x-text="current.prompt"></h2>

        <div class="mt-6 grid gap-3 sm:grid-cols-2">
            <template x-for="(choice, i) in current.choices" :key="index + '-' + i">
                <button type="button" x-on:click="choose(choice)" :disabled="picked !== null"
                        class="flex min-h-14 items-center gap-3 rounded-control border-2 px-4 py-3 text-left font-semibold transition disabled:cursor-default"
                        :class="{
                            'border-line bg-white hover:border-brand-strong hover:bg-cream-light': state(choice) === 'idle',
                            'animate-pulse border-brand-strong bg-cream': state(choice) === 'pending',
                            'border-success bg-green-50 text-success': state(choice) === 'right',
                            'game-shake border-danger bg-red-50 text-danger': state(choice) === 'wrong',
                            'border-line bg-white opacity-50': state(choice) === 'dim',
                        }">
                    <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-cream text-sm text-primary" aria-hidden="true">
                        <span x-show="state(choice) !== 'right' && state(choice) !== 'wrong'" x-text="i + 1"></span>
                        <span x-show="state(choice) === 'right'"><x-ui.icon name="check" class="size-4 text-success" /></span>
                        <span x-show="state(choice) === 'wrong'"><x-ui.icon name="close" class="size-4 text-danger" /></span>
                    </span>
                    <span x-text="choice"></span>
                </button>
            </template>
        </div>

        <div x-show="answer !== null" class="game-pop mt-6 flex flex-col gap-4 rounded-control bg-cream p-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="font-semibold" :class="picked === answer ? 'text-success' : 'text-danger'" x-text="message"></p>
                <a :href="bibleLink(reference)" target="_blank" rel="noopener" class="mt-1 inline-flex items-center gap-1.5 text-sm font-semibold text-brand-strong underline-offset-4 hover:underline">
                    <x-ui.icon name="book" class="size-4" /> Leer <span x-text="reference"></span><span class="sr-only"> (pestaña nueva)</span>
                </a>
            </div>
            <button type="button" x-ref="next" x-on:click="next()" class="inline-flex min-h-11 shrink-0 items-center justify-center gap-2 rounded-full bg-brand-strong px-5 font-semibold text-white hover:bg-primary">
                <span x-text="index + 1 < questions.length ? 'Siguiente' : 'Ver resultado'"></span> <x-ui.icon name="arrow-right" class="size-4" />
            </button>
        </div>
    </div>
    <p class="sr-only" aria-live="polite" x-text="message"></p>

    @include('games.partials.result', ['summary' => '`${score} de ${questions.length} respuestas correctas` + (outcome.stars ? \'\' : \' (necesitas el 60 %)\')'])
    <p x-show="! finished" class="text-center text-sm text-muted">Consejo: en la computadora puedes responder con las teclas 1 a 4.</p>
</div>
