<x-layouts.admin title="Inicio">
    {{-- Cabecera --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-extrabold sm:text-3xl">Hola, {{ auth()->user()->name }}</h1>
            <p class="mt-1 text-muted">
                {{ ucfirst(now()->translatedFormat('l j \d\e F')) }}
                @if ($event) · {{ $event->title }}@if ($event->countdownLabel()): {{ mb_strtolower($event->countdownLabel()) }}@endif @endif
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            @can('manage-donations')
                <a href="{{ route('admin.donations') }}" wire:navigate class="inline-flex min-h-10 items-center gap-2 rounded-full bg-white px-4 text-sm font-semibold text-primary shadow-sm ring-1 ring-line hover:bg-cream">
                    <x-ui.icon name="coins" class="size-4" /> Aportes
                </a>
            @endcan
            <a href="{{ route('home') }}" target="_blank" rel="noopener" class="inline-flex min-h-10 items-center gap-2 rounded-full bg-white px-4 text-sm font-semibold text-primary shadow-sm ring-1 ring-line hover:bg-cream">
                <x-ui.icon name="external" class="size-4" /> Ver sitio<span class="sr-only"> (pestaña nueva)</span>
            </a>
        </div>
    </div>

    {{-- Indicadores --}}
    @if ($tiles)
        <ul class="mt-6 grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
            @foreach ($tiles as $tile)
                <li>
                    <a href="{{ route($tile['route']) }}" wire:navigate class="block h-full rounded-3xl border border-line bg-white p-4 transition sm:p-5 hover:shadow-card hover:ring-2 hover:ring-brand/40">
                        <x-ui.icon :name="$tile['icon']" class="size-6 text-brand-strong sm:size-7" />
                        <p @class(['mt-3 font-display text-2xl font-extrabold tabular-nums sm:mt-4 sm:text-3xl', 'text-brand-strong' => $tile['accent'] ?? false, 'text-ink' => ! ($tile['accent'] ?? false)])>{{ $tile['value'] }}</p>
                        <p class="mt-1 text-sm text-muted">{{ $tile['label'] }}@isset($tile['hint']) <span class="text-stone-400">· {{ $tile['hint'] }}</span>@endisset</p>
                    </a>
                </li>
            @endforeach
        </ul>
    @endif

    <div class="mt-6 grid gap-4 xl:grid-cols-2">
        {{-- Aportes por verificar --}}
        @can('manage-donations')
            <section class="rounded-3xl border border-line bg-white p-5 sm:p-6" aria-labelledby="aportes-pendientes">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 id="aportes-pendientes" class="text-lg font-extrabold">Aportes por verificar</h2>
                        <p class="text-sm text-muted">Confírmalos con el estado de cuenta</p>
                    </div>
                    <a href="{{ route('admin.donations') }}" wire:navigate class="text-sm font-semibold text-brand-strong hover:underline">Ver todos</a>
                </div>
                @if ($pendingDonations->isEmpty())
                    <p class="mt-6 text-sm text-muted">No hay aportes pendientes.</p>
                @else
                    <ul class="mt-4 divide-y divide-line">
                        @foreach ($pendingDonations as $donation)
                            <li class="flex items-center justify-between gap-3 py-3">
                                <div class="min-w-0">
                                    <p class="truncate font-semibold">{{ $donation->donor_name ?: 'Anónimo' }} <span class="font-normal text-muted">· {{ $donation->method_label }}</span></p>
                                    <p class="text-xs text-muted">{{ $donation->reference }} · {{ $donation->created_at->diffForHumans() }}</p>
                                </div>
                                <span class="font-display font-bold tabular-nums">S/ {{ number_format((float) $donation->declared_amount, 2) }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        @endcan

        {{-- Estado del contenido publicado --}}
        @can('manage-content')
            <section class="rounded-3xl border border-line bg-white p-5 sm:p-6" aria-labelledby="contenido-publicado">
                <h2 id="contenido-publicado" class="text-lg font-extrabold">Contenido publicado</h2>
                <p class="text-sm text-muted">Lo que hoy se ve en la web</p>
                <ul class="mt-4 grid gap-2 sm:grid-cols-2">
                    @foreach ($content as $section)
                        <li>
                            <a href="{{ route($section['route']) }}" wire:navigate class="flex items-center gap-3 rounded-control px-3 py-2.5 hover:bg-cream">
                                <span class="flex size-9 items-center justify-center rounded-full bg-cream text-brand-strong"><x-ui.icon :name="$section['icon']" class="size-4" /></span>
                                <span class="flex-1 text-sm">{{ $section['label'] }}</span>
                                <span @class(['font-display font-bold tabular-nums', 'text-stone-400' => $section['count'] === 0, 'text-ink' => $section['count'] > 0])>{{ $section['count'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endcan
    </div>
</x-layouts.admin>
