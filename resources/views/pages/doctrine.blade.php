<x-layouts.public title="Doctrina" description="Doctrinario del Movimiento Misionero Mundial: las instrucciones bíblicas que enseña la iglesia.">
    <x-ui.page-intro title="Doctrina" eyebrow="Instrucciones bíblicas">
        Las instrucciones bíblicas que enseña el Movimiento Misionero Mundial, reunidas en su Doctrinario. Pasa las páginas para leerlo aquí mismo.
    </x-ui.page-intro>

    <div class="container-content pb-16">
        @if ($book)
            {{-- ===== Lector propio: páginas con enlaces firmados (DoctrinePageController) ===== --}}
            @php
                $ratio = round($book->page_width / max(1, $book->page_height), 4);
            @endphp
            <section aria-labelledby="doctrinario-titulo"
                     x-data="doctrineBook({ id: {{ $book->id }}, pages: @js($pages), ratio: {{ $ratio }}, toc: @js($toc) })"
                     x-on:keydown.window="keys($event)">
                <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <x-ui.section-label>Doctrinario del MMM</x-ui.section-label>
                        <h2 id="doctrinario-titulo" class="mt-2 text-2xl font-bold sm:text-3xl">{{ $book->title }}</h2>
                        @if ($book->author)<p class="mt-1 text-muted">{{ $book->author }}</p>@endif
                    </div>
                    <p class="text-sm text-muted">{{ $book->page_count }} páginas</p>
                </div>

                {{-- El recuadro se ajusta al libro y queda centrado; las flechas van por fuera. --}}
                <div class="relative mx-auto mt-8 w-fit max-w-full">
                <div x-ref="reader" class="flex scroll-mt-24 flex-col overflow-clip rounded-card bg-primary-deep shadow-[0_30px_60px_-30px_rgb(57_49_45/0.7)] ring-1 ring-black/10 [&:fullscreen]:overflow-y-auto [&:fullscreen]:rounded-none"
                     x-on:contextmenu.prevent>
                    {{-- Escenario del libro --}}
                    <div class="relative flex flex-1 items-center justify-center overflow-clip bg-[radial-gradient(ellipse_at_center,rgb(255_255_255/0.08),transparent_70%)] p-3 select-none sm:p-4 [touch-action:pan-y]"
                         x-on:touchstart.passive="touchStart($event)" x-on:touchmove="touchMove($event)" x-on:touchend="touchEnd($event)" x-on:touchcancel="swipe = null; pinch = null">
                        <div x-ref="book" class="relative transition-transform duration-500 ease-out-soft [perspective:2600px]"
                             :style="bookStyle()" :class="shiftClass()">
                            <template x-if="wide">
                                {{-- Doble página (escritorio) --}}
                                <div class="relative size-full">
                                    <div class="absolute inset-y-0 left-0 w-1/2 overflow-hidden bg-white shadow-[inset_-18px_0_24px_-18px_rgb(0_0_0/0.35)]" x-show="base().left" x-on:click="prev()">
                                        <img :src="pages[base().left - 1]" :alt="'Página ' + base().left" class="size-full object-contain" draggable="false">
                                    </div>
                                    <div class="absolute inset-y-0 right-0 w-1/2 overflow-hidden bg-white shadow-[inset_18px_0_24px_-18px_rgb(0_0_0/0.3)]" x-show="base().right" x-on:click="next()">
                                        <img :src="pages[base().right - 1]" :alt="'Página ' + base().right" class="size-full object-contain" draggable="false">
                                    </div>
                                    {{-- Hoja que gira --}}
                                    <template x-if="flip">
                                        <div class="absolute inset-y-0 w-1/2 transition-transform duration-700 ease-in-out [transform-style:preserve-3d]"
                                             :class="flip.dir === 'next' ? 'right-0 origin-left' : 'left-0 origin-right'"
                                             :style="`transform: rotateY(${flip.turned ? (flip.dir === 'next' ? -180 : 180) : 0}deg)`">
                                            <div class="absolute inset-0 overflow-hidden bg-white [backface-visibility:hidden]">
                                                <img :src="pages[flip.front - 1]" alt="" class="size-full object-contain" draggable="false">
                                            </div>
                                            <div class="absolute inset-0 overflow-hidden bg-white [backface-visibility:hidden] [transform:rotateY(180deg)]">
                                                <img :src="pages[flip.back - 1]" alt="" class="size-full object-contain" draggable="false">
                                            </div>
                                        </div>
                                    </template>
                                    <div class="pointer-events-none absolute inset-y-0 left-1/2 w-px -translate-x-1/2 bg-black/15" x-show="base().left && base().right" aria-hidden="true"></div>
                                </div>
                            </template>
                            <template x-if="! wide">
                                {{-- Una página (móvil) --}}
                                <div class="relative size-full overflow-hidden bg-white shadow-xl">
                                    <img :src="pages[page - 1]" :alt="'Página ' + page" class="size-full object-contain" :class="motion" draggable="false" x-on:click="tap($event)">
                                </div>
                            </template>
                        </div>

                    </div>

                    {{-- Barra de controles: fija abajo mientras se lee bajando con el scroll --}}
                    <div class="sticky bottom-0 z-10 flex flex-wrap items-center gap-x-1 gap-y-2 border-t border-white/10 bg-primary-deep px-2 py-2.5 text-white shadow-[0_-10px_24px_-14px_rgb(0_0_0/0.6)] sm:gap-x-3 sm:px-5">
                        <button type="button" x-on:click="prev()" :disabled="! canPrev()" class="inline-flex size-9 items-center justify-center rounded-full hover:bg-white/10 sm:size-10 disabled:opacity-30">
                            <x-ui.icon name="chevron-left" class="size-5" /><span class="sr-only">Anterior</span>
                        </button>
                        <span class="min-w-0 text-center text-xs tabular-nums sm:min-w-28 sm:text-sm" aria-live="polite" x-text="label()"></span>
                        <span x-show="section()" class="hidden max-w-64 truncate text-sm text-white/70 xl:inline" x-text="section()?.title"></span>
                        <button type="button" x-on:click="next()" :disabled="! canNext()" class="inline-flex size-9 items-center justify-center rounded-full hover:bg-white/10 sm:size-10 disabled:opacity-30">
                            <x-ui.icon name="chevron-right" class="size-5" /><span class="sr-only">Siguiente</span>
                        </button>
                        <label class="order-last w-full sm:order-none sm:w-auto sm:flex-1">
                            <span class="sr-only">Ir a la página</span>
                            <input type="range" min="1" :max="pages.length" :value="page" x-on:change="goTo(+$event.target.value)"
                                   class="w-full accent-[var(--color-accent-light)]">
                        </label>
                        <div class="ms-auto flex items-center gap-1">
                            <button type="button" x-show="toc.length" x-on:click="openIndex()" class="inline-flex h-9 items-center gap-2 rounded-full px-2.5 text-sm font-semibold hover:bg-white/10 sm:h-10 sm:px-3" title="Índice">
                                <x-ui.icon name="list" class="size-5" /><span class="sr-only sm:not-sr-only">Índice</span>
                            </button>
                            <button type="button" x-on:click="$refs.thumbs.showModal()" class="inline-flex size-9 items-center justify-center rounded-full hover:bg-white/10 sm:size-10" title="Todas las páginas">
                                <x-ui.icon name="grid" class="size-5" /><span class="sr-only">Todas las páginas</span>
                            </button>
                            <button type="button" x-on:click="openZoom()" class="inline-flex size-9 items-center justify-center rounded-full hover:bg-white/10 sm:size-10" title="Zoom">
                                <x-ui.icon name="zoom-in" class="size-5" /><span class="sr-only">Zoom</span>
                            </button>
                            <button type="button" x-on:click="fullscreen()" class="inline-flex size-9 items-center justify-center rounded-full hover:bg-white/10 sm:size-10" title="Pantalla completa">
                                <x-ui.icon name="external" class="size-5" /><span class="sr-only">Pantalla completa</span>
                            </button>
                        </div>
                    </div>
                </div>

                    {{-- Flechas por fuera del recuadro (escritorio) --}}
                    <button type="button" x-on:click="prev()" :disabled="! canPrev()"
                            class="absolute top-1/2 -left-16 hidden size-12 -translate-y-1/2 items-center justify-center rounded-full bg-white text-primary shadow-card ring-1 ring-line transition hover:bg-cream disabled:opacity-0 lg:flex">
                        <x-ui.icon name="chevron-left" class="size-6" /><span class="sr-only">Página anterior</span>
                    </button>
                    <button type="button" x-on:click="next()" :disabled="! canNext()"
                            class="absolute top-1/2 -right-16 hidden size-12 -translate-y-1/2 items-center justify-center rounded-full bg-white text-primary shadow-card ring-1 ring-line transition hover:bg-cream disabled:opacity-0 lg:flex">
                        <x-ui.icon name="chevron-right" class="size-6" /><span class="sr-only">Página siguiente</span>
                    </button>
                </div>
                <p class="mt-4 text-center text-sm text-muted">
                    <span class="hidden lg:inline">Usa ← → o toca las páginas para avanzar.</span>
                    <span class="lg:hidden">Desliza a los lados o toca los bordes para pasar de página · pellizca o toca dos veces para ampliar.</span>
                </p>

                {{-- Índice: lecciones del libro; al tocar una, el libro va a su página --}}
                <dialog x-ref="index" aria-labelledby="indice-titulo" x-on:click="if ($event.target === $el) $el.close()"
                        class="m-0 mt-auto max-h-[85dvh] w-full max-w-none overflow-hidden rounded-t-card bg-white p-0 shadow-2xl backdrop:bg-primary-deep/60 backdrop:backdrop-blur-sm
                               sm:mt-0 sm:mr-auto sm:h-dvh sm:max-h-none sm:w-[26rem] sm:rounded-none sm:rounded-r-card">
                    <div class="flex h-full max-h-[85dvh] flex-col sm:max-h-none">
                        <div class="flex items-center justify-between gap-3 border-b border-line px-5 py-4">
                            <div class="min-w-0">
                                <p class="text-xs font-semibold tracking-[0.14em] text-brand-strong uppercase">Índice</p>
                                <h3 id="indice-titulo" class="truncate font-display text-lg font-bold text-primary">{{ $book->title }}</h3>
                            </div>
                            <button type="button" x-on:click="$refs.index.close()" class="inline-flex size-10 shrink-0 items-center justify-center rounded-full hover:bg-cream"><x-ui.icon name="close" class="size-5" /><span class="sr-only">Cerrar</span></button>
                        </div>
                        <ol class="flex-1 overflow-y-auto p-2" x-ref="indexList">
                            <template x-for="(entry, i) in toc" :key="i">
                                <li>
                                    <button type="button" x-on:click="goTo(entry.page); $refs.index.close()"
                                            class="group flex w-full items-center gap-3 rounded-control px-3 py-2.5 text-left transition"
                                            :class="isSection(entry) ? 'bg-brand-strong text-white' : 'hover:bg-cream'"
                                            :aria-current="isSection(entry) ? 'true' : null" :data-current="isSection(entry)">
                                        <span class="min-w-0 flex-1">
                                            <span class="block text-xs font-semibold tracking-wide uppercase" :class="isSection(entry) ? 'text-white/80' : 'text-brand-strong'" x-text="parts(entry).label"></span>
                                            <span class="block font-semibold leading-snug" :class="isSection(entry) ? 'text-white' : 'text-ink'" x-text="parts(entry).title"></span>
                                        </span>
                                        <span class="shrink-0 text-xs tabular-nums" :class="isSection(entry) ? 'text-white/80' : 'text-muted'" x-text="'pág. ' + entry.page"></span>
                                    </button>
                                </li>
                            </template>
                        </ol>
                    </div>
                </dialog>

                {{-- Miniaturas --}}
                <dialog x-ref="thumbs" aria-label="Todas las páginas" x-on:click="if ($event.target === $el) $el.close()"
                        class="m-auto max-h-[85dvh] w-[min(56rem,calc(100%-2rem))] overflow-hidden rounded-card bg-white p-0 shadow-2xl backdrop:bg-primary-deep/70 backdrop:backdrop-blur-sm">
                    <div class="flex items-center justify-between border-b border-line px-5 py-3">
                        <p class="font-bold text-primary">Todas las páginas</p>
                        <button type="button" x-on:click="$refs.thumbs.close()" class="inline-flex size-10 items-center justify-center rounded-full hover:bg-cream"><x-ui.icon name="close" class="size-5" /><span class="sr-only">Cerrar</span></button>
                    </div>
                    <ul class="grid max-h-[70dvh] grid-cols-3 gap-3 overflow-y-auto p-5 sm:grid-cols-5 lg:grid-cols-6" x-on:contextmenu.prevent>
                        <template x-for="(src, i) in pages" :key="i">
                            <li>
                                <button type="button" x-on:click="goTo(i + 1); $refs.thumbs.close()" class="block w-full rounded-control p-1 text-center ring-1 transition"
                                        :class="isVisible(i + 1) ? 'ring-2 ring-brand-strong' : 'ring-line hover:ring-brand'">
                                    <img :src="src" :alt="'Página ' + (i + 1)" loading="lazy" draggable="false" class="w-full bg-stone-50 object-contain" :style="`aspect-ratio: ${ratio}`">
                                    <span class="mt-1 block text-xs text-muted tabular-nums" x-text="i + 1"></span>
                                </button>
                            </li>
                        </template>
                    </ul>
                </dialog>

                {{-- Zoom: una página, de 100 % a 400 %; pellizcar, arrastrar, Ctrl + rueda y doble toque --}}
                <dialog x-ref="zoom" aria-label="Zoom de la página" x-on:close="zoomOpen = false"
                        class="m-0 size-full max-h-none max-w-none bg-stone-900 p-0 text-white backdrop:bg-black">
                    <div class="flex h-full flex-col">
                        <div class="flex items-center justify-between gap-2 border-b border-white/10 bg-black/40 px-2 py-2 sm:px-4">
                            <div class="flex items-center gap-1">
                                <button type="button" x-on:click="zoomTo(zoomPage - 1)" :disabled="zoomPage <= 1" class="inline-flex size-10 items-center justify-center rounded-full hover:bg-white/10 disabled:opacity-30">
                                    <x-ui.icon name="chevron-left" class="size-5" /><span class="sr-only">Página anterior</span>
                                </button>
                                <span class="min-w-0 text-center text-sm tabular-nums" x-text="`${zoomPage} / ${pages.length}`"></span>
                                <button type="button" x-on:click="zoomTo(zoomPage + 1)" :disabled="zoomPage >= pages.length" class="inline-flex size-10 items-center justify-center rounded-full hover:bg-white/10 disabled:opacity-30">
                                    <x-ui.icon name="chevron-right" class="size-5" /><span class="sr-only">Página siguiente</span>
                                </button>
                            </div>
                            <div class="flex items-center gap-1 rounded-full bg-white/10 p-1">
                                <button type="button" x-on:click="zoomBy(1 / 1.25)" :disabled="scale <= 1" class="inline-flex size-9 items-center justify-center rounded-full hover:bg-white/15 disabled:opacity-30">
                                    <x-ui.icon name="zoom-out" class="size-5" /><span class="sr-only">Alejar</span>
                                </button>
                                <button type="button" x-on:click="setScale(1)" class="min-w-14 rounded-full px-2 py-1 text-sm font-semibold tabular-nums hover:bg-white/15" title="Ajustar a la pantalla"
                                        x-text="Math.round(scale * 100) + ' %'"></button>
                                <button type="button" x-on:click="zoomBy(1.25)" :disabled="scale >= maxScale" class="inline-flex size-9 items-center justify-center rounded-full hover:bg-white/15 disabled:opacity-30">
                                    <x-ui.icon name="zoom-in" class="size-5" /><span class="sr-only">Acercar</span>
                                </button>
                                <button type="button" x-on:click="setScale(1)" x-show="scale > 1" class="hidden size-9 items-center justify-center rounded-full hover:bg-white/15 sm:inline-flex" title="Ajustar a la pantalla">
                                    <x-ui.icon name="fit" class="size-5" /><span class="sr-only">Ajustar a la pantalla</span>
                                </button>
                            </div>
                            <button type="button" x-on:click="$refs.zoom.close()" class="inline-flex size-10 items-center justify-center rounded-full hover:bg-white/10">
                                <x-ui.icon name="close" class="size-5" /><span class="sr-only">Cerrar</span>
                            </button>
                        </div>
                        <div x-ref="viewport" class="relative flex-1 overflow-auto overscroll-contain select-none [touch-action:pan-x_pan-y]"
                             :class="scale > 1 ? (dragging ? 'cursor-grabbing' : 'cursor-grab') : 'cursor-zoom-in'"
                             x-on:contextmenu.prevent x-on:wheel="wheel($event)" x-on:dblclick="toggleZoom($event)"
                             x-on:pointerdown="dragStart($event)" x-on:pointermove="dragMove($event)" x-on:pointerup="dragEnd()" x-on:pointercancel="dragEnd()" x-on:pointerleave="dragEnd()"
                             x-on:touchstart="pinchStart($event)" x-on:touchmove="pinchMove($event)" x-on:touchend="pinchEnd($event)">
                            {{-- Centrado mientras cabe; con zoom, se desplaza --}}
                            <div class="flex min-h-full w-max min-w-full items-center justify-center p-3 sm:p-6">
                                <img :src="pages[zoomPage - 1]" :alt="'Página ' + zoomPage" draggable="false"
                                     class="max-w-none bg-white shadow-2xl" :style="`width: ${zoomWidth()}px; aspect-ratio: ${ratio}`">
                            </div>
                        </div>
                        <p class="hidden border-t border-white/10 bg-black/40 px-4 py-2 text-center text-xs text-white/70 sm:block">
                            Doble clic para acercar · arrastra para moverte · Ctrl + rueda para zoom · + / − / 0 en el teclado
                        </p>
                    </div>
                </dialog>
            </section>
        @elseif ($bookUrl)
            {{-- Mientras no se suba el PDF: el libro de FlipHTML5 --}}
            <section aria-labelledby="doctrinario-titulo">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <x-ui.section-label>Doctrinario del MMM</x-ui.section-label>
                        <h2 id="doctrinario-titulo" class="mt-2 text-2xl font-bold sm:text-3xl">Instrucciones Bíblicas para los Recién Convertidos</h2>
                        <p class="mt-1 text-muted">Luis M. Ortiz</p>
                    </div>
                    <x-ui.button href="{{ $bookUrl }}" variant="secondary" target="_blank" rel="noopener" class="self-start sm:self-auto">
                        <x-ui.icon name="external" class="size-5" /> Abrir en pantalla completa<span class="sr-only"> (se abre en una pestaña nueva)</span>
                    </x-ui.button>
                </div>
                <div class="mt-6 overflow-hidden rounded-card bg-primary-deep shadow-[0_30px_60px_-30px_rgb(57_49_45/0.7)] ring-1 ring-black/10">
                    <iframe src="{{ $bookUrl }}" title="Doctrinario del Movimiento Misionero Mundial"
                            class="block h-[72dvh] min-h-[26rem] w-full lg:h-auto lg:aspect-16/10"
                            sandbox="allow-scripts allow-same-origin allow-popups allow-popups-to-escape-sandbox"
                            allow="fullscreen" loading="lazy" referrerpolicy="strict-origin-when-cross-origin"></iframe>
                </div>
            </section>
        @else
            <x-ui.empty-state>El Doctrinario se publicará aquí en cuanto la organización lo confirme.</x-ui.empty-state>
        @endif

        {{-- Enseñanzas de la categoría «Doctrina» (Panel › Enseñanzas) --}}
        @if ($teachings->isNotEmpty())
            <section class="mt-16" aria-labelledby="ensenanzas-doctrina">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <x-ui.section-label>Palabra</x-ui.section-label>
                        <h2 id="ensenanzas-doctrina" class="mt-2 text-2xl font-bold sm:text-3xl">Enseñanzas de doctrina</h2>
                    </div>
                    <a href="{{ route('teachings.index', ['categoria' => \App\Enums\TeachingCategory::Doctrine->value]) }}" class="inline-flex min-h-11 items-center gap-2 font-semibold text-brand-strong hover:underline">
                        Ver todas <x-ui.icon name="arrow-right" class="size-4" />
                    </a>
                </div>
                <ul class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($teachings as $teaching)
                        <li>@include('teachings.partials.card', ['teaching' => $teaching, 'featured' => false])</li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>

    @if ($book)
        <script>
            /**
             * Lector del Doctrinario: doble página con hoja que gira (escritorio)
             * o una página con deslizamiento (móvil). Recuerda la última página
             * leída en este navegador.
             */
            function doctrineBook({ id, pages, ratio, toc }) {
                const store = {
                    get() { try { return +localStorage.getItem('doctrina-' + id) || 1; } catch { return 1; } },
                    set(n) { try { localStorage.setItem('doctrina-' + id, n); } catch {} },
                };
                const reduced = matchMedia('(prefers-reduced-motion: reduce)').matches;
                const wideQuery = matchMedia('(min-width: 1024px)');

                return {
                    pages, ratio, toc,
                    page: Math.min(Math.max(store.get(), 1), pages.length),
                    wide: wideQuery.matches,
                    flip: null,
                    motion: '',
                    isFull: false,
                    swipe: null,
                    lastTap: null,

                    init() {
                        wideQuery.addEventListener('change', (e) => { this.wide = e.matches; this.flip = null; });
                        addEventListener('resize', () => this.zoomOpen && this.measure());
                        document.addEventListener('fullscreenchange', () => { this.isFull = document.fullscreenElement === this.$refs.reader; });
                        this.$watch('page', (n) => { store.set(n); this.preload(); });
                        this.preload();
                    },

                    // ----- Qué se ve -----
                    // Escritorio: portada sola a la derecha; luego pares (2-3, 4-5...).
                    spread(p = this.page) {
                        if (p === 1) return { left: null, right: 1 };
                        const left = p % 2 === 0 ? p : p - 1;
                        return { left, right: left + 1 <= pages.length ? left + 1 : null };
                    },
                    base() { return this.flip ? this.flip.base : this.spread(); },
                    visiblePages() {
                        if (! this.wide) return [this.page];
                        const s = this.spread();
                        return [s.left, s.right].filter(Boolean);
                    },
                    isVisible(n) { return this.visiblePages().includes(n); },
                    label() {
                        const v = this.visiblePages();
                        return v.length > 1 ? `Páginas ${v[0]}–${v[1]} de ${pages.length}` : `Página ${v[0]} de ${pages.length}`;
                    },
                    // El libro cerrado (portada o contraportada) se centra.
                    shiftClass() {
                        if (! this.wide || this.flip) return '';
                        const s = this.spread();
                        return ! s.left ? '-translate-x-1/4' : (! s.right ? 'translate-x-1/4' : '');
                    },
                    // Al cambiar de hoja, se vuelve al inicio de las páginas (si ya se había bajado).
                    toTop() {
                        const reader = this.$refs.reader;
                        if (document.fullscreenElement === reader) { reader.scrollTo({ top: 0, behavior: 'smooth' }); return; }
                        if (reader.getBoundingClientRect().top < 0) reader.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    },
                    // Tamaño del libro: el mayor que cabe entero en la pantalla (el recuadro se ajusta a él).
                    bookStyle() {
                        const r = this.wide ? ratio * 2 : ratio;
                        // Alto: menos la cabecera del sitio y la barra de controles (en pantalla completa, solo la barra).
                        const height = this.isFull ? '100dvh - 6rem' : '100dvh - 11.5rem';
                        // Ancho: menos los márgenes y, en escritorio, el lugar de las flechas exteriores.
                        const width = this.isFull ? '100vw - 2rem' : (this.wide ? '100vw - 12rem' : '100vw - 3.5rem');
                        return `aspect-ratio: ${r}; width: min(${width}, (${height}) * ${r}, 84rem)`;
                    },

                    // ----- Índice -----
                    // Lección en la que se está: la última que empieza en una página visible o antes.
                    section() {
                        const last = Math.max(...this.visiblePages());
                        return toc.filter((e) => e.page <= last).at(-1) ?? null;
                    },
                    // Por valor (Alpine envuelve los objetos: no se pueden comparar con ===).
                    isSection(entry) {
                        const s = this.section();
                        return !! s && s.page === entry.page && s.title === entry.title;
                    },
                    // «Lección 4 · El único Dios» → etiqueta «Lección 4» y título «El único Dios».
                    parts(entry) {
                        const [label, ...rest] = entry.title.split(' · ');
                        return rest.length ? { label, title: rest.join(' · ') } : { label: '', title: entry.title };
                    },
                    openIndex() {
                        this.$refs.index.showModal();
                        this.$nextTick(() => this.$refs.indexList.querySelector('[data-current=true]')?.scrollIntoView({ block: 'center' }));
                    },

                    // ----- Avanzar y retroceder -----
                    canPrev() { return this.page > 1; },
                    canNext() { const v = this.visiblePages(); return v[v.length - 1] < pages.length; },
                    next() {
                        if (! this.canNext() || this.flip) return;
                        if (! this.wide) return this.show(this.page + 1, 'next');
                        const now = this.spread();
                        const target = this.spread((now.right ?? now.left) + 1);
                        this.turn('next', now.right ?? now.left, target.left, { left: now.left, right: target.right }, target.left);
                    },
                    prev() {
                        if (! this.canPrev() || this.flip) return;
                        if (! this.wide) return this.show(this.page - 1, 'prev');
                        const now = this.spread();
                        const target = this.spread(now.left === 2 ? 1 : (now.left ?? 1) - 2);
                        this.turn('prev', now.left, target.right, { left: target.left, right: now.right }, target.left ?? target.right);
                    },
                    // Hoja que gira: cara delantera = página que se va; trasera = la que llega.
                    turn(dir, front, back, base, landing) {
                        if (reduced || ! front || ! back) { this.page = landing; this.$nextTick(() => this.toTop()); return; }
                        this.flip = { dir, front, back, base, turned: false };
                        requestAnimationFrame(() => requestAnimationFrame(() => { if (this.flip) this.flip.turned = true; }));
                        setTimeout(() => { this.page = landing; this.flip = null; this.toTop(); }, 720);
                    },
                    show(n, dir) {
                        this.motion = '';
                        this.page = n;
                        this.$nextTick(() => { this.motion = dir === 'next' ? 'book-in-next' : 'book-in-prev'; });
                    },
                    // Salto (índice, miniaturas, barra): primero se cargan las páginas de destino,
                    // para no ver un instante la página anterior en su lugar.
                    async goTo(n) {
                        n = Math.min(Math.max(n, 1), pages.length);
                        const s = this.spread(n);
                        const target = this.wide ? [s.left, s.right].filter(Boolean) : [n];
                        await Promise.race([Promise.all(target.map((p) => this.load(p))), new Promise((r) => setTimeout(r, 1500))]);
                        this.flip = null;
                        this.page = n;
                        this.$nextTick(() => this.toTop());
                    },
                    load(p) {
                        return new Promise((resolve) => {
                            const img = new Image();
                            img.onload = img.onerror = () => resolve();
                            img.src = pages[p - 1];
                        });
                    },

                    // ----- Gestos y teclado -----
                    // Toque en la página (móvil): los bordes pasan de página; en el centro, dos
                    // toques seguidos la amplían en ese punto. Así, leer no cambia de página sin querer.
                    tap(e) {
                        const r = e.currentTarget.getBoundingClientRect();
                        const x = (e.clientX - r.left) / r.width;
                        if (x < 0.25) return this.prev();
                        if (x > 0.75) return this.next();
                        if (this.lastTap && Date.now() - this.lastTap < 350) {
                            this.lastTap = null;
                            return this.zoomAt(this.page, { x, y: (e.clientY - r.top) / r.height }, 2.5);
                        }
                        this.lastTap = Date.now();
                    },
                    // Un dedo que se desliza pasa de página; dos dedos son un pellizco, que abre el zoom.
                    touchStart(e) {
                        if (e.touches.length > 1) {
                            this.swipe = null;
                            const [a, b] = e.touches;
                            if (! this.zoomOpen) this.pinch = { stage: true, dist: Math.hypot(a.clientX - b.clientX, a.clientY - b.clientY) };
                            return;
                        }
                        // Con el navegador ampliado, el dedo mueve la vista: no se pasa de página.
                        if ((window.visualViewport?.scale ?? 1) > 1.05) return;
                        const t = e.touches[0];
                        this.swipe = { x: t.clientX, y: t.clientY, time: Date.now() };
                    },
                    touchMove(e) {
                        if (! this.pinch?.stage || e.touches.length !== 2) return;
                        e.preventDefault();
                        const [a, b] = e.touches;
                        const dist = Math.hypot(a.clientX - b.clientX, a.clientY - b.clientY);
                        const mid = { x: (a.clientX + b.clientX) / 2, y: (a.clientY + b.clientY) / 2 };
                        if (! this.zoomOpen) {
                            if (dist < this.pinch.dist * 1.1) return; // pellizco hacia dentro o muy leve: nada
                            this.pinch.dist = dist;
                            const { n, point } = this.pageAt(mid);
                            return this.zoomAt(n, point, 1.15);
                        }
                        this.setScale(1.15 * dist / this.pinch.dist, mid);
                    },
                    touchEnd(e) {
                        if (this.pinch?.stage) {
                            if (! e.touches.length) this.pinch = null;
                            return;
                        }
                        const s = this.swipe;
                        this.swipe = null;
                        if (! s || e.touches.length) return;
                        const t = e.changedTouches[0];
                        const dx = t.clientX - s.x;
                        const dy = t.clientY - s.y;
                        // Solo un gesto claro: rápido, largo y sobre todo horizontal (no al bajar leyendo).
                        const min = Math.max(60, e.currentTarget.clientWidth * 0.15);
                        if (Date.now() - s.time > 800 || Math.abs(dx) < min || Math.abs(dx) < Math.abs(dy) * 2) return;
                        if (e.cancelable) e.preventDefault(); // sin el «clic» que el navegador genera al soltar
                        this.lastTap = null;
                        dx < 0 ? this.next() : this.prev();
                    },
                    // Página bajo un punto de la pantalla y la posición (0–1) del punto en ella.
                    pageAt(pt) {
                        const r = this.$refs.book.getBoundingClientRect();
                        const x = Math.min(1, Math.max(0, (pt.x - r.left) / r.width));
                        const y = Math.min(1, Math.max(0, (pt.y - r.top) / r.height));
                        if (! this.wide) return { n: this.page, point: { x, y } };
                        const s = this.spread();
                        if (! s.left || ! s.right) return { n: s.left ?? s.right, point: { x: 0.5, y } };
                        return x < 0.5 ? { n: s.left, point: { x: x * 2, y } } : { n: s.right, point: { x: x * 2 - 1, y } };
                    },
                    keys(e) {
                        if (/input|textarea|select/i.test(e.target.tagName) || e.altKey || e.ctrlKey || e.metaKey) return;
                        if (this.zoomOpen) return this.zoomKeys(e);
                        if (e.key === 'ArrowRight') { e.preventDefault(); this.next(); }
                        if (e.key === 'ArrowLeft') { e.preventDefault(); this.prev(); }
                    },

                    // ----- Zoom (una página, 100 %–400 %) -----
                    zoomOpen: false,
                    zoomPage: 1,
                    scale: 1,
                    maxScale: 4,
                    fitWidth: 600,
                    dragging: false,
                    drag: null,
                    pinch: null,

                    openZoom(n = null) {
                        this.zoomPage = n ?? this.visiblePages()[0];
                        this.scale = 1;
                        this.zoomOpen = true;
                        this.$refs.zoom.showModal();
                        this.$nextTick(() => this.measure());
                    },
                    // Abre el zoom ya ampliado y centrado en un punto (0–1) de la página.
                    zoomAt(n, point, scale) {
                        this.openZoom(n);
                        this.$nextTick(() => {
                            this.scale = Math.min(this.maxScale, scale);
                            this.$nextTick(() => {
                                const v = this.$refs.viewport;
                                const img = v.querySelector('img');
                                v.scrollLeft = img.offsetLeft + point.x * img.offsetWidth - v.clientWidth / 2;
                                v.scrollTop = img.offsetTop + point.y * img.offsetHeight - v.clientHeight / 2;
                            });
                        });
                    },
                    zoomTo(n) {
                        if (n < 1 || n > pages.length) return;
                        this.zoomPage = n;
                        this.scale = 1;
                        this.$refs.viewport.scrollTo(0, 0);
                    },
                    // Ancho con el que la página entera cabe en la pantalla (100 %).
                    measure() {
                        const v = this.$refs.viewport;
                        if (! v) return;
                        const pad = innerWidth >= 640 ? 48 : 24;
                        this.fitWidth = Math.max(200, Math.min(v.clientWidth - pad, (v.clientHeight - pad) * ratio));
                    },
                    zoomWidth() { return Math.round(this.fitWidth * this.scale); },
                    // Cambia el zoom manteniendo fijo el punto señalado (o el centro).
                    setScale(next, anchor = null) {
                        const v = this.$refs.viewport;
                        next = Math.min(this.maxScale, Math.max(1, next));
                        if (! v || next === this.scale) { this.scale = next; return; }
                        const rect = v.getBoundingClientRect();
                        const ax = anchor ? anchor.x - rect.left : v.clientWidth / 2;
                        const ay = anchor ? anchor.y - rect.top : v.clientHeight / 2;
                        const fx = (v.scrollLeft + ax) / v.scrollWidth;
                        const fy = (v.scrollTop + ay) / v.scrollHeight;
                        this.scale = next;
                        this.$nextTick(() => {
                            v.scrollLeft = fx * v.scrollWidth - ax;
                            v.scrollTop = fy * v.scrollHeight - ay;
                        });
                    },
                    zoomBy(factor, anchor = null) { this.setScale(Math.round(this.scale * factor * 100) / 100, anchor); },
                    toggleZoom(e) { this.setScale(this.scale > 1 ? 1 : 2.5, { x: e.clientX, y: e.clientY }); },
                    zoomKeys(e) {
                        if (e.key === '+' || e.key === '=') { e.preventDefault(); this.zoomBy(1.25); }
                        if (e.key === '-') { e.preventDefault(); this.zoomBy(1 / 1.25); }
                        if (e.key === '0') { e.preventDefault(); this.setScale(1); }
                        // Con zoom, las flechas mueven la página; sin zoom, cambian de página.
                        if (this.scale === 1 && e.key === 'ArrowRight') { e.preventDefault(); this.zoomTo(this.zoomPage + 1); }
                        if (this.scale === 1 && e.key === 'ArrowLeft') { e.preventDefault(); this.zoomTo(this.zoomPage - 1); }
                    },
                    // Ctrl + rueda (también el gesto de pellizcar del touchpad).
                    wheel(e) {
                        if (! e.ctrlKey) return;
                        e.preventDefault();
                        this.zoomBy(Math.exp(-e.deltaY * 0.002), { x: e.clientX, y: e.clientY });
                    },
                    // Arrastrar con el mouse para moverse por la página ampliada.
                    dragStart(e) {
                        if (e.pointerType !== 'mouse' || this.scale === 1 || e.button !== 0) return;
                        const v = this.$refs.viewport;
                        this.drag = { x: e.clientX, y: e.clientY, left: v.scrollLeft, top: v.scrollTop };
                        this.dragging = true;
                    },
                    dragMove(e) {
                        if (! this.drag) return;
                        const v = this.$refs.viewport;
                        v.scrollLeft = this.drag.left - (e.clientX - this.drag.x);
                        v.scrollTop = this.drag.top - (e.clientY - this.drag.y);
                    },
                    dragEnd() { this.drag = null; this.dragging = false; },
                    // Pellizcar con dos dedos (un dedo desplaza de forma nativa).
                    pinchStart(e) {
                        if (e.touches.length !== 2) return;
                        const [a, b] = e.touches;
                        this.pinch = { dist: Math.hypot(a.clientX - b.clientX, a.clientY - b.clientY), scale: this.scale };
                    },
                    pinchMove(e) {
                        if (! this.pinch || e.touches.length !== 2) return;
                        e.preventDefault();
                        const [a, b] = e.touches;
                        const dist = Math.hypot(a.clientX - b.clientX, a.clientY - b.clientY);
                        this.setScale(this.pinch.scale * dist / this.pinch.dist, { x: (a.clientX + b.clientX) / 2, y: (a.clientY + b.clientY) / 2 });
                    },
                    pinchEnd(e) { if (e.touches.length < 2) this.pinch = null; },
                    fullscreen() {
                        document.fullscreenElement ? document.exitFullscreen() : this.$refs.reader.requestFullscreen?.();
                    },

                    // Precarga las páginas vecinas para que el giro sea fluido.
                    preload() {
                        [this.page - 2, this.page - 1, this.page + 1, this.page + 2, this.page + 3]
                            .filter((n) => n >= 1 && n <= pages.length)
                            .forEach((n) => { new Image().src = pages[n - 1]; });
                    },
                };
            }
        </script>
    @endif
</x-layouts.public>
