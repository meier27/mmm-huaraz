{{-- Ordena los libros de la Biblia (resources/js/games.js › booksGame) --}}
<div x-data="booksGame(@js($config))" class="mx-auto max-w-4xl space-y-4">
    @include('games.partials.bar', ['stats' => [['check', 'Ordenados', '`${placed.length} / ${pool.length}`'], ['close', 'Errores', 'mistakes'], ['clock', 'Tiempo', 'clock()']]])

    <div x-show="! finished" class="rounded-card bg-white p-5 shadow-card ring-1 ring-black/5 sm:p-8">
        <h2 class="text-sm font-bold tracking-wide text-muted uppercase">Tu orden</h2>
        <ol class="mt-3 flex flex-wrap gap-2">
            <template x-for="i in pool.length" :key="i">
                <li class="flex min-h-11 items-center gap-2 rounded-full px-3 text-sm font-semibold transition"
                    :class="placed[i - 1] ? 'game-pop bg-green-50 text-success ring-1 ring-success/40' : 'border-2 border-dashed border-sand-dark text-muted'">
                    <span class="flex size-6 items-center justify-center rounded-full text-xs" :class="placed[i - 1] ? 'bg-success text-white' : 'bg-cream'" x-text="i"></span>
                    <span x-text="placed[i - 1] ?? '…'"></span>
                </li>
            </template>
        </ol>

        <div class="mt-6 border-t border-line pt-6">
            <h2 class="text-sm font-bold tracking-wide text-muted uppercase">Toca el libro que sigue</h2>
            <div class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-3">
                <template x-for="name in pool" :key="name">
                    <button type="button" x-on:click="choose(name)" :disabled="placed.includes(name)"
                            class="min-h-12 rounded-control border-2 px-3 font-display text-lg font-bold transition disabled:invisible"
                            :class="shake === name ? 'game-shake border-danger bg-red-50 text-danger' : 'border-line bg-cream-light text-primary hover:-translate-y-0.5 hover:border-brand-strong'"
                            x-text="name"></button>
                </template>
            </div>
        </div>
        <p aria-live="polite" class="mt-4 min-h-6 text-center text-sm font-semibold" :class="message.endsWith('correcto.') ? 'text-success' : 'text-danger'" x-text="message"></p>
    </div>

    @include('games.partials.result', ['summary' => '`${pool.length} libros en orden con ${mistakes} ${mistakes === 1 ? \'error\' : \'errores\'}`'])
</div>
