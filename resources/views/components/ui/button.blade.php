@props([
    'variant' => 'primary',
    'href' => null,
    'type' => 'button',
])

@php
    $classes = 'inline-flex min-h-11 items-center justify-center gap-2 rounded-control px-5 py-2.5 font-semibold transition-colors disabled:cursor-not-allowed disabled:opacity-60 '
        . match ($variant) {
            'primary' => 'bg-brand-strong text-white hover:bg-earth',
            'secondary' => 'border border-earth text-earth hover:bg-cream',
            'ghost' => 'text-earth hover:bg-cream',
        };
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->class($classes) }}>{{ $slot }}</button>
@endif
