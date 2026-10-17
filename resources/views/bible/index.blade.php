<x-layouts.public title="Biblia" description="Lee la Biblia en línea: los 66 libros capítulo por capítulo, versículo del día y búsqueda por palabras.">
    {{-- ===== Portada (como bible.com) ===== --}}
    <section class="relative bg-primary-deep pt-16 pb-28 text-white sm:pt-24 sm:pb-36" aria-labelledby="biblia-titulo">
        <x-ui.curve position="bottom" class="text-cream" />
        <div class="container-content">
            <h1 id="biblia-titulo" class="max-w-3xl font-display text-5xl leading-tight font-bold text-white sm:text-7xl">
                La Santa <span class="text-brand">Biblia</span>.
                <span class="block text-cream/90 italic">para leer cada día.</span>
            </h1>
            <p class="mt-6 max-w-xl text-lg text-cream/80">{{ $versionName }} · Los 66 libros, capítulo por capítulo.</p>

            {{-- Tarjetas: leer y versículo del día --}}
            <div class="mt-12 grid gap-5 lg:grid-cols-2"
                 x-data="{ last: (() => { try { return (JSON.parse(localStorage.getItem('biblia-recientes')) ?? [])[0] ?? null; } catch { return null; } })() }">
                <div class="flex flex-col rounded-card bg-white/5 p-7 ring-1 ring-white/10 sm:p-9">
                    <h2 class="font-sans text-2xl font-bold text-white sm:text-3xl">Lee la Biblia en línea</h2>
                    <p class="mt-2 text-cream/80">Elige un libro o continúa donde lo dejaste.</p>
                    <div class="mt-auto flex flex-wrap gap-3 pt-8">
                        <a href="{{ route('bible.chapter', ['genesis', 1]) }}" x-show="! last" class="inline-flex min-h-12 items-center rounded-full bg-white px-6 font-bold text-primary transition hover:bg-brand">
                            Iniciar lectura
                        </a>
                        <template x-if="last">
                            <a :href="last.url" class="inline-flex min-h-12 items-center rounded-full bg-white px-6 font-bold text-primary transition hover:bg-brand">
                                Continuar: <span class="ms-1" x-text="`${last.name} ${last.chapter}`"></span>
                            </a>
                        </template>
                        <a href="#libros" class="inline-flex min-h-12 items-center rounded-full border border-white/40 px-6 font-bold text-white transition hover:bg-white/10">
                            Ver libros
                        </a>
                    </div>
                </div>

                <div class="flex flex-col rounded-card bg-white/5 p-7 ring-1 ring-white/10 sm:p-9">
                    <h2 class="font-sans text-2xl font-bold text-white sm:text-3xl">Versículo del día</h2>
                    @if ($verse)
                        <blockquote class="mt-4 font-display text-xl leading-relaxed text-cream">«{{ $verse->text }}»</blockquote>
                        <p class="mt-3 text-sm font-semibold text-brand">{{ $verse->reference }}@if ($verse->version) · {{ $verse->version }}@endif</p>
                        @if ($verseLocation)
                            <div class="mt-auto pt-8">
                                <a href="{{ route('bible.chapter', [$verseLocation['book']['slug'], $verseLocation['chapter']]) }}@if ($verseLocation['verse'])?v={{ $verseLocation['verse'] }}#v{{ $verseLocation['verse'] }}@endif"
                                   class="inline-flex min-h-12 items-center rounded-full bg-white px-6 font-bold text-primary transition hover:bg-brand">
                                    Leer ahora
                                </a>
                            </div>
                        @endif
                    @else
                        <p class="mt-4 text-cream/80">El versículo del día se publicará aquí.</p>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <div>
        <div class="container-content pt-6 pb-16">
            {{-- Recientes (solo en este navegador) --}}
            <section x-data="{ recents: (() => { try { return JSON.parse(localStorage.getItem('biblia-recientes')) ?? []; } catch { return []; } })() }"
                     x-show="recents.length" x-cloak aria-labelledby="recientes" class="mb-12">
                <h2 id="recientes" class="text-sm font-semibold tracking-[0.2em] text-brand-strong uppercase">Recientes</h2>
                <ul class="mt-3 flex flex-wrap gap-2">
                    <template x-for="r in recents" :key="r.url">
                        <li>
                            <a :href="r.url" class="inline-flex min-h-10 items-center gap-2 rounded-full bg-white px-4 text-sm font-semibold text-primary shadow-sm ring-1 ring-line hover:bg-white/60">
                                <x-ui.icon name="book" class="size-4 text-brand-strong" /> <span x-text="`${r.name} ${r.chapter}`"></span>
                            </a>
                        </li>
                    </template>
                </ul>
            </section>

            <div class="max-w-2xl">
                <h2 class="font-display text-2xl font-bold text-primary">Buscar en la Biblia</h2>
                <div class="mt-4">@include('bible.partials.search-form')</div>
            </div>

            {{-- Libros --}}
            <div id="libros" class="mt-14 scroll-mt-28 space-y-12">
                @foreach (['Antiguo Testamento' => $oldTestament, 'Nuevo Testamento' => $newTestament] as $label => $books)
                    <section aria-labelledby="{{ Str::slug($label) }}">
                        <h2 id="{{ Str::slug($label) }}" class="inline-flex items-center gap-3 text-2xl font-bold text-primary">
                            <span class="h-0.5 w-8 bg-brand" aria-hidden="true"></span>{{ $label }}
                        </h2>
                        <ul class="mt-5 grid grid-cols-2 gap-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                            @foreach ($books as $book)
                                <li>
                                    <a href="{{ route('bible.chapter', $book['slug']) }}" class="flex min-h-12 items-center justify-between gap-2 rounded-control bg-white px-4 py-2 text-sm font-semibold text-ink shadow-sm ring-1 ring-black/5 transition hover:text-brand-strong hover:ring-brand">
                                        {{ $book['name'] }} <span class="text-xs font-normal text-muted">{{ $book['chapters'] }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endforeach
            </div>

            <p class="mt-12 text-xs text-muted">Texto bíblico: {{ $versionName }} ({{ $versionShort }}){{ $publicDomain ? ', de dominio público,' : '' }} obtenido de esbiblia.net.</p>
        </div>
    </div>
</x-layouts.public>
