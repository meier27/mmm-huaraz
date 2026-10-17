<x-layouts.public :title="$book['name'].' '.$chapter" :description="$book['name'].' capítulo '.$chapter.' ('.$versionName.').'">
    @php
        $reference = $book['name'].' '.$chapter;
    @endphp

    <div x-data="bibleReader({
            books: @js($books),
            current: @js(['name' => $book['name'], 'slug' => $book['slug'], 'chapter' => $chapter, 'testament' => $book['testament']]),
            chapterUrl: @js(route('bible.chapter', ['__LIBRO__', 999])),
            version: @js($versionShort),
            highlight: {{ $highlight ?: 'null' }},
         })"
         x-on:keydown.escape.window="picker = false; settings = false">

        {{-- ===== Barra del lector (como bible.com): libro/capítulo, versión, tamaño ===== --}}
        <div class="sticky top-20 z-30 border-b border-line bg-white/95 backdrop-blur">
            <div class="container-content flex h-14 items-center justify-between gap-2">
                <a href="{{ route('bible.index') }}" class="inline-flex size-10 items-center justify-center rounded-full text-primary hover:bg-cream" title="Inicio de la Biblia">
                    <x-ui.icon name="book" class="size-5" /><span class="sr-only">Inicio de la Biblia</span>
                </a>

                <div class="flex items-center gap-2">
                    <button type="button" x-on:click="picker = ! picker; settings = false; step = 'books'" :aria-expanded="picker.toString()" aria-expanded="false" aria-controls="selector-pasaje"
                            class="inline-flex min-h-10 items-center gap-2 rounded-full bg-stone-100 px-4 font-display text-sm font-bold text-primary uppercase hover:bg-cream">
                        {{ $reference }} <x-ui.icon name="chevron-right" class="size-4 rotate-90" />
                    </button>
                    <span class="inline-flex min-h-10 items-center rounded-full border border-line px-3 text-xs font-bold text-primary" title="{{ $versionName }}">{{ $versionShort }}</span>
                </div>

                <button type="button" x-on:click="settings = ! settings; picker = false" :aria-expanded="settings.toString()" aria-expanded="false" aria-controls="ajustes-lectura"
                        class="inline-flex size-10 items-center justify-center rounded-full font-display text-sm font-bold text-primary hover:bg-cream" title="Tamaño del texto">
                    Aa<span class="sr-only"> · tamaño del texto</span>
                </button>
            </div>

            {{-- Selector de libro y capítulo --}}
            <div id="selector-pasaje" x-show="picker" x-cloak x-transition.opacity x-on:click.outside="picker = false"
                 class="absolute inset-x-0 top-full max-h-[70dvh] overflow-y-auto border-b border-line bg-white shadow-xl">
                <div class="container-content py-5">
                    <template x-if="step === 'books'">
                        <div>
                            <div class="flex gap-2" role="tablist">
                                <template x-for="(label, key) in { AT: 'Antiguo Testamento', NT: 'Nuevo Testamento' }" :key="key">
                                    <button type="button" role="tab" x-on:click="testament = key" :aria-selected="(testament === key).toString()"
                                            class="rounded-full px-4 py-2 text-sm font-semibold" :class="testament === key ? 'bg-primary text-white' : 'bg-stone-100 text-primary hover:bg-cream'" x-text="label"></button>
                                </template>
                            </div>
                            <ul class="mt-4 grid grid-cols-2 gap-1 sm:grid-cols-3 lg:grid-cols-5">
                                <template x-for="b in books.filter(b => b.testament === testament)" :key="b.slug">
                                    <li>
                                        <button type="button" x-on:click="chooseBook(b)"
                                                class="flex min-h-10 w-full items-center rounded-control px-3 text-left text-sm hover:bg-cream"
                                                :class="b.slug === current.slug ? 'font-bold text-brand-strong' : 'text-ink'" x-text="b.name"></button>
                                    </li>
                                </template>
                            </ul>
                        </div>
                    </template>
                    <template x-if="step === 'chapters'">
                        <div>
                            <button type="button" x-on:click="step = 'books'" class="inline-flex items-center gap-1 text-sm font-semibold text-brand-strong hover:underline">
                                <x-ui.icon name="chevron-left" class="size-4" /> Libros
                            </button>
                            <p class="mt-2 font-display text-xl font-bold text-primary" x-text="book.name"></p>
                            <ul class="mt-3 grid grid-cols-6 gap-1.5 sm:grid-cols-10 lg:grid-cols-15">
                                <template x-for="n in book.chapters" :key="n">
                                    <li>
                                        <a :href="url(book.slug, n)" class="flex size-11 items-center justify-center rounded-control text-sm font-semibold"
                                           :class="book.slug === current.slug && n === current.chapter ? 'bg-brand-strong text-white' : 'bg-stone-100 hover:bg-brand/20'" x-text="n"></a>
                                    </li>
                                </template>
                            </ul>
                        </div>
                    </template>
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

        {{-- ===== Texto ===== --}}
        <article class="container-content max-w-2xl! py-12">
            <h1 class="text-center font-display text-3xl font-bold tracking-wide text-primary uppercase sm:text-4xl">{{ $reference }}</h1>
            <p class="mt-2 text-center text-sm text-muted">{{ $versionName }}</p>

            @if ($verses === null)
                <x-ui.alert type="error" class="mt-10">No pudimos cargar este capítulo en este momento. Inténtalo de nuevo en unos minutos.</x-ui.alert>
            @else
                <p class="mt-10 font-display leading-[2.1] text-ink" :class="sizes[size]">
                    @foreach ($verses as $verse)
                        <span id="v{{ $verse['verse'] }}" data-verse="{{ $verse['verse'] }}" data-text="{{ $verse['text'] }}"
                              role="button" tabindex="0"
                              x-on:click="toggle({{ $verse['verse'] }})" x-on:keydown.enter.prevent="toggle({{ $verse['verse'] }})" x-on:keydown.space.prevent="toggle({{ $verse['verse'] }})"
                              :aria-pressed="selected.includes({{ $verse['verse'] }}).toString()"
                              class="scroll-mt-40 cursor-pointer rounded-sm decoration-brand decoration-2 underline-offset-[6px] transition-colors hover:bg-cream focus-visible:bg-cream"
                              :class="selected.includes({{ $verse['verse'] }}) && 'bg-brand/20 underline decoration-dotted'"><sup class="me-0.5 font-sans text-[0.65em] font-bold text-brand-strong">{{ $verse['verse'] }}</sup>{{ $verse['text'] }} </span>
                    @endforeach
                </p>
                <p class="mt-8 text-center text-xs text-muted">Toca un versículo para copiarlo o compartirlo.</p>
            @endif

            {{-- Navegación inferior (y la única en móvil) --}}
            <nav aria-label="Capítulos" class="mt-12 flex items-center justify-between gap-3 border-t border-line pt-6">
                @if ($previous)
                    <x-ui.button variant="secondary" href="{{ route('bible.chapter', [$previous['book']['slug'], $previous['chapter']]) }}" rel="prev">
                        <x-ui.icon name="chevron-left" class="size-5" /> {{ $previous['book']['name'] }} {{ $previous['chapter'] }}
                    </x-ui.button>
                @else
                    <span></span>
                @endif
                @if ($next)
                    <x-ui.button variant="secondary" href="{{ route('bible.chapter', [$next['book']['slug'], $next['chapter']]) }}" rel="next">
                        {{ $next['book']['name'] }} {{ $next['chapter'] }} <x-ui.icon name="chevron-right" class="size-5" />
                    </x-ui.button>
                @endif
            </nav>

            <div class="mt-10">
                @include('bible.partials.search-form')
            </div>
        </article>

        {{-- Flechas laterales fijas (escritorio), como en bible.com --}}
        @if ($previous)
            <a href="{{ route('bible.chapter', [$previous['book']['slug'], $previous['chapter']]) }}" title="{{ $previous['book']['name'] }} {{ $previous['chapter'] }}"
               class="fixed top-1/2 left-4 z-20 hidden size-12 items-center justify-center rounded-full bg-white text-primary shadow-card ring-1 ring-line hover:bg-cream lg:flex xl:left-10">
                <x-ui.icon name="chevron-left" /><span class="sr-only">Capítulo anterior</span>
            </a>
        @endif
        @if ($next)
            <a href="{{ route('bible.chapter', [$next['book']['slug'], $next['chapter']]) }}" title="{{ $next['book']['name'] }} {{ $next['chapter'] }}"
               class="fixed top-1/2 right-4 z-20 hidden size-12 items-center justify-center rounded-full bg-white text-primary shadow-card ring-1 ring-line hover:bg-cream lg:flex xl:right-10">
                <x-ui.icon name="chevron-right" /><span class="sr-only">Capítulo siguiente</span>
            </a>
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

    <script>
        // Lector de la Biblia: selector, tamaño de letra, selección y recientes.
        // Las preferencias quedan solo en este navegador (localStorage, con protección).
        function bibleReader({ books, current, chapterUrl, version, highlight }) {
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
                books, current, version,
                picker: false, settings: false, step: 'books',
                testament: current.testament, book: null,
                sizes: ['text-base', 'text-lg', 'text-xl', 'text-2xl', 'text-3xl'],
                size: store.get('biblia-tamano', 1),
                selected: highlight ? [highlight] : [],
                notice: '',

                init() {
                    const recents = store.get('biblia-recientes', []).filter((r) => ! (r.slug === current.slug && r.chapter === current.chapter));
                    recents.unshift({ name: current.name, slug: current.slug, chapter: current.chapter, url: location.pathname });
                    store.set('biblia-recientes', recents.slice(0, 6));
                    if (highlight) this.$nextTick(() => document.getElementById('v' + highlight)?.scrollIntoView({ block: 'center' }));
                },
                url(slug, n) { return chapterUrl.replace('__LIBRO__', slug).replace(/999$/, n); },
                chooseBook(b) { this.book = b; this.step = 'chapters'; },
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
                link() { return `${location.origin}${location.pathname}?v=${this.selected[0]}#v${this.selected[0]}`; },
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
