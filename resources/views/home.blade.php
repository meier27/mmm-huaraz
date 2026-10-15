@php
    $startsAt = \Illuminate\Support\Carbon::parse(config('convention.starts_at'));
    $endsAt = \Illuminate\Support\Carbon::parse(config('convention.ends_at'));
@endphp

<x-layouts.public :description="config('convention.name').' «'.config('convention.theme').'» en '.config('convention.city').', del '.$startsAt->day.' al '.$endsAt->translatedFormat('j \d\e F \d\e Y').'.'">
    <section class="bg-cream">
        <div class="container-content py-16 sm:py-24">
            <p class="text-sm font-semibold tracking-wide text-brand-strong uppercase">
                {{ config('convention.organization') }} · {{ config('convention.city') }}
            </p>

            <h1 class="mt-3 text-4xl font-extrabold sm:text-5xl lg:text-6xl">
                {{ config('convention.name') }}
                <span class="block text-brand-strong">«{{ config('convention.theme') }}»</span>
            </h1>

            <dl class="mt-8 grid gap-4 sm:max-w-2xl sm:grid-cols-2">
                <div class="rounded-card bg-white p-5 shadow-card">
                    <dt class="text-sm text-muted">Fechas</dt>
                    <dd class="mt-1 font-display text-lg font-bold text-earth">
                        <time datetime="{{ $startsAt->toDateString() }}">{{ $startsAt->day }}</time>
                        al
                        <time datetime="{{ $endsAt->toDateString() }}">{{ $endsAt->translatedFormat('j \d\e F \d\e Y') }}</time>
                    </dd>
                </div>
                <div class="rounded-card bg-white p-5 shadow-card">
                    <dt class="text-sm text-muted">Sede</dt>
                    <dd class="mt-1 font-display text-lg font-bold text-earth">
                        {{ config('convention.venue') }}, {{ config('convention.city') }}
                    </dd>
                </div>
            </dl>
        </div>
    </section>
</x-layouts.public>
