{{-- Crucigrama bíblico (resources/js/games.js › crosswordGame) --}}
<div x-data="crosswordGame(@js($config))" class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_22rem] lg:items-start">
    <div class="min-w-0 space-y-4">
        @include('games.partials.bar', ['stats' => [['clock', 'Tiempo', 'clock()'], ['bulb', 'Letras reveladas', 'hints']]])

        {{-- Pista de la palabra activa: fija arriba mientras se escribe (útil en el celular) --}}
        <div x-show="! finished && current()" class="sticky top-22 z-20 flex items-start gap-3 rounded-control bg-primary-deep px-4 py-3 text-white shadow-lg">
            <span class="shrink-0 rounded-full bg-accent-light px-2.5 py-0.5 text-sm font-bold text-primary-deep"
                  x-text="current() && (current().number + (current().dir === 'across' ? ' →' : ' ↓'))"></span>
            <span class="min-w-0 flex-1 font-semibold" x-text="current()?.clue"></span>
            <a :href="bibleLink(current()?.reference)" target="_blank" rel="noopener" class="shrink-0 text-sm text-accent-light underline-offset-4 hover:underline" x-text="current()?.reference"></a>
        </div>

        <div x-show="! finished" class="rounded-card bg-white p-3 shadow-card ring-1 ring-black/5 sm:p-6">
            <div class="mx-auto grid gap-0.5" :style="`grid-template-columns: repeat(${cols}, minmax(0, 1fr)); max-width: ${cols * 3}rem`">
                <template x-for="(cell, i) in cells" :key="i">
                    <div class="relative aspect-square">
                        <template x-if="cell">
                            <div class="absolute inset-0 overflow-hidden rounded-[3px] ring-1 ring-primary/50"
                                 :class="{ 'bg-accent-light': active === i, 'bg-accent-light/35': active !== i && inCurrent(i), 'bg-white': active !== i && ! inCurrent(i) }">
                                <span class="pointer-events-none absolute top-0 left-0.5 text-[0.5rem] leading-none font-bold text-muted sm:top-0.5 sm:left-1 sm:text-[0.65rem]" x-text="cell.number"></span>
                                <input type="text" :data-cell="i" maxlength="2" autocomplete="off" autocapitalize="characters" spellcheck="false" enterkeyhint="next"
                                       :value="cell.entry" :aria-label="`Casilla ${Math.floor(i / cols) + 1}-${i % cols + 1}` + (cell.number ? `, palabra ${cell.number}` : '')"
                                       x-on:pointerdown="if (active === i) tap(i)" x-on:focus="if (active !== i) tap(i)"
                                       x-on:input="input(i, $event)" x-on:keydown="keydown(i, $event)"
                                       class="size-full border-0 bg-transparent p-0 text-center font-sans text-base font-bold uppercase caret-transparent outline-none sm:text-xl"
                                       :class="{ 'text-danger': cell.state === 'wrong', 'text-brand-strong': cell.state === 'hint', 'text-primary': ! cell.state }">
                            </div>
                        </template>
                    </div>
                </template>
            </div>

            <div class="mt-5 flex flex-wrap justify-center gap-2">
                <button type="button" x-on:click="check()" :disabled="busy" class="inline-flex min-h-11 items-center gap-2 rounded-full border border-primary px-4 text-sm font-semibold text-primary hover:bg-cream disabled:opacity-50">
                    <x-ui.icon name="check" class="size-4" /> Comprobar
                </button>
                <button type="button" x-on:click="hint()" :disabled="busy" class="inline-flex min-h-11 items-center gap-2 rounded-full border border-primary px-4 text-sm font-semibold text-primary hover:bg-cream disabled:opacity-50">
                    <x-ui.icon name="bulb" class="size-4" /> Revelar una letra
                </button>
            </div>
            <p class="mt-3 text-center text-xs text-muted">Revelar letras o comprobar muchas veces resta estrellas.</p>
            <p aria-live="polite" class="mt-1 min-h-6 text-center text-sm font-semibold text-brand-strong" x-text="message"></p>
        </div>

        @include('games.partials.result', ['summary' => '`${entries.length} palabras` + (hints ? ` con ${hints} ${hints === 1 ? \'letra revelada\' : \'letras reveladas\'}` : \' sin ayuda\')'])
    </div>

    {{-- Pistas --}}
    <aside x-show="! finished" class="grid gap-6 rounded-card bg-white p-5 shadow-card ring-1 ring-black/5 sm:grid-cols-2 lg:grid-cols-1">
        @foreach (['across' => 'Horizontales', 'down' => 'Verticales'] as $dir => $label)
            <section>
                <h2 class="flex items-center gap-2 text-sm font-bold tracking-wide text-muted uppercase">{{ $label }} <span aria-hidden="true">{{ $dir === 'across' ? '→' : '↓' }}</span></h2>
                <ol class="mt-2 space-y-1">
                    <template x-for="entry in {{ $dir }}()" :key="entry.id">
                        <li>
                            <button type="button" x-on:click="goToEntry(entry)" class="flex w-full gap-2 rounded-control px-3 py-2 text-left text-sm transition"
                                    :class="current()?.id === entry.id ? 'bg-accent-light/40' : 'hover:bg-cream'">
                                <span class="w-5 shrink-0 font-bold text-brand-strong" x-text="entry.number"></span>
                                <span :class="isFilled(entry) ? 'text-muted' : 'text-ink'" x-text="entry.clue"></span>
                            </button>
                        </li>
                    </template>
                </ol>
            </section>
        @endforeach
    </aside>
</div>
