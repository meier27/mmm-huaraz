{{--
    Bloques de «Nosotros» (Panel › Página «Nosotros»): foto con una tarjeta de
    texto montada encima, alternando el lado; y citas con autor.
    - Con «marco polaroid»: la foto se recorta y se enmarca en blanco, inclinada.
    - Sin marco: la foto se muestra tal cual (PNG con su propio marco o recorte).
    Requiere $blocks (no vacío).
--}}
@php
    $petal = 'M12 2c1.7 0 3 1.8 3 4.2 0 1.5-.6 2.8-1.4 3.6.8-.8 2.1-1.4 3.6-1.4C19.6 8.4 21.4 9.7 21.4 11.4S19.6 14.4 17.2 14.4c-1.5 0-2.8-.6-3.6-1.4.8.8 1.4 2.1 1.4 3.6 0 2.4-1.3 4.2-3 4.2s-3-1.8-3-4.2c0-1.5.6-2.8 1.4-3.6-.8.8-2.1 1.4-3.6 1.4C4.4 14.4 2.6 13.1 2.6 11.4S4.4 8.4 6.8 8.4c1.5 0 2.8.6 3.6 1.4C9.6 9 9 7.7 9 6.2 9 3.8 10.3 2 12 2z';
    $side = 0;
@endphp

@foreach ($blocks as $block)
    @if ($block->type === \App\Enums\AboutBlockType::Quote)
        {{-- Cita con autor --}}
        <figure @class(['relative', 'pt-10 md:pt-28' => $block->imageUrl() && ! $block->photo_frame])>
            <div @class(['relative rounded-card bg-linear-to-r from-primary-deep via-primary to-gradient-end text-cream shadow-[0_30px_60px_-30px_rgb(57_49_45/0.7)]',
                         'md:grid md:grid-cols-[minmax(0,2fr)_minmax(0,3fr)] md:items-end' => $block->imageUrl(),
                         'overflow-hidden md:overflow-visible' => $block->imageUrl() && $block->photo_frame])>
                {{-- Puntos decorativos en las esquinas (detrás de la tarjeta) --}}
                <span class="absolute -top-6 -left-6 -z-10 hidden size-28 bg-[radial-gradient(var(--color-accent-light)_2px,transparent_2px)] bg-size-[14px_14px] opacity-70 sm:block" aria-hidden="true"></span>
                <span class="absolute -right-6 -bottom-6 -z-10 hidden size-28 bg-[radial-gradient(var(--color-accent-light)_2px,transparent_2px)] bg-size-[14px_14px] opacity-70 sm:block" aria-hidden="true"></span>
                @if ($block->imageUrl())
                    @if ($block->photo_frame)
                        {{-- Foto normal: ocupa todo el alto y sobresale por arriba. --}}
                        <div class="md:-mt-12 md:self-stretch">
                            <img src="{{ $block->imageUrl() }}" alt="{{ $block->image_alt ?? '' }}" width="560" height="560"
                                 class="aspect-4/3 w-full object-cover object-top md:aspect-auto md:h-full md:min-h-80 md:rounded-l-card" loading="lazy" decoding="async">
                        </div>
                    @else
                        {{-- Recorte transparente: sale por encima de la tarjeta, apoyado abajo. --}}
                        <div class="-mt-10 px-6 md:-mt-40 md:-ms-4 md:px-0">
                            <img src="{{ $block->imageUrl() }}" alt="{{ $block->image_alt ?? '' }}" width="600" height="380"
                                 class="mx-auto h-auto w-full max-w-md object-contain object-bottom md:w-[112%] md:max-w-none" loading="lazy" decoding="async">
                        </div>
                    @endif
                @endif
                <div class="relative p-7 sm:p-10">
                    <svg viewBox="0 0 24 24" class="absolute top-6 right-6 size-6 text-accent-light" aria-hidden="true"><path fill="currentColor" d="{{ $petal }}" /></svg>
                    <blockquote class="max-w-2xl text-base leading-relaxed text-cream/85 sm:text-lg [&_strong]:font-bold [&_strong]:text-accent-light [&>*+*]:mt-4">
                        {{ \App\Support\Markdown::render($block->body) }}
                    </blockquote>
                    @if ($block->author_name)
                        <figcaption class="mt-6">
                            <span class="block font-display text-2xl font-bold text-accent-light italic sm:text-3xl">{{ $block->author_name }}</span>
                            @if ($block->author_role)
                                <span class="mt-1 block text-xs font-semibold tracking-[0.14em] text-cream/75 uppercase">{{ $block->author_role }}</span>
                            @endif
                        </figcaption>
                    @endif
                </div>
            </div>
        </figure>
    @else
        @php
            $highlight = $block->type === \App\Enums\AboutBlockType::Highlight;
            $photoRight = $side++ % 2 === 1;
        @endphp
        <article @class(['grid items-center gap-6 md:grid-cols-12 md:gap-0', 'md:max-w-4xl md:mx-auto md:block' => ! $block->imageUrl()])>
            @if ($block->imageUrl())
                @if ($block->photo_frame)
                    <figure @class(['relative mx-auto w-11/12 bg-white p-3 pb-10 shadow-[0_28px_50px_-24px_rgb(57_49_45/0.55)] sm:p-4 sm:pb-14 md:col-span-6 md:row-start-1',
                                    'md:col-start-7 md:rotate-[3deg]' => $photoRight, 'md:col-start-1 md:-rotate-[3deg]' => ! $photoRight])>
                        <img src="{{ $block->imageUrl() }}" alt="{{ $block->image_alt ?? '' }}" width="700" height="525"
                             class="aspect-4/3 w-full object-cover" loading="lazy" decoding="async">
                    </figure>
                @else
                    {{-- La imagen ya trae su marco: se muestra tal cual, con sombra que sigue su forma. --}}
                    <figure @class(['relative md:row-start-1', 'md:col-span-7 md:col-start-6' => $photoRight, 'md:col-span-7 md:col-start-1' => ! $photoRight])>
                        <img src="{{ $block->imageUrl() }}" alt="{{ $block->image_alt ?? '' }}" width="760" height="600"
                             class="h-auto w-full drop-shadow-[0_24px_28px_rgb(57_49_45/0.35)]" loading="lazy" decoding="async">
                    </figure>
                @endif
            @endif

            <div @class(['relative z-10 rounded-card p-7 shadow-[0_20px_45px_-25px_rgb(57_49_45/0.5)] sm:p-9 md:row-start-1',
                         'md:col-span-6 md:col-start-7 md:-ms-6' => $block->imageUrl() && ! $photoRight,
                         'md:col-span-6 md:col-start-1 md:-me-6' => $block->imageUrl() && $photoRight,
                         'bg-white text-ink' => ! $highlight,
                         'bg-linear-to-br from-brand-strong via-highlight-via to-highlight-end text-white' => $highlight])>
                <div class="absolute top-6 right-6 flex flex-col items-center gap-2" aria-hidden="true">
                    <svg viewBox="0 0 24 24" @class(['size-6', 'text-accent-light' => ! $highlight, 'text-white' => $highlight])><path fill="currentColor" d="{{ $petal }}" /></svg>
                    <span @class(['h-8 w-2 rounded-full', 'bg-primary-soft' => ! $highlight, 'bg-white' => $highlight])></span>
                    <span @class(['h-6 w-px', 'bg-primary-soft/50' => ! $highlight, 'bg-white/60' => $highlight])></span>
                </div>
                <div @class(['max-w-prose pe-10 leading-relaxed sm:text-[1.05rem] [&>*+*]:mt-4', '[&_strong]:text-primary' => ! $highlight, '[&_strong]:text-white [&_a]:text-white [&_a]:underline' => $highlight])>
                    {{ \App\Support\Markdown::render($block->body) }}
                </div>
            </div>
        </article>
    @endif
@endforeach
