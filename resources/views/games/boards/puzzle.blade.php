{{-- Rompecabezas (resources/js/games.js › puzzleGame) --}}
<div x-data="puzzleGame(@js($config))" class="mx-auto max-w-3xl space-y-4">
    @include('games.partials.bar', ['stats' => [['puzzle', 'Piezas en su lugar', '`${placedCount()} / ${tiles.length}`'], ['refresh', 'Movimientos', 'swaps.length'], ['clock', 'Tiempo', 'clock()']]])

    <div class="rounded-card bg-white p-3 shadow-card ring-1 ring-black/5 sm:p-6">
        <div class="relative mx-auto overflow-hidden rounded-md bg-primary-deep" :style="boardStyle()">
            <div x-show="! peek && ! finished && ! loading" class="grid size-full gap-px select-none [touch-action:none]"
                 :style="`grid-template-columns: repeat(${n}, minmax(0, 1fr))`" x-on:pointerup="up($event)" x-on:pointercancel="dragFrom = null" x-on:dragstart.prevent>
                <template x-for="(piece, pos) in tiles" :key="pos">
                    <button type="button" :data-pos="pos" :style="tileStyle(piece)"
                            x-on:pointerdown="down($event, pos)" x-on:click="$event.detail === 0 && tap(pos)"
                            :aria-label="`Pieza en la posición ${pos + 1}` + (piece === pos ? ', en su lugar' : '') + (selected === pos ? ', elegida' : '')"
                            class="relative bg-no-repeat transition duration-200 focus-visible:z-10 focus-visible:outline-4 focus-visible:outline-accent-light"
                            :class="selected === pos ? 'z-10 scale-[0.92] rounded-sm ring-4 ring-accent-light' : 'hover:brightness-110'"></button>
                </template>
            </div>
            <img x-show="peek || finished" x-cloak :src="image.url" :alt="image.alt" class="size-full object-fill" :class="finished && 'game-pop'" draggable="false">
            <div x-show="loading" class="absolute inset-0 flex items-center justify-center text-sm text-white/80">Cargando…</div>
        </div>

        <div x-show="! finished" class="mt-4 flex flex-wrap items-center justify-center gap-2">
            <button type="button" x-on:click="peek = ! peek" :aria-pressed="peek.toString()"
                    class="inline-flex min-h-11 items-center gap-2 rounded-full border border-primary px-4 text-sm font-semibold text-primary hover:bg-cream">
                <x-ui.icon name="eye" class="size-4" /> <span x-text="peek ? 'Volver a las piezas' : 'Ver la imagen'"></span>
            </button>
        </div>
        <p class="mt-3 text-center text-sm text-muted" x-show="! finished">Arrastra una pieza sobre otra para cambiarlas, o toca las dos.</p>
        <p aria-live="polite" class="mt-1 min-h-6 text-center text-sm font-semibold text-brand-strong" x-show="! finished" x-text="message"></p>
    </div>

    {{-- El versículo que se descubre al terminar --}}
    <template x-if="finished && verse">
        <figure class="game-pop rounded-card bg-cream p-6 text-center ring-1 ring-line">
            <blockquote class="font-display text-xl leading-relaxed font-semibold text-primary sm:text-2xl" x-text="`«${verse.text}»`"></blockquote>
            <figcaption class="mt-3">
                <a :href="bibleLink(verse.reference)" target="_blank" rel="noopener" class="font-semibold text-brand-strong hover:underline" x-text="verse.reference"></a>
            </figcaption>
        </figure>
    </template>

    @include('games.partials.result', ['summary' => '`${tiles.length} piezas en ${swaps.length} movimientos`'])
</div>
