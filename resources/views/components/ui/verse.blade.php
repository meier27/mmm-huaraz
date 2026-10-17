@props(['verse'])

<figure {{ $attributes->class('relative overflow-hidden rounded-card bg-primary-deep p-8 text-white sm:p-10') }}>
    <span class="absolute -top-6 left-4 font-display text-[9rem] leading-none text-white/10" aria-hidden="true">“</span>
    <p class="text-xs font-semibold tracking-[0.2em] text-brand uppercase">Versículo destacado</p>
    <blockquote class="relative mt-4 font-display text-xl leading-relaxed font-semibold text-white sm:text-2xl">
        {{ $verse->text }}
    </blockquote>
    <figcaption class="mt-5 font-semibold text-cream">
        {{ $verse->reference }}@if ($verse->version) <span class="font-normal text-cream/80">({{ $verse->version }})</span>@endif
    </figcaption>
</figure>
