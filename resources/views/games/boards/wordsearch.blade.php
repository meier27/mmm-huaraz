{{-- Sopa de letras (resources/js/games.js › wordSearch) --}}
<div x-data="wordSearch(@js($config))" class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_19rem] lg:items-start">
    <div class="min-w-0 space-y-4">
        @include('games.partials.bar', ['stats' => [['search', 'Encontradas', '`${found.length} / ${targets.length}`'], ['clock', 'Tiempo', 'clock()']]])

        <div x-show="! finished" class="rounded-card bg-white p-3 shadow-card ring-1 ring-black/5 sm:p-6">
            <div class="mx-auto grid max-w-lg gap-1 select-none [touch-action:none]" :style="`grid-template-columns: repeat(${size}, minmax(0, 1fr))`"
                 x-on:pointerdown="down($event)" x-on:pointermove="move($event)" x-on:pointerup="up()" x-on:pointercancel="dragging = false"
                 x-on:pointerleave="dragging && up()" aria-label="Sopa de letras" role="group">
                <template x-for="(letter, i) in letters" :key="i">
                    <button type="button" :data-cell="i" x-text="letter" :tabindex="i === cursor ? 0 : -1" :aria-label="`${letter}, fila ${Math.floor(i / size) + 1}, columna ${i % size + 1}`"
                            x-on:focus="cursor = i" x-on:keydown="nav($event, i)" x-on:keydown.enter.prevent="press(i)" x-on:keydown.space.prevent="press(i)"
                            class="flex aspect-square items-center justify-center rounded-md font-sans text-base font-bold transition-transform duration-150 sm:text-xl"
                            :class="cellClass(i)"></button>
                </template>
            </div>
            <p class="mt-4 text-center text-sm text-muted">Arrastra el dedo desde la primera hasta la última letra, o toca ambas. Con el teclado: flechas y Enter.</p>
            <p aria-live="polite" class="mt-1 min-h-6 text-center text-sm font-semibold text-brand-strong" x-text="message"></p>
        </div>

        @include('games.partials.result', ['summary' => '`Encontraste las ${targets.length} palabras`'])
    </div>

    <aside class="rounded-card bg-white p-5 shadow-card ring-1 ring-black/5">
        <h2 class="text-sm font-bold tracking-wide text-muted uppercase">Busca estas palabras</h2>
        <ul class="mt-3 grid grid-cols-2 gap-2 lg:grid-cols-1">
            <template x-for="(target, i) in targets" :key="i">
                <li class="rounded-control px-3 py-2 text-sm" :class="foundTarget(i) ? 'bg-green-50' : 'bg-cream-light'">
                    <span class="flex items-center gap-2 font-bold tracking-wide" :class="foundTarget(i) ? 'text-success line-through' : 'text-primary'">
                        <x-ui.icon name="check" class="size-4" x-show="foundTarget(i)" />
                        <span x-text="target"></span>
                    </span>
                    <a x-show="foundTarget(i)" :href="bibleLink(foundTarget(i)?.reference)" target="_blank" rel="noopener" class="mt-0.5 block text-xs font-semibold text-brand-strong hover:underline">
                        <span x-text="foundTarget(i)?.reference"></span><span class="sr-only"> (pestaña nueva)</span>
                    </a>
                </li>
            </template>
        </ul>
    </aside>
</div>
