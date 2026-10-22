{{--
    Buscador de la Biblia: libro («Juan»), pasaje («Juan 3:16») o palabra.
    Requiere $books y el script bible.partials.scripts. Opcional: $query.
    Sin JavaScript también funciona: el servidor abre el libro o el pasaje.
--}}
<form method="GET" action="{{ route('bible.search') }}" role="search" x-ref="form" class="relative"
      x-data="bibleSearch({ books: @js($books), indexUrl: @js(route('bible.index')), chapterUrl: @js(route('bible.chapter', ['__LIBRO__', 999])), initial: @js($query ?? '') })"
      x-on:click.outside="open = false" x-on:keydown.escape="open = false">
    <div class="flex gap-2">
        <label for="biblia-q" class="sr-only">Buscar un libro, un pasaje o una palabra</label>
        <div class="relative min-w-0 flex-1">
            <x-ui.icon name="search" class="pointer-events-none absolute top-1/2 left-4 size-5 -translate-y-1/2 text-muted" />
            <input id="biblia-q" type="search" name="q" maxlength="80" required autocomplete="off" spellcheck="false"
                   placeholder="Libro, cita o palabra: «Juan», «Juan 3:16», «esperanza»"
                   x-model="q" x-on:input="open = true; active = -1" x-on:focus="open = true"
                   x-on:keydown.arrow-down.prevent="move(1)" x-on:keydown.arrow-up.prevent="move(-1)" x-on:keydown.enter="enter($event)"
                   role="combobox" aria-autocomplete="list" aria-controls="biblia-sugerencias" :aria-expanded="(open && suggestions().length > 0).toString()"
                   :aria-activedescendant="active >= 0 ? 'sugerencia-' + active : null"
                   class="block min-h-12 w-full rounded-full border border-stone-300 bg-white py-2 ps-12 pe-4 text-ink shadow-sm focus:border-brand-strong">
        </div>
        <x-ui.button type="submit" class="shrink-0 rounded-full!"><span class="sr-only sm:not-sr-only">Buscar</span><x-ui.icon name="arrow-right" class="size-5 sm:hidden" /></x-ui.button>
    </div>

    {{-- Sugerencias --}}
    <ul id="biblia-sugerencias" role="listbox" x-show="open && suggestions().length" x-cloak x-transition.opacity
        class="absolute inset-x-0 top-full z-40 mt-2 overflow-hidden rounded-card bg-white py-2 text-ink shadow-2xl ring-1 ring-line">
        <template x-for="(item, i) in suggestions()" :key="item.type + item.label">
            <li :id="'sugerencia-' + i" role="option" :aria-selected="(i === active).toString()">
                <button type="button" x-on:click="choose(item)" x-on:mouseenter="active = i"
                        class="flex min-h-12 w-full items-center gap-3 px-4 text-left" :class="i === active ? 'bg-cream' : ''">
                    <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-full"
                          :class="item.type === 'word' ? 'bg-stone-100 text-muted' : 'bg-brand-strong/10 text-brand-strong'">
                        <template x-if="item.type === 'word'"><x-ui.icon name="search" class="size-4" /></template>
                        <template x-if="item.type !== 'word'"><x-ui.icon name="book" class="size-4" /></template>
                    </span>
                    <span class="min-w-0 flex-1">
                        <template x-if="item.type === 'passage'">
                            <span><span class="block text-xs font-semibold tracking-wide text-brand-strong uppercase">Ir al pasaje</span><span class="font-semibold" x-text="item.label"></span></span>
                        </template>
                        <template x-if="item.type === 'book'">
                            <span><span class="font-semibold" x-text="item.label"></span> <span class="text-sm text-muted" x-text="'· ' + item.chapters + (item.chapters === 1 ? ' capítulo' : ' capítulos')"></span></span>
                        </template>
                        <template x-if="item.type === 'word'">
                            <span class="text-muted">Buscar «<span class="font-semibold text-ink" x-text="item.label"></span>» en los versículos</span>
                        </template>
                    </span>
                    <x-ui.icon name="chevron-right" class="size-4 shrink-0 text-muted" />
                </button>
            </li>
        </template>
    </ul>
</form>
