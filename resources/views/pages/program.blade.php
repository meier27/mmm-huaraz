<x-layouts.public title="Programa">
    <x-ui.page-intro title="Programa" :eyebrow="$settings->dateRangeLabel()">
        Actividades de la convención día por día.
    </x-ui.page-intro>

    <div class="container-content py-10">
        @php($hasItems = $days->contains(fn ($day) => $day->items->isNotEmpty()))

        @if (! $hasItems)
            <x-ui.empty-state>El programa se publicará aquí en cuanto la organización lo confirme.</x-ui.empty-state>
        @else
            <div class="space-y-10">
                @foreach ($days as $day)
                    <section aria-labelledby="dia-{{ $day->id }}">
                        <h2 id="dia-{{ $day->id }}" class="text-2xl font-bold">
                            {{ $day->label() }}
                            @if ($day->title)<span class="block text-base font-semibold text-brand-strong">{{ $day->title }}</span>@endif
                        </h2>

                        @if ($day->items->isEmpty())
                            <p class="mt-3 text-muted">Horario por confirmar.</p>
                        @else
                            <ol class="mt-4 divide-y divide-line rounded-card border border-line bg-white">
                                @foreach ($day->items as $item)
                                    <li class="grid gap-1 p-4 sm:grid-cols-[9rem_1fr] sm:gap-4">
                                        <p class="font-semibold tabular-nums text-brand-strong">{{ $item->timeLabel() }}</p>
                                        <div>
                                            <h3 class="font-sans text-base font-semibold text-ink">{{ $item->title }}</h3>
                                            @if ($item->speaker || $item->venue)
                                                <p class="text-sm text-muted">{{ collect([$item->speaker, $item->venue?->name])->filter()->join(' · ') }}</p>
                                            @endif
                                            @if ($item->description)
                                                <p class="mt-1 text-sm whitespace-pre-line">{{ $item->description }}</p>
                                            @endif
                                        </div>
                                    </li>
                                @endforeach
                            </ol>
                        @endif
                    </section>
                @endforeach
            </div>
        @endif
    </div>
</x-layouts.public>
