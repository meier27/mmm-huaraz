{{-- Tarjeta de enseñanza. Requiere $teaching; opcional $featured (versión grande y horizontal). --}}
@php
    $featured ??= false;
    $image = $teaching->imageUrl() ?: ($teaching->video_id ? "https://i.ytimg.com/vi/{$teaching->video_id}/hqdefault.jpg" : null);
@endphp
<article @class(['group relative flex h-full overflow-hidden rounded-card bg-white shadow-[0_18px_40px_-28px_rgb(57_49_45/0.55)] ring-1 ring-black/5 transition duration-300 hover:-translate-y-1 hover:shadow-[0_28px_50px_-26px_rgb(57_49_45/0.6)]',
                 'flex-col lg:flex-row' => $featured, 'flex-col' => ! $featured])>
    <div @class(['relative overflow-hidden', 'aspect-video lg:aspect-auto lg:w-3/5 lg:shrink-0' => $featured, 'aspect-video' => ! $featured])>
        @if ($image)
            <img src="{{ $image }}" alt="{{ $teaching->image_alt ?? '' }}" width="640" height="360"
                 class="size-full object-cover transition duration-700 ease-out-soft group-hover:scale-105" loading="lazy" decoding="async">
        @else
            <div class="flex size-full items-center justify-center bg-linear-to-br from-primary to-primary-deep text-brand"><x-ui.icon name="book" class="size-12" /></div>
        @endif
        <div class="absolute inset-0 bg-linear-to-t from-black/45 via-transparent to-transparent" aria-hidden="true"></div>
        <span class="absolute top-4 left-4 inline-flex items-center gap-1.5 rounded-full bg-white/95 px-3 py-1 text-xs font-bold tracking-wide text-brand-strong uppercase shadow-sm">
            {{ $teaching->category->label() }}
        </span>
        @if ($teaching->video_id)
            <span class="absolute right-4 bottom-4 inline-flex size-11 items-center justify-center rounded-full bg-white/95 text-brand-strong shadow-lg transition group-hover:scale-110" aria-hidden="true">
                <x-ui.icon name="play" class="size-5" />
            </span>
        @endif
    </div>

    <div @class(['flex flex-1 flex-col', 'p-6 sm:p-8 lg:justify-center' => $featured, 'p-6' => ! $featured])>
        @if ($featured)
            <p class="text-xs font-bold tracking-[0.16em] text-primary-soft uppercase">Lo más reciente</p>
        @endif
        <h3 @class(['font-bold leading-snug', 'mt-2 text-2xl sm:text-3xl' => $featured, 'text-xl' => ! $featured])>
            <a href="{{ route('teachings.show', $teaching->slug) }}" class="transition after:absolute after:inset-0 group-hover:text-brand-strong">{{ $teaching->title }}</a>
        </h3>
        <p @class(['mt-3 text-muted', 'line-clamp-4 leading-relaxed' => $featured, 'line-clamp-3 text-sm' => ! $featured])>{{ $teaching->summary() }}</p>

        <div @class(['mt-auto flex items-center justify-between gap-3 border-t border-line pt-4 text-xs text-muted', 'lg:mt-6' => $featured])>
            <span class="flex min-w-0 flex-wrap items-center gap-x-3 gap-y-1">
                <span class="inline-flex items-center gap-1"><x-ui.icon name="calendar" class="size-3.5 text-brand-strong" />
                    <time datetime="{{ $teaching->published_on->format('Y-m-d') }}">{{ $teaching->published_on->translatedFormat('j M Y') }}</time></span>
                @if ($teaching->author_name)
                    <span class="inline-flex items-center gap-1"><x-ui.icon name="user" class="size-3.5 text-brand-strong" /> {{ $teaching->author_name }}</span>
                @endif
            </span>
            <span class="inline-flex shrink-0 items-center gap-1 font-bold text-brand-strong" aria-hidden="true">
                {{ $teaching->video_id ? 'Ver' : 'Leer' }} <x-ui.icon name="arrow-right" class="size-3.5 transition group-hover:translate-x-1" />
            </span>
        </div>
    </div>
</article>
