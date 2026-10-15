@props(['title', 'eyebrow' => null])

<section class="bg-cream">
    <div class="container-content py-10 sm:py-14">
        @if ($eyebrow)
            <p class="text-sm font-semibold tracking-wide text-brand-strong uppercase">{{ $eyebrow }}</p>
        @endif
        <h1 class="mt-2 text-3xl font-extrabold sm:text-4xl">{{ $title }}</h1>
        @if ($slot->isNotEmpty())
            <div class="mt-3 max-w-2xl text-muted">{{ $slot }}</div>
        @endif
    </div>
</section>
