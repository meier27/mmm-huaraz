@props(['label', 'model', 'options' => [], 'placeholder' => null])

{{-- $options: [valor => etiqueta] --}}
@php($id = str_replace('.', '-', $model))

<div {{ $attributes->only('class') }}>
    <label for="{{ $id }}" class="block text-sm font-medium">{{ $label }}</label>
    <select id="{{ $id }}" wire:model="{{ $model }}"
            @error($model) aria-invalid="true" aria-describedby="{{ $id }}-error" @enderror
            {{ $attributes->except('class')->class('mt-1 block min-h-11 w-full rounded-control border border-stone-300 bg-white px-3 py-2 focus:border-navy') }}>
        @if ($placeholder !== null)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach ($options as $value => $optionLabel)
            <option value="{{ $value }}">{{ $optionLabel }}</option>
        @endforeach
    </select>
    @error($model)
        <p id="{{ $id }}-error" class="mt-1 text-sm text-danger">{{ $message }}</p>
    @enderror
</div>
