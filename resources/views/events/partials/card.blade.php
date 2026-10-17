{{-- Tarjeta de evento. Requiere $event. --}}
<article class="group relative flex h-full gap-4 rounded-card bg-white p-5 shadow-card ring-1 ring-black/5 transition hover:-translate-y-0.5 hover:shadow-xl">
    <div class="flex w-16 shrink-0 flex-col items-center justify-center self-start rounded-control bg-cream py-2 text-center" aria-hidden="true">
        @if ($event->hasDates())
            <span class="text-xs font-semibold text-brand-strong uppercase">{{ $event->starts_on->translatedFormat('M') }}</span>
            <span class="font-display text-2xl font-extrabold text-primary">{{ $event->starts_on->day }}</span>
        @else
            <x-ui.icon name="calendar" class="size-6 text-brand-strong" />
        @endif
    </div>
    <div class="min-w-0">
        <h3 class="text-lg font-bold">
            <a href="{{ route('events.show', $event->slug) }}" class="after:absolute after:inset-0 group-hover:text-brand-strong">{{ $event->title }}</a>
        </h3>
        <p class="mt-1 text-sm text-muted">{{ collect([$event->dateLabel(), $event->time_label, $event->location])->filter()->join(' · ') }}</p>
        @if ($event->summary)
            <p class="mt-2 line-clamp-2 text-sm">{{ $event->summary }}</p>
        @endif
    </div>
</article>
