{{-- Tarjeta de un juego (índice, portada y «Más juegos»). Requiere $game. --}}
@php
    $compact ??= false;
    $done ??= null; // niveles superados por el jugador (null sin cuenta)
@endphp
@if ($game['ready'])
    <a href="{{ route('games.levels', $game['slug']) }}"
       class="group flex h-full flex-col overflow-hidden rounded-card bg-white shadow-[0_18px_40px_-24px_rgb(57_49_45/0.4)] ring-1 ring-black/5 transition hover:-translate-y-1 hover:shadow-[0_24px_44px_-22px_rgb(57_49_45/0.5)]">
@else
    <div class="flex h-full flex-col overflow-hidden rounded-card bg-white/70 ring-1 ring-black/5" aria-disabled="true">
@endif
        <span @class(['relative isolate flex items-center justify-center overflow-hidden bg-linear-to-br from-primary-deep via-primary to-gradient-end', 'h-24' => $compact, 'h-28 sm:h-32' => ! $compact, 'grayscale' => ! $game['ready']])>
            <span class="absolute -top-10 -right-8 -z-10 size-32 rounded-full bg-brand/30 blur-2xl" aria-hidden="true"></span>
            <x-ui.mountains class="absolute inset-x-0 bottom-0 -z-10 h-8 text-white/5" />
            <span class="flex size-14 items-center justify-center rounded-2xl bg-accent-light text-primary-deep shadow-lg transition duration-300 group-hover:scale-110 group-hover:-rotate-6 sm:size-16">
                <x-ui.icon :name="$game['icon']" class="size-7 sm:size-8" />
            </span>
        </span>
        <span class="flex flex-1 flex-col p-5">
            <span class="font-display text-lg leading-snug font-bold text-primary sm:text-xl">{{ $game['title'] }}</span>
            @unless ($compact)
                <span class="mt-1 flex-1 text-sm leading-relaxed text-muted">{{ $game['text'] }}</span>
            @endunless
            @if ($game['ready'] && $done !== null)
                <span class="mt-3 block">
                    <span class="block h-1.5 overflow-hidden rounded-full bg-cream" aria-hidden="true">
                        <span class="block h-full rounded-full bg-brand-strong" style="width: {{ round($done / \App\Services\GameCatalog::LEVELS * 100) }}%"></span>
                    </span>
                    <span class="mt-1 block text-xs text-muted tabular-nums">{{ $done }} de {{ \App\Services\GameCatalog::LEVELS }} niveles</span>
                </span>
            @endif
            @if ($game['ready'])
                <span class="mt-4 inline-flex items-center gap-2 text-sm font-bold text-brand-strong">
                    Jugar <x-ui.icon name="arrow-right" class="size-4 transition group-hover:translate-x-1" />
                </span>
            @else
                <span class="mt-4 text-sm font-semibold text-muted">Muy pronto</span>
            @endif
        </span>
@if ($game['ready'])
    </a>
@else
    </div>
@endif
