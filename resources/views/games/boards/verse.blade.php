{{-- Completa el versículo (resources/js/games.js › verseGame) --}}
<div x-data="verseGame(@js($config))" class="mx-auto max-w-3xl space-y-4">
    @include('games.partials.bar', ['stats' => [['quote', 'Versículo', '`${index + 1} / ${verses.length}`'], ['close', 'Errores', 'mistakes'], ['clock', 'Tiempo', 'clock()']]])

    <div x-show="! finished" class="rounded-card bg-white p-5 shadow-card ring-1 ring-black/5 sm:p-8">
        <p class="text-sm text-muted">Toca una palabra para ponerla en el espacio resaltado. Toca un espacio para elegirlo o para quitar su palabra.</p>

        <blockquote class="mt-5 font-display text-xl leading-[2.4] text-primary sm:text-2xl sm:leading-[2.3]">
            <template x-for="(token, i) in tokens" :key="index + '-' + i">
                <span>
                    <template x-if="token.blank === undefined"><span x-text="token.text ?? (token.pre + token.word + token.post)"></span></template>
                    <template x-if="token.blank !== undefined">
                        <span class="whitespace-nowrap"><span x-text="token.pre"></span><button type="button" x-on:click="select(token)"
                                class="mx-0.5 inline-flex min-h-10 min-w-20 items-center justify-center rounded-md border-b-4 px-2 align-middle font-sans text-lg leading-none font-bold transition sm:text-xl"
                                :class="{
                                    'game-shake border-danger bg-red-50 text-danger': token.state === 'wrong',
                                    'border-success bg-green-50 text-success': token.state === 'right',
                                    'border-primary bg-cream text-primary': token.filled && ! token.state,
                                    'border-brand-strong bg-accent-light/40': ! token.filled && token.blank === active,
                                    'border-sand-dark bg-cream-light': ! token.filled && token.blank !== active,
                                }"
                                :aria-label="token.filled ? `Espacio con «${token.filled.word}». Toca para quitarla.` : (token.blank === active ? 'Espacio vacío seleccionado' : 'Espacio vacío')">
                            <span x-text="token.filled?.word ?? ''"></span>
                        </button><span x-text="token.post"></span></span>
                    </template>
                </span>
            </template>
        </blockquote>

        <p x-show="solved" class="game-pop mt-3 text-right">
            <a :href="bibleLink(reference)" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 font-semibold text-brand-strong underline-offset-4 hover:underline">
                <x-ui.icon name="book" class="size-4" /> <span x-text="reference"></span><span class="sr-only"> (pestaña nueva)</span>
            </a>
        </p>

        <div x-show="! solved" class="mt-6 flex flex-wrap justify-center gap-2 border-t border-line pt-6">
            <template x-for="chip in chips" :key="index + '-' + chip.id">
                <button type="button" x-on:click="place(chip)" :disabled="chip.used" x-text="chip.word"
                        class="min-h-11 rounded-full bg-primary px-5 font-semibold text-white shadow-md transition hover:-translate-y-0.5 hover:bg-brand-strong disabled:invisible"></button>
            </template>
        </div>

        <p aria-live="polite" class="mt-4 min-h-6 text-center font-semibold" :class="solved ? 'text-success' : 'text-danger'" x-text="message"></p>

        <div x-show="solved" class="mt-2 text-center">
            <button type="button" x-ref="next" x-on:click="next()" class="inline-flex min-h-11 items-center gap-2 rounded-full bg-brand-strong px-6 font-semibold text-white hover:bg-primary">
                <span x-text="index + 1 < verses.length ? 'Siguiente versículo' : 'Ver resultado'"></span> <x-ui.icon name="arrow-right" class="size-4" />
            </button>
        </div>
    </div>

    @include('games.partials.result', ['summary' => '`${verses.length} ${verses.length === 1 ? \'versículo\' : \'versículos\'} con ${mistakes} ${mistakes === 1 ? \'error\' : \'errores\'}`'])
</div>
