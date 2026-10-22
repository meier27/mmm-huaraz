<x-layouts.public :title="$book['name'].' '.$chapter" :description="$book['name'].' capítulo '.$chapter.' ('.$versionName.').'">
    @php
        $reference = $book['name'].' '.$chapter;
        // Paginación del capítulo (en el navegador): se abre en la página del versículo enlazado.
        $perPage = 10;
        $total = count($verses ?? []);
        // Por posición (no por número): hay capítulos y versiones con numeración discontinua.
        $positions = collect($verses ?? [])->pluck('verse')->flip();
        $startPage = $highlight ? intdiv($positions[$highlight[0]] ?? 0, $perPage) + 1 : 1;
        $previousUrl = $previous ? route('bible.chapter', [$previous['book']['slug'], $previous['chapter']]) : null;
        $nextUrl = $next ? route('bible.chapter', [$next['book']['slug'], $next['chapter']]) : null;
    @endphp

    <div x-data="bibleReader({
            books: @js($books),
            current: @js(['name' => $book['name'], 'slug' => $book['slug'], 'chapter' => $chapter, 'testament' => $book['testament'], 'verses' => $total]),
            chapterUrl: @js(route('bible.chapter', ['__LIBRO__', 999])),
            countUrl: @js(route('bible.verse-count', ['__LIBRO__', 999])),
            version: @js($versionShort),
            highlight: @js($highlight),
            perPage: {{ $perPage }},
            page: {{ $startPage }},
            previousUrl: @js($previousUrl),
            nextUrl: @js($nextUrl),
         })"
         x-on:keydown.escape.window="picker = false; settings = false"
         x-on:keydown.window="keys($event)">

        {{-- ===== Barra del lector: libro/capítulo/versículo, versión, tamaño ===== --}}
        <div class="sticky top-20 z-30 border-b border-line bg-white/95 backdrop-blur">
            <div class="container-content flex h-14 items-center justify-between gap-2">
                <a href="{{ route('bible.index') }}" class="inline-flex size-10 items-center justify-center rounded-full text-primary hover:bg-cream" title="Inicio de la Biblia">
                    <x-ui.icon name="book" class="size-5" /><span class="sr-only">Inicio de la Biblia</span>
                </a>

                <div class="flex min-w-0 items-center gap-2">
                    <button type="button" x-on:click="openPicker()" :aria-expanded="picker.toString()" aria-expanded="false" aria-controls="selector-pasaje"
                            class="inline-flex min-h-10 min-w-0 items-center gap-2 rounded-full bg-stone-100 px-4 font-display text-sm font-bold text-primary uppercase hover:bg-cream">
                        <span class="truncate" x-text="title()">{{ $reference }}</span> <x-ui.icon name="chevron-right" class="size-4 shrink-0 rotate-90" />
                    </button>
                    <span class="inline-flex min-h-10 shrink-0 items-center rounded-full border border-line px-3 text-xs font-bold text-primary" title="{{ $versionName }}">{{ $versionShort }}</span>
                </div>

                <button type="button" x-on:click="settings = ! settings; picker = false" :aria-expanded="settings.toString()" aria-expanded="false" aria-controls="ajustes-lectura"
                        class="inline-flex size-10 items-center justify-center rounded-full font-display text-sm font-bold text-primary hover:bg-cream" title="Tamaño del texto">
                    Aa<span class="sr-only"> · tamaño del texto</span>
                </button>
            </div>

            {{-- Selector: libro → capítulo → versículo (compartido con la portada) --}}
            <div id="selector-pasaje" x-show="picker" x-cloak x-transition.opacity x-on:click.outside="picker = false"
                 class="absolute inset-x-0 top-full max-h-[75dvh] overflow-y-auto border-b border-line bg-white shadow-xl">
                <div class="container-content py-5">
                    @include('bible.partials.passage-steps')
                </div>
            </div>

            {{-- Ajustes de lectura --}}
            <div id="ajustes-lectura" x-show="settings" x-cloak x-transition.opacity x-on:click.outside="settings = false"
                 class="absolute top-full right-4 w-64 rounded-b-card border border-t-0 border-line bg-white p-4 shadow-xl sm:right-8">
                <p class="text-sm font-semibold text-primary">Tamaño del texto</p>
                <div class="mt-3 flex items-center justify-between gap-2">
                    <button type="button" x-on:click="setSize(size - 1)" :disabled="size === 0" class="inline-flex size-10 items-center justify-center rounded-full bg-stone-100 font-display text-sm font-bold disabled:opacity-40">A<span class="sr-only"> más pequeño</span></button>
                    <span class="flex gap-1" aria-hidden="true">
                        <template x-for="i in 5" :key="i"><span class="size-2 rounded-full" :class="i - 1 <= size ? 'bg-brand-strong' : 'bg-stone-200'"></span></template>
                    </span>
                    <button type="button" x-on:click="setSize(size + 1)" :disabled="size === 4" class="inline-flex size-10 items-center justify-center rounded-full bg-stone-100 font-display text-lg font-bold disabled:opacity-40">A<span class="sr-only"> más grande</span></button>
                </div>
            </div>
        </div>

        {{-- ===== Texto (paginado) ===== --}}
        <article x-ref="top" class="container-content max-w-2xl! scroll-mt-40 py-10 sm:py-12">
            <header class="text-center">
                <h1 class="font-display text-3xl font-bold tracking-wide text-primary uppercase sm:text-4xl">{{ $reference }}</h1>
                <p class="mt-2 text-sm text-muted">{{ $versionName }}</p>
            </header>

            @if ($verses === null)
                <x-ui.alert type="error" class="mt-10">No pudimos cargar este capítulo en este momento. Inténtalo de nuevo en unos minutos.</x-ui.alert>
            @else
                {{-- Indicador de la página actual --}}
                <div x-show="pages > 1" x-cloak class="mt-8 flex items-center justify-center gap-3 text-sm text-muted">
                    <span class="h-px w-10 bg-line" aria-hidden="true"></span>
                    <span aria-live="polite">Versículos <strong class="text-primary tabular-nums" x-text="rangeLabel()"></strong> de <span class="tabular-nums">{{ $total }}</span></span>
                    <span class="h-px w-10 bg-line" aria-hidden="true"></span>
                </div>

                <p class="mt-6 font-display leading-[2.1] text-ink" :class="sizes[size]">
                    @foreach ($verses as $i => $verse)
                        @php
                            $n = $verse['verse'];
                            $inFirstPage = intdiv($i, $perPage) + 1 === $startPage;
                            $isHighlighted = $highlight && $n >= $highlight[0] && $n <= $highlight[1];
                        @endphp
                        {{-- Sin JavaScript se ve el capítulo completo; con él, solo la página actual. --}}
                        <span id="v{{ $n }}" data-verse="{{ $n }}" data-index="{{ $i }}" data-text="{{ $verse['text'] }}"
                              @unless ($inFirstPage) x-cloak @endunless
                              x-show="inPage({{ $i }})"
                              role="button" tabindex="0"
                              x-on:click="toggle({{ $n }})" x-on:keydown.enter.prevent="toggle({{ $n }})" x-on:keydown.space.prevent="toggle({{ $n }})"
                              :aria-pressed="selected.includes({{ $n }}).toString()"
                              @class(['scroll-mt-48 cursor-pointer rounded-sm decoration-brand decoration-2 underline-offset-[6px] transition-colors hover:bg-cream focus-visible:bg-cream', 'verse-focus' => $isHighlighted])
                              :class="{ 'verse-focus': isFocused({{ $n }}), 'underline decoration-dotted': selected.includes({{ $n }}) }"><sup class="me-0.5 font-sans text-[0.65em] font-bold text-brand-strong">{{ $n }}</sup>{{ $verse['text'] }} </span>
                    @endforeach
                </p>

                {{-- Paginación --}}
                <nav x-show="pages > 1" x-cloak aria-label="Páginas del capítulo" class="mt-10 flex flex-wrap items-center justify-center gap-2">
                    <button type="button" x-on:click="go(page - 1)" :disabled="page === 1"
                            class="inline-flex size-11 items-center justify-center rounded-full bg-white text-primary shadow-sm ring-1 ring-line transition hover:bg-cream disabled:opacity-35">
                        <x-ui.icon name="chevron-left" class="size-5" /><span class="sr-only">Página anterior</span>
                    </button>
                    <template x-for="item in pageItems()" :key="item.key">
                        <span>
                            <span x-show="item.gap" class="inline-flex w-6 justify-center text-muted" aria-hidden="true">…</span>
                            <button x-show="! item.gap" type="button" x-on:click="go(item.n)" :aria-current="item.n === page ? 'page' : null"
                                    class="inline-flex size-11 items-center justify-center rounded-full text-sm font-bold tabular-nums transition"
                                    :class="item.n === page ? 'bg-primary-deep text-white shadow-md' : 'bg-white text-primary ring-1 ring-line hover:bg-cream'"
                                    x-text="item.n" :aria-label="'Página ' + item.n"></button>
                        </span>
                    </template>
                    <button type="button" x-on:click="go(page + 1)" :disabled="page === pages"
                            class="inline-flex size-11 items-center justify-center rounded-full bg-white text-primary shadow-sm ring-1 ring-line transition hover:bg-cream disabled:opacity-35">
                        <x-ui.icon name="chevron-right" class="size-5" /><span class="sr-only">Página siguiente</span>
                    </button>
                </nav>

                <p class="mt-6 text-center text-xs text-muted">
                    Toca un versículo para copiarlo o compartirlo<span class="hidden lg:inline"> · usa ← → para cambiar de página</span>.
                </p>
            @endif

            {{-- Capítulo anterior / siguiente --}}
            <nav aria-label="Capítulos" class="mt-10 flex items-center justify-between gap-3 border-t border-line pt-6">
                @if ($previous)
                    <x-ui.button variant="secondary" href="{{ $previousUrl }}" rel="prev">
                        <x-ui.icon name="chevron-left" class="size-5" /> {{ $previous['book']['name'] }} {{ $previous['chapter'] }}
                    </x-ui.button>
                @else
                    <span></span>
                @endif
                @if ($next)
                    <x-ui.button variant="secondary" href="{{ $nextUrl }}" rel="next">
                        {{ $next['book']['name'] }} {{ $next['chapter'] }} <x-ui.icon name="chevron-right" class="size-5" />
                    </x-ui.button>
                @endif
            </nav>

            <div class="mt-10">
                @include('bible.partials.search-form')
            </div>
        </article>

        {{-- Flechas laterales fijas (escritorio): página anterior/siguiente y, en los extremos, capítulo --}}
        @if ($verses !== null)
            <button type="button" x-show="page > 1 || previousUrl" x-on:click="turn(-1)" :title="page > 1 ? 'Página anterior' : 'Capítulo anterior'"
                    class="fixed top-1/2 left-4 z-20 hidden size-12 items-center justify-center rounded-full bg-white text-primary shadow-card ring-1 ring-line hover:bg-cream lg:flex xl:left-10">
                <x-ui.icon name="chevron-left" /><span class="sr-only" x-text="page > 1 ? 'Página anterior' : 'Capítulo anterior'">Anterior</span>
            </button>
            <button type="button" x-show="page < pages || nextUrl" x-on:click="turn(1)" :title="page < pages ? 'Página siguiente' : 'Capítulo siguiente'"
                    class="fixed top-1/2 right-4 z-20 hidden size-12 items-center justify-center rounded-full bg-white text-primary shadow-card ring-1 ring-line hover:bg-cream lg:flex xl:right-10">
                <x-ui.icon name="chevron-right" /><span class="sr-only" x-text="page < pages ? 'Página siguiente' : 'Capítulo siguiente'">Siguiente</span>
            </button>
        @endif

        {{-- Barra de acciones al seleccionar versículos --}}
        <div x-show="selected.length" x-cloak x-transition
             class="fixed inset-x-0 bottom-4 z-40 mx-auto flex w-[calc(100%-2rem)] max-w-md items-center gap-2 rounded-full bg-primary-deep p-2 ps-5 text-white shadow-2xl"
             role="region" aria-label="Versículos seleccionados">
            <span class="flex-1 truncate text-sm font-semibold" x-text="label()"></span>
            <button type="button" x-on:click="copy()" class="inline-flex min-h-10 items-center rounded-full bg-white/10 px-4 text-sm font-semibold hover:bg-white/20">Copiar</button>
            <button type="button" x-on:click="share()" class="inline-flex min-h-10 items-center rounded-full bg-brand-strong px-4 text-sm font-semibold hover:bg-brand hover:text-primary">Compartir</button>
            <button type="button" x-on:click="selected = []" class="inline-flex size-10 items-center justify-center rounded-full hover:bg-white/10"><x-ui.icon name="close" class="size-5" /><span class="sr-only">Quitar selección</span></button>
        </div>
        <p x-show="notice" x-cloak x-transition.opacity x-text="notice" role="status"
           class="fixed inset-x-0 bottom-20 z-40 mx-auto w-fit rounded-full bg-stone-900 px-4 py-2 text-sm text-white shadow-lg"></p>
    </div>

    @include('bible.partials.scripts')
    <script>
        // Lector de la Biblia: selector (libro → capítulo → versículo), paginación,
        // versículo resaltado, tamaño de letra, selección y recientes.
        // Las preferencias quedan solo en este navegador (localStorage, con protección).
        function bibleReader({ books, current, chapterUrl, countUrl, version, highlight, perPage, page, previousUrl, nextUrl }) {
            // Copia con la API del portapapeles y, si no está disponible (sin HTTPS
            // o sin permiso), con el método clásico de un textarea temporal.
            async function copyText(text) {
                try { await navigator.clipboard.writeText(text); return true; } catch {}
                const area = Object.assign(document.createElement('textarea'), { value: text, readOnly: true });
                area.style.cssText = 'position:fixed;opacity:0;pointer-events:none';
                document.body.appendChild(area);
                area.select();
                let ok = false;
                try { ok = document.execCommand('copy'); } catch {}
                area.remove();
                return ok;
            }

            const store = {
                get(key, fallback) { try { return JSON.parse(localStorage.getItem(key)) ?? fallback; } catch { return fallback; } },
                set(key, value) { try { localStorage.setItem(key, JSON.stringify(value)); } catch {} },
            };

            return {
                ...passagePicker({ books, chapterUrl, countUrl, current }),
                current, version, highlight, perPage, page, previousUrl, nextUrl,
                pages: Math.max(1, Math.ceil(current.verses / perPage)),
                picker: false, settings: false,
                sizes: ['text-base', 'text-lg', 'text-xl', 'text-2xl', 'text-3xl'],
                size: store.get('biblia-tamano', 1),
                selected: [],
                notice: '',

                init() {
                    const recents = store.get('biblia-recientes', []).filter((r) => ! (r.slug === current.slug && r.chapter === current.chapter));
                    recents.unshift({ name: current.name, slug: current.slug, chapter: current.chapter, url: location.pathname });
                    store.set('biblia-recientes', recents.slice(0, 6));
                    // Al cargar con «?v=»: centrar el versículo resaltado.
                    if (highlight) this.$nextTick(() => this.reveal(highlight[0], 'auto'));
                },

                // ----- Paginación -----
                inPage(index) { return Math.floor(index / perPage) + 1 === this.page; },
                // Números reales del primer y último versículo de la página.
                rangeLabel() {
                    const nums = [...document.querySelectorAll('[data-index]')].filter((el) => this.inPage(+el.dataset.index)).map((el) => el.dataset.verse);
                    return nums.length ? `${nums[0]}–${nums[nums.length - 1]}` : '';
                },
                go(p, scroll = true) {
                    if (p < 1 || p > this.pages || p === this.page) return;
                    this.page = p;
                    if (scroll) this.$nextTick(() => {
                        const top = this.$refs.top.getBoundingClientRect().top;
                        if (top < 0 || top > innerHeight * 0.5) this.$refs.top.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    });
                },
                // Flechas: cambian de página y, en los extremos, de capítulo.
                turn(dir) {
                    if (dir < 0) this.page > 1 ? this.go(this.page - 1) : previousUrl && (location.href = previousUrl);
                    else this.page < this.pages ? this.go(this.page + 1) : nextUrl && (location.href = nextUrl);
                },
                keys(e) {
                    if (this.picker || e.altKey || e.ctrlKey || e.metaKey || /input|textarea|select/i.test(e.target.tagName)) return;
                    if (e.key === 'ArrowLeft') { e.preventDefault(); this.turn(-1); }
                    if (e.key === 'ArrowRight') { e.preventDefault(); this.turn(1); }
                },
                // Números de página compactos: 1 … 4 5 6 … 12
                pageItems() {
                    const all = Array.from({ length: this.pages }, (_, i) => i + 1);
                    const keep = all.filter((n) => n === 1 || n === this.pages || Math.abs(n - this.page) <= 1);
                    const items = [];
                    keep.forEach((n, i) => {
                        if (i && n - keep[i - 1] > 1) items.push({ gap: true, key: 'gap' + n });
                        items.push({ n, key: 'p' + n });
                    });
                    return items;
                },

                // ----- Versículo resaltado -----
                isFocused(n) { return !! this.highlight && n >= this.highlight[0] && n <= this.highlight[1]; },
                reveal(n, behavior = 'smooth') {
                    const el = document.getElementById('v' + n);
                    if (! el) return;
                    this.go(Math.floor(+el.dataset.index / perPage) + 1, false);
                    this.$nextTick(() => el.scrollIntoView({ behavior, block: 'center' }));
                },
                title() {
                    if (! this.highlight) return `${current.name} ${current.chapter}`;
                    const [a, b] = this.highlight;
                    return `${current.name} ${current.chapter}:${a === b ? a : a + '-' + b}`;
                },

                // ----- Selector (pasos en passagePicker) -----
                openPicker() {
                    this.picker = ! this.picker;
                    this.settings = false;
                    // Se abre en los capítulos del libro que se está leyendo.
                    if (this.picker) { this.book = books.find((b) => b.slug === current.slug); this.step = 'chapters'; }
                },
                isCurrent(n) { return this.book?.slug === current.slug && n === current.chapter; },
                isHighlighted(v) { return this.isCurrentChapter() && !! this.highlight && v >= this.highlight[0] && v <= this.highlight[1]; },
                // En el mismo capítulo no se recarga: se pasa a su página y se resalta.
                pickVerse(event, v) {
                    if (! this.isCurrentChapter()) return;
                    event.preventDefault();
                    this.highlight = [v, v];
                    this.picker = false;
                    history.replaceState(null, '', `${location.pathname}?v=${v}#v${v}`);
                    this.reveal(v);
                },

                // ----- Lectura y selección -----
                setSize(n) { this.size = Math.max(0, Math.min(4, n)); store.set('biblia-tamano', this.size); },
                toggle(n) {
                    this.selected = this.selected.includes(n) ? this.selected.filter((v) => v !== n) : [...this.selected, n].sort((a, b) => a - b);
                },
                label() {
                    const v = this.selected;
                    const range = v.length === 1 ? v[0] : (v[v.length - 1] - v[0] === v.length - 1 ? `${v[0]}-${v[v.length - 1]}` : v.join(','));
                    return `${current.name} ${current.chapter}:${range}`;
                },
                text() {
                    const verses = this.selected.map((n) => document.querySelector(`[data-verse="${n}"]`)?.dataset.text).join(' ');
                    return `«${verses}»\n— ${this.label()} (${version})`;
                },
                link() {
                    const v = this.selected;
                    const range = v[v.length - 1] - v[0] === v.length - 1 && v.length > 1 ? `${v[0]}-${v[v.length - 1]}` : v[0];
                    return `${location.origin}${location.pathname}?v=${range}#v${v[0]}`;
                },
                flash(message) { this.notice = message; setTimeout(() => this.notice = '', 2200); },
                async copy() {
                    this.flash(await copyText(this.text()) ? 'Versículo copiado' : 'No se pudo copiar');
                },
                async share() {
                    if (navigator.share) {
                        try { await navigator.share({ title: this.label(), text: this.text(), url: this.link() }); } catch {}
                        return;
                    }
                    this.flash(await copyText(`${this.text()}\n${this.link()}`) ? 'Enlace copiado para compartir' : 'No se pudo copiar');
                },
            };
        }
    </script>
</x-layouts.public>
