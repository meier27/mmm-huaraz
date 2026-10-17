<x-layouts.public :title="$teaching->title" :description="$teaching->summary()">
    <article>
        <header class="bg-cream">
            <div class="container-content max-w-3xl! py-10 sm:py-14">
                <a href="{{ route('teachings.index', ['categoria' => $teaching->category->value]) }}" class="text-sm font-semibold tracking-wide text-brand-strong uppercase hover:underline">{{ $teaching->category->label() }}</a>
                <h1 class="mt-2 text-3xl font-extrabold sm:text-4xl">{{ $teaching->title }}</h1>
                <p class="mt-3 text-muted">
                    <time datetime="{{ $teaching->published_on->format('Y-m-d') }}">{{ $teaching->published_on->translatedFormat('j \d\e F \d\e Y') }}</time>@if ($teaching->author_name) · {{ $teaching->author_name }}@endif
                </p>
            </div>
        </header>

        <div class="container-content max-w-3xl! py-10">
            @if ($teaching->videoEmbedUrl())
                <div class="mb-10 overflow-hidden rounded-card bg-black shadow-xl">
                    <iframe src="{{ $teaching->videoEmbedUrl() }}" title="Vídeo: {{ $teaching->title }}" class="aspect-video w-full"
                            allow="encrypted-media; picture-in-picture; fullscreen" allowfullscreen
                            referrerpolicy="strict-origin-when-cross-origin" loading="lazy"></iframe>
                </div>
            @elseif ($teaching->imageUrl())
                <img src="{{ $teaching->imageUrl() }}" alt="{{ $teaching->image_alt ?? '' }}" width="1200" height="675" class="mb-10 aspect-video w-full rounded-card object-cover" decoding="async">
            @endif

            <div class="rich-text">{{ $teaching->renderedBody() }}</div>

            <p class="mt-12 border-t border-line pt-6">
                <a href="{{ route('teachings.index') }}" class="inline-flex items-center gap-2 font-semibold text-brand-strong underline-offset-4 hover:underline">
                    <x-ui.icon name="chevron-left" class="size-5" /> Todas las enseñanzas
                </a>
            </p>
        </div>
    </article>

    @if ($related->isNotEmpty())
        <section class="border-t border-sand-dark py-14" aria-labelledby="relacionadas">
            <div class="container-content">
                <h2 id="relacionadas" class="text-2xl font-extrabold">Más de {{ mb_strtolower($teaching->category->label()) }}</h2>
                <ul class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($related as $teaching)
                        <li>@include('teachings.partials.card')</li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif
</x-layouts.public>
