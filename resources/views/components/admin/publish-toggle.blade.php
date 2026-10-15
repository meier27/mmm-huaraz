@props(['record'])

<button type="button" wire:click="togglePublished({{ $record->id }})"
        @class([
            'inline-flex min-h-9 items-center rounded-full px-3 text-xs font-semibold',
            'bg-green-100 text-success' => $record->is_published,
            'bg-stone-100 text-muted' => ! $record->is_published,
        ])
        title="Cambiar estado de publicación">
    {{ $record->is_published ? 'Publicado' : 'Borrador' }}
</button>
