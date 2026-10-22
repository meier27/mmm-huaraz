{{--
    Pasos para elegir un pasaje: libro → capítulo → versículo.
    Va dentro de un componente que use passagePicker() (bible.partials.scripts).
--}}
<div>
    {{-- En qué paso está (se puede volver atrás tocándolo) --}}
    <ol class="flex flex-wrap items-center gap-1 text-sm" aria-label="Pasos">
        <li><button type="button" x-on:click="step = 'books'" class="rounded-full px-3 py-1.5 font-semibold" :class="step === 'books' ? 'bg-primary-deep text-white' : 'text-brand-strong hover:bg-cream'"
                    x-text="book ? book.name : 'Libro'">Libro</button></li>
        <li aria-hidden="true" class="text-muted"><x-ui.icon name="chevron-right" class="size-4" /></li>
        <li><button type="button" x-on:click="book && (step = 'chapters')" :disabled="! book" class="rounded-full px-3 py-1.5 font-semibold disabled:text-muted"
                    :class="step === 'chapters' ? 'bg-primary-deep text-white' : 'text-brand-strong hover:bg-cream'"
                    x-text="chapterChoice && step === 'verses' ? 'Capítulo ' + chapterChoice : 'Capítulo'">Capítulo</button></li>
        <li aria-hidden="true" class="text-muted"><x-ui.icon name="chevron-right" class="size-4" /></li>
        <li><span class="rounded-full px-3 py-1.5 font-semibold" :class="step === 'verses' ? 'bg-primary-deep text-white' : 'text-muted'">Versículo</span></li>
    </ol>

    {{-- 1. Libro --}}
    <div x-show="step === 'books'" class="mt-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex gap-2" role="tablist" x-show="! filter">
                <template x-for="(label, key) in { AT: 'Antiguo Testamento', NT: 'Nuevo Testamento' }" :key="key">
                    <button type="button" role="tab" x-on:click="testament = key" :aria-selected="(testament === key).toString()"
                            class="rounded-full px-4 py-2 text-sm font-semibold" :class="testament === key ? 'bg-primary text-white' : 'bg-stone-100 text-primary hover:bg-cream'" x-text="label"></button>
                </template>
            </div>
            <label class="relative sm:w-64">
                <span class="sr-only">Filtrar libros</span>
                <x-ui.icon name="search" class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted" />
                <input type="search" x-model="filter" placeholder="Buscar libro…" autocomplete="off"
                       x-on:keydown.enter.prevent="shownBooks().length === 1 && chooseBook(shownBooks()[0])"
                       class="block min-h-10 w-full rounded-full border border-stone-300 bg-white py-1.5 ps-9 pe-3 text-sm focus:border-brand-strong">
            </label>
        </div>
        <ul class="mt-4 grid grid-cols-2 gap-1.5 sm:grid-cols-3 lg:grid-cols-4">
            <template x-for="b in shownBooks()" :key="b.slug">
                <li>
                    <button type="button" x-on:click="chooseBook(b)"
                            class="flex min-h-11 w-full items-center justify-between gap-2 rounded-control px-3 text-left text-sm ring-1 ring-line hover:bg-cream hover:ring-brand"
                            :class="book && b.slug === book.slug ? 'bg-cream font-bold text-brand-strong' : 'bg-white text-ink'">
                        <span x-text="b.name"></span><span class="text-xs text-muted tabular-nums" x-text="b.chapters"></span>
                    </button>
                </li>
            </template>
        </ul>
        <p x-show="filter && ! shownBooks().length" class="mt-4 text-sm text-muted">Ningún libro coincide con «<span x-text="filter"></span>».</p>
    </div>

    {{-- 2. Capítulo --}}
    <div x-show="step === 'chapters'" x-cloak class="mt-4">
        <template x-if="book">
            <div>
                <p class="font-display text-2xl font-bold text-primary" x-text="book.name"></p>
                <p class="text-sm text-muted" x-text="book.chapters === 1 ? 'Un capítulo · tócalo para ver sus versículos' : book.chapters + ' capítulos · elige uno para ver sus versículos'"></p>
                <ul class="mt-4 grid grid-cols-6 gap-1.5 sm:grid-cols-[repeat(auto-fill,minmax(3.25rem,1fr))]">
                    <template x-for="n in book.chapters" :key="n">
                        <li>
                            <button type="button" x-on:click="chooseChapter(n)" class="flex h-12 w-full items-center justify-center rounded-control text-sm font-semibold tabular-nums transition"
                                    :class="isCurrent(n) ? 'bg-brand-strong text-white' : 'bg-stone-100 hover:bg-accent-light/40'" x-text="n"></button>
                        </li>
                    </template>
                </ul>
            </div>
        </template>
    </div>

    {{-- 3. Versículo --}}
    <div x-show="step === 'verses'" x-cloak class="mt-4">
        <template x-if="book && chapterChoice">
            <div>
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <p class="font-display text-2xl font-bold text-primary" x-text="book.name + ' ' + chapterChoice"></p>
                        <p class="text-sm text-muted">Elige el versículo que quieres leer</p>
                    </div>
                    <a :href="url(book.slug, chapterChoice)" class="inline-flex min-h-10 items-center gap-1.5 rounded-full bg-primary-deep px-4 text-sm font-semibold text-white hover:bg-brand-strong">
                        Leer el capítulo completo <x-ui.icon name="arrow-right" class="size-4" />
                    </a>
                </div>
                <p x-show="loading" class="mt-4 text-sm text-muted" role="status">Cargando versículos…</p>
                <p x-show="loadError" class="mt-4 text-sm text-danger" role="status">No pudimos cargar los versículos. Abre el capítulo completo.</p>
                <ul x-show="! loading && verseCount" class="mt-4 grid grid-cols-6 gap-1.5 sm:grid-cols-[repeat(auto-fill,minmax(3.25rem,1fr))]">
                    <template x-for="v in verseCount" :key="v">
                        <li>
                            <a :href="verseUrl(v)" x-on:click="pickVerse($event, v)"
                               class="flex h-12 w-full items-center justify-center rounded-control bg-stone-100 text-sm font-semibold tabular-nums transition hover:bg-accent-light/40"
                               :class="isHighlighted(v) && 'bg-brand-strong! text-white'" x-text="v"></a>
                        </li>
                    </template>
                </ul>
            </div>
        </template>
    </div>
</div>
