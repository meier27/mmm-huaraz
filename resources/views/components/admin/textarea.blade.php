@props(['label', 'model', 'rows' => 4, 'hint' => null])

@php($id = str_replace('.', '-', $model))

<div {{ $attributes->only('class') }}>
    <label for="{{ $id }}" class="block text-sm font-medium">{{ $label }}</label>
    <textarea id="{{ $id }}" rows="{{ $rows }}" wire:model="{{ $model }}"
              @error($model) aria-invalid="true" @enderror
              aria-describedby="{{ $id }}-hint {{ $id }}-error"
              {{ $attributes->except('class')->class('mt-1 block w-full rounded-control border border-stone-300 bg-white px-3 py-2 focus:border-brand-strong') }}></textarea>
    @if ($hint)
        <p id="{{ $id }}-hint" class="mt-1 text-xs text-muted">{{ $hint }}</p>
    @endif
    @error($model)
        <p id="{{ $id }}-error" class="mt-1 text-sm text-danger">{{ $message }}</p>
    @enderror
</div>
