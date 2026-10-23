{{-- Tarjeta de un premio. Requiere $prize; opcional $progress (número). --}}
@php
    $remaining = $prize->remaining();
@endphp
<article class="flex h-full flex-col overflow-hidden rounded-card bg-white shadow-card ring-1 ring-black/5">
    <div class="relative flex aspect-16/9 items-center justify-center bg-linear-to-br from-primary-deep via-primary to-gradient-end">
        @if ($prize->imageUrl())
            <img src="{{ $prize->imageUrl() }}" alt="{{ $prize->image_alt }}" class="absolute inset-0 size-full object-cover" loading="lazy">
        @else
            <x-ui.icon name="trophy" class="size-14 text-accent-light" />
        @endif
        <span @class(['absolute top-3 right-3 rounded-full px-3 py-1 text-xs font-bold shadow', 'bg-white text-primary' => $remaining > 0, 'bg-stone-700 text-white' => $remaining === 0])>
            {{ $remaining > 0 ? ($remaining === 1 ? 'Queda 1' : "Quedan {$remaining}") : 'Agotado' }}
        </span>
    </div>
    <div class="flex flex-1 flex-col p-5">
        <h3 class="font-display text-lg font-bold text-primary">{{ $prize->title }}</h3>
        @if ($prize->description)
            <p class="mt-1 text-sm text-muted">{{ $prize->description }}</p>
        @endif
        <p class="mt-3 flex items-start gap-2 text-sm font-semibold text-brand-strong">
            <x-ui.icon name="star" class="mt-0.5 size-4 shrink-0" /> {{ $prize->goalText() }}
        </p>
        @isset($progress)
            <div class="mt-3">
                <div class="h-2 overflow-hidden rounded-full bg-cream" role="progressbar" aria-valuemin="0" aria-valuemax="{{ $prize->goal_value }}" aria-valuenow="{{ $progress }}" aria-label="Avance hacia el premio">
                    <div class="h-full rounded-full bg-brand-strong" style="width: {{ round($progress / max(1, $prize->goal_value) * 100) }}%"></div>
                </div>
                <p class="mt-1 text-xs text-muted tabular-nums">{{ $progress }} de {{ $prize->goal_value }}</p>
            </div>
        @endisset
    </div>
</article>
