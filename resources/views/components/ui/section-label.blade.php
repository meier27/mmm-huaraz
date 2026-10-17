@props(['dark' => false])

{{--
    Antetítulo de sección como en los portales oficiales del MMM: chevron con
    degradado naranja + texto en mayúsculas (marrón sobre claro, blanco sobre oscuro).
--}}
@php
    $gradientId = 'chevron-'.\Illuminate\Support\Str::random(6);
@endphp
<p {{ $attributes->class(['inline-flex items-center gap-2 text-sm font-extrabold tracking-[0.12em] uppercase sm:text-base', 'text-primary' => ! $dark, 'text-white' => $dark]) }}>
    <svg viewBox="0 0 16 22" class="h-5 w-auto shrink-0" aria-hidden="true">
        <defs>
            <linearGradient id="{{ $gradientId }}" x1="0" y1="0" x2="0.4" y2="1">
                <stop offset="0" stop-color="var(--color-accent-light)" />
                <stop offset="1" stop-color="var(--color-accent-warm)" />
            </linearGradient>
        </defs>
        <path fill="url(#{{ $gradientId }})" d="M3.2 1.2c-1.9-.9-3.7 1.1-2.6 2.9L5.3 11 .6 17.9c-1.1 1.8.7 3.8 2.6 2.9l10.6-7.1c1.7-1.1 1.7-4.3 0-5.4z" />
    </svg>
    {{ $slot }}
</p>
