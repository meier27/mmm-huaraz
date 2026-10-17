<x-layouts.public title="Eventos" description="Convenciones, campañas, vigilias y actividades del MMM Huaraz.">
    <x-ui.page-intro title="Eventos" eyebrow="Agenda">
        Convenciones, campañas, vigilias y demás actividades de la iglesia.
    </x-ui.page-intro>

    <div class="container-content py-10">
        {{-- Evento destacado (elegido en el panel) --}}
        @if ($featured)
            <a href="{{ route('events.show', $featured) }}" class="group relative isolate block overflow-hidden rounded-card bg-linear-to-br from-primary via-primary-soft to-primary-deep p-6 text-white shadow-xl sm:p-10">
                @if ($featured->imageUrl())
                    <img src="{{ $featured->imageUrl() }}" alt="" class="absolute inset-0 -z-20 size-full object-cover opacity-30" loading="lazy" decoding="async">
                @endif
                <x-ui.mountains class="absolute inset-x-0 bottom-0 -z-10 h-24 text-primary-deep sm:h-32" />
                <p class="text-xs font-semibold tracking-[0.2em] text-brand uppercase">{{ collect(['Destacado', $featured->countdownLabel()])->filter()->join(' · ') }}</p>
                <h2 class="mt-3 text-3xl font-extrabold text-white sm:text-4xl">{{ $featured->title }}</h2>
                @if ($featured->theme)
                    <p class="mt-1 font-display text-xl font-bold text-brand">«{{ $featured->theme }}»</p>
                @endif
                <p class="mt-3 text-cream/90">{{ collect([$featured->city ?? $featured->location, $featured->dateLabel()])->filter()->join(' · ') }}</p>
                <span class="mt-6 inline-flex min-h-12 items-center gap-2 rounded-control bg-brand px-6 py-3 font-bold text-primary transition group-hover:bg-white">
                    {{ $featured->hasAnyModule() ? 'Ver todo sobre el evento' : 'Ver detalles' }} <x-ui.icon name="arrow-right" class="size-5" />
                </span>
            </a>
        @endif

        <section id="proximos" @class(['scroll-mt-28', 'mt-14' => $featured]) aria-labelledby="proximos-titulo">
            <h2 id="proximos-titulo" class="text-2xl font-extrabold sm:text-3xl">Próximos eventos</h2>
            @if ($upcoming->isEmpty())
                <x-ui.empty-state class="mt-5">No hay otros eventos publicados por ahora.</x-ui.empty-state>
            @else
                <ul class="mt-5 grid gap-4 md:grid-cols-2">
                    @foreach ($upcoming as $event)
                        <li>@include('events.partials.card')</li>
                    @endforeach
                </ul>
                <div class="mt-8">{{ $upcoming->links() }}</div>
            @endif
        </section>

        @if ($past->isNotEmpty())
            <section id="anteriores" class="mt-14 scroll-mt-28" aria-labelledby="anteriores-titulo">
                <h2 id="anteriores-titulo" class="text-2xl font-extrabold sm:text-3xl">Eventos anteriores</h2>
                <ul class="mt-5 grid gap-4 opacity-90 md:grid-cols-2">
                    @foreach ($past as $event)
                        <li>@include('events.partials.card')</li>
                    @endforeach
                </ul>
                <div class="mt-8">{{ $past->links() }}</div>
            </section>
        @endif
    </div>
</x-layouts.public>
