<x-layouts.admin title="Doctrinario">
    <x-admin.page-header title="Doctrinario" description="El libro de /doctrina en el lector propio del sitio: sin anuncios y sin publicar el PDF. Cada página se convierte en imagen con una marca de agua discreta." />

    @if ($errors->any())
        <x-ui.alert type="error" class="mb-6">{{ $errors->first() }}</x-ui.alert>
    @endif

    @php
        $field = 'mt-1 block min-h-11 w-full rounded-control border border-stone-300 bg-white px-3 py-2 focus:border-brand-strong';
    @endphp

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_24rem] xl:items-start">
        <div class="space-y-6">
            @if ($book)
                {{-- Libro actual --}}
                <section class="rounded-card bg-white p-5 shadow-card sm:p-6" aria-labelledby="libro-actual">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 id="libro-actual" class="text-xl font-bold">{{ $book->title }}</h2>
                            <p class="text-sm text-muted">{{ $book->author ?: 'Sin autor' }} · {{ $book->page_count }} páginas</p>
                        </div>
                        @php
                            [$tone, $label] = match (true) {
                                $book->status === \App\Models\DoctrineBook::FAILED => ['bg-red-100 text-danger', 'Error al procesar'],
                                ! $book->isReady() => ['bg-amber-100 text-warning', 'Procesando'],
                                $book->is_published => ['bg-green-100 text-success', 'Publicado en /doctrina'],
                                default => ['bg-stone-100 text-muted', 'Listo, sin publicar'],
                            };
                        @endphp
                        <span class="rounded-full px-3 py-1 text-sm font-semibold {{ $tone }}">{{ $label }}</span>
                    </div>

                    @if ($book->error)
                        <x-ui.alert type="error" class="mt-4">{{ $book->error }}</x-ui.alert>
                    @endif

                    @if ($preview->isNotEmpty())
                        <ul class="mt-5 grid grid-cols-3 gap-3 sm:grid-cols-6">
                            @foreach ($preview as $n)
                                <li class="overflow-hidden rounded-control bg-stone-100 ring-1 ring-line">
                                    <img src="{{ URL::temporarySignedRoute('doctrine.page', now()->addMinutes(30), [$book, $n]) }}" alt="Página {{ $n }}" loading="lazy"
                                         style="aspect-ratio: {{ $book->page_width }} / {{ $book->page_height }}" class="w-full object-contain" draggable="false">
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    <form method="POST" action="{{ route('admin.doctrine.update', $book) }}" class="mt-6 grid gap-4 border-t border-line pt-5 sm:grid-cols-2">
                        @csrf @method('PUT')
                        <div>
                            <label for="edit-title" class="block text-sm font-medium">Título</label>
                            <input id="edit-title" name="title" value="{{ old('title', $book->title) }}" required maxlength="200" class="{{ $field }}">
                        </div>
                        <div>
                            <label for="edit-author" class="block text-sm font-medium">Autor (opcional)</label>
                            <input id="edit-author" name="author" value="{{ old('author', $book->author) }}" maxlength="150" class="{{ $field }}">
                        </div>
                        <div class="sm:col-span-2">
                            <div class="flex flex-wrap items-end justify-between gap-2">
                                <label for="edit-toc" class="block text-sm font-medium">Índice <span class="font-normal text-muted">· una línea por entrada: «página | título»</span></label>
                                <span class="text-xs text-muted">{{ count($book->index()) }} entradas · el libro tiene {{ $book->page_count }} páginas</span>
                            </div>
                            <textarea id="edit-toc" name="toc" rows="10" spellcheck="false"
                                      class="{{ $field }} font-mono text-sm leading-relaxed">{{ old('toc', collect($book->index())->map(fn ($e) => $e['page'].' | '.$e['title'])->implode("\n")) }}</textarea>
                            <p class="mt-1 text-xs text-muted">Se detecta solo al subir el PDF (busca «LECCIÓN…» y los títulos de la página «CONTENIDO»). Corrígelo aquí si algo no quedó bien; la página es la del visor (la portada es la 1).</p>
                        </div>
                        <label class="inline-flex items-center gap-2 sm:col-span-2">
                            <input type="hidden" name="is_published" value="0">
                            <input type="checkbox" name="is_published" value="1" @checked($book->is_published) @disabled(! $book->isReady()) class="size-5 rounded border-stone-300">
                            <span>Publicar en /doctrina</span>
                        </label>
                        <div class="flex flex-wrap gap-3 sm:col-span-2">
                            <x-ui.button type="submit">Guardar</x-ui.button>
                            @if ($book->is_published)
                                <x-ui.button href="{{ route('doctrine') }}" variant="secondary" target="_blank" rel="noopener">Ver en la web<span class="sr-only"> (pestaña nueva)</span></x-ui.button>
                            @endif
                        </div>
                    </form>

                    <div class="mt-5 flex flex-wrap gap-3 border-t border-line pt-5">
                        <form method="POST" action="{{ route('admin.doctrine.reprocess', $book) }}">
                            @csrf
                            <x-ui.button type="submit" variant="secondary">Volver a generar las páginas</x-ui.button>
                        </form>
                        <form method="POST" action="{{ route('admin.doctrine.index', $book) }}" onsubmit="return confirm('¿Detectar el índice de nuevo? Se reemplazará el índice actual, incluidas tus correcciones.')">
                            @csrf
                            <x-ui.button type="submit" variant="secondary">Detectar el índice de nuevo</x-ui.button>
                        </form>
                        <form method="POST" action="{{ route('admin.doctrine.destroy', $book) }}" onsubmit="return confirm('¿Eliminar el Doctrinario y sus páginas? /doctrina volverá a mostrar FlipHTML5.')">
                            @csrf @method('DELETE')
                            <x-ui.button type="submit" variant="secondary" class="text-danger!">Eliminar</x-ui.button>
                        </form>
                    </div>
                </section>
            @endif

            {{-- Subir o reemplazar el PDF --}}
            <section class="rounded-card bg-white p-5 shadow-card sm:p-6" aria-labelledby="subir-pdf">
                <h2 id="subir-pdf" class="text-xl font-bold">{{ $book ? 'Reemplazar el PDF' : 'Subir el Doctrinario' }}</h2>
                <p class="mt-1 text-sm text-muted">PDF de hasta 40 MB. Convertir un libro de unas 70 páginas toma alrededor de un minuto: no cierres la página.</p>

                <form method="POST" action="{{ route('admin.doctrine.store') }}" enctype="multipart/form-data" class="mt-5 grid gap-4 sm:grid-cols-2"
                      x-data="{ sending: false }" x-on:submit="sending = true">
                    @csrf
                    <div>
                        <label for="title" class="block text-sm font-medium">Título</label>
                        <input id="title" name="title" value="{{ old('title', $book?->title ?? 'Instrucciones Bíblicas para los Recién Convertidos') }}" required maxlength="200" class="{{ $field }}">
                    </div>
                    <div>
                        <label for="author" class="block text-sm font-medium">Autor (opcional)</label>
                        <input id="author" name="author" value="{{ old('author', $book?->author ?? 'Luis M. Ortiz') }}" maxlength="150" class="{{ $field }}">
                    </div>
                    <div class="sm:col-span-2">
                        <label for="pdf" class="block text-sm font-medium">Archivo PDF</label>
                        <input id="pdf" name="pdf" type="file" accept="application/pdf" required
                               class="mt-1 block w-full rounded-control border border-dashed border-sand-dark bg-cream-light p-3 text-sm file:me-3 file:rounded-full file:border-0 file:bg-primary-deep file:px-4 file:py-2 file:font-semibold file:text-white">
                    </div>
                    <div class="sm:col-span-2">
                        <x-ui.button type="submit" x-bind:disabled="sending">
                            <span x-show="! sending">Subir y procesar</span>
                            <span x-show="sending" x-cloak>Procesando… (puede tardar un minuto)</span>
                        </x-ui.button>
                    </div>
                </form>
            </section>
        </div>

        <aside class="rounded-card bg-cream p-5 text-sm ring-1 ring-line" aria-labelledby="como-protege">
            <h2 id="como-protege" class="font-sans text-base font-bold text-primary">Cómo se protege</h2>
            <ul class="mt-3 space-y-2 text-ink/80">
                <li>• El PDF queda en el almacenamiento privado: nunca se publica.</li>
                <li>• Las páginas se muestran con enlaces que caducan a las 3 horas.</li>
                <li>• Otros sitios no pueden insertarlas ni enlazarlas: el servidor y el navegador las bloquean.</li>
                <li>• Cada página lleva una marca de agua discreta con el nombre del sitio.</li>
                <li>• Nada que se ve en pantalla es imposible de copiar (capturas): esto evita el uso fácil, no el 100 %.</li>
            </ul>
        </aside>
    </div>
</x-layouts.admin>
