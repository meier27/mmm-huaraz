@props([
    'variant' => 'primary',
    'type' => 'button',
])

@php
    $classes = 'inline-flex min-h-11 items-center justify-center gap-2 rounded-control px-5 py-2.5 font-semibold transition-colors disabled:cursor-not-allowed disabled:opacity-60 '
        . match ($variant) {
            'primary' => 'bg-brand-strong text-white hover:bg-primary',
            'secondary' => 'border border-primary text-primary hover:bg-cream',
            'ghost' => 'text-primary hover:bg-cream',
        };
@endphp

{{-- "href" se deja en $attributes para que Blade lo escape una sola vez. --}}
@if ($attributes->has('href'))
    <a {{ $attributes->class($classes) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->merge(['type' => $type])->class($classes) }}>{{ $slot }}</button>
@endif
