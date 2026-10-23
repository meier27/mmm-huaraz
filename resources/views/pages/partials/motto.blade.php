{{--
    Lema del año (Panel › Lemas): tarjeta de tamaño medio con la imagen oficial
    a un lado. Requiere $motto.
--}}
<article id="lema" class="mx-auto mt-12 max-w-5xl sm:mt-16 scroll-mt-28 overflow-hidden rounded-card bg-white/80 shadow-[0_24px_50px_-30px_rgb(57_49_45/0.45)] ring-1 ring-sand-dark sm:flex" aria-labelledby="lema-titulo">
    @if ($motto->imageUrl())
        <img src="{{ $motto->imageUrl() }}" alt="{{ $motto->image_alt ?? '' }}" width="320" height="350"
             class="h-52 w-full object-cover sm:h-auto sm:w-72 sm:shrink-0 lg:w-80" loading="lazy" decoding="async">
    @endif
    <div class="flex flex-col justify-center p-7 sm:p-10">
        <x-ui.section-label>Lema del año</x-ui.section-label>
        <h2 id="lema-titulo" class="mt-3 flex flex-wrap items-baseline gap-x-3 text-4xl sm:text-5xl">
            <span class="font-black text-brand-strong italic">{{ $motto->title }}</span>
            <span class="font-sans text-3xl font-light text-primary-soft sm:text-4xl">{{ $motto->year }}</span>
        </h2>
        @if ($motto->description)
            <p class="mt-4 line-clamp-4 leading-relaxed text-muted">{{ \App\Support\Markdown::plain($motto->description, 600) }}</p>
        @endif
        @if ($motto->verse_text)
            <p class="mt-5 border-t border-line pt-4 font-display text-lg font-semibold text-brand-strong italic">
                «{{ $motto->verse_text }}»@if ($motto->verse_reference) <span class="font-sans text-base font-semibold not-italic text-primary-soft">· {{ $motto->verse_reference }}</span>@endif
            </p>
        @endif
    </div>
</article>
