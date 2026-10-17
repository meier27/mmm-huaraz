@props(['label', 'model', 'live' => false])

@php($id = str_replace('.', '-', $model))

<div>
    <label for="{{ $id }}" class="inline-flex min-h-11 items-center gap-2 text-sm font-medium">
        <input id="{{ $id }}" type="checkbox" {{ $live ? 'wire:model.live' : 'wire:model' }}="{{ $model }}" class="size-5 rounded border-stone-300">
        {{ $label }}
    </label>
    @error($model)
        <p class="mt-1 text-sm text-danger">{{ $message }}</p>
    @enderror
</div>
