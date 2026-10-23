@props(['name', 'label', 'type' => 'text', 'hint' => null, 'value' => null])

{{-- Campo de los formularios de jugadores (con su error y ayuda accesibles). --}}
<div>
    <label for="{{ $name }}" class="block text-sm font-semibold text-primary">{{ $label }}</label>
    <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" @if ($type !== 'password') value="{{ old($name, $value) }}" @endif
           @error($name) aria-invalid="true" @enderror aria-describedby="{{ $name }}-hint {{ $name }}-error"
           {{ $attributes->class('mt-1 block min-h-12 w-full rounded-control border border-stone-300 bg-white px-3 py-2 text-base focus:border-brand-strong') }}>
    @if ($hint)
        <p id="{{ $name }}-hint" class="mt-1 text-xs text-muted">{{ $hint }}</p>
    @endif
    @error($name)
        <p id="{{ $name }}-error" class="mt-1 text-sm text-danger">{{ $message }}</p>
    @enderror
</div>
