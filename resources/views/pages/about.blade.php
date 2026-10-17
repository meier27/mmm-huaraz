<x-layouts.public title="Nosotros" :description="'Quiénes somos y los líderes de '.$site->church_name.'.'">
    <x-ui.page-intro title="Nosotros" :eyebrow="$site->church_name">
        Quiénes somos y quiénes sirven en nuestra iglesia.
    </x-ui.page-intro>

    <section class="relative isolate overflow-x-clip pt-10 pb-20 sm:pt-14 sm:pb-28">

        <div class="container-content space-y-20 sm:space-y-28">
            @if ($blocks->isEmpty() && $leaders->isEmpty())
                <x-ui.empty-state>La información de la iglesia se publicará aquí en cuanto la confirme el pastorado.</x-ui.empty-state>
            @endif

            @if ($blocks->isNotEmpty())
                @include('pages.partials.about-blocks', ['blocks' => $blocks])
            @endif

            @if ($leaders->isNotEmpty())
                <section aria-labelledby="lideres">
                    <x-ui.section-label>Nuestros líderes</x-ui.section-label>
                    <h2 id="lideres" class="mt-3 text-3xl font-bold sm:text-4xl">Quienes sirven en Huaraz</h2>
                    <ul class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($leaders as $leader)
                            <li class="rounded-card bg-white p-7 text-center shadow-[0_20px_45px_-25px_rgb(57_49_45/0.45)] ring-1 ring-black/5">
                                @if ($leader->imageUrl())
                                    <img src="{{ $leader->imageUrl() }}" alt="{{ $leader->name }}" width="160" height="160" class="mx-auto size-32 rounded-full object-cover ring-4 ring-cream" loading="lazy" decoding="async">
                                @else
                                    <span class="mx-auto flex size-32 items-center justify-center rounded-full bg-cream font-display text-4xl font-bold text-brand-strong ring-4 ring-sand" aria-hidden="true">{{ mb_substr(preg_replace('/^\[[^\]]*\]\s*/u', '', $leader->name), 0, 1) }}</span>
                                @endif
                                <h3 class="mt-5 text-lg font-bold">{{ $leader->name }}</h3>
                                <p class="mt-1 text-xs font-semibold tracking-[0.12em] text-brand-strong uppercase">{{ $leader->role }}</p>
                                @if ($leader->bio)
                                    <p class="mt-3 text-sm text-muted">{{ $leader->bio }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </div>
    </section>
</x-layouts.public>
