@props(['type' => 'info'])

@php
    $classes = match ($type) {
        'success' => 'border-success/30 bg-green-50 text-success',
        'error' => 'border-danger/30 bg-red-50 text-danger',
        'warning' => 'border-warning/30 bg-amber-50 text-warning',
        default => 'border-navy/20 bg-cream text-navy',
    };
@endphp

<div role="{{ $type === 'error' ? 'alert' : 'status' }}" {{ $attributes->class("rounded-control border px-4 py-3 text-sm $classes") }}>
    {{ $slot }}
</div>
