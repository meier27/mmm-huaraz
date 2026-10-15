<x-layouts.public>
    <section class="relative overflow-hidden bg-cream">
        <div class="container-content py-14 sm:py-20 lg:py-24">
            <p class="text-sm font-semibold tracking-wide text-brand-strong uppercase">
                {{ $settings->organization }} · {{ $settings->city }}
            </p>

            <h1 class="mt-3 max-w-4xl text-4xl font-extrabold sm:text-5xl lg:text-6xl">
                {{ $settings->name }}
                <span class="block text-brand-strong">«{{ $settings->theme }}»</span>
            </h1>

            <p class="mt-5 inline-flex items-center rounded-full bg-white px-4 py-2 text-sm font-semibold text-earth shadow-card">
                {{ $settings->countdownLabel() }}
            </p>

            <dl class="mt-8 grid gap-4 sm:max-w-3xl sm:grid-cols-2">
                <div class="rounded-card bg-white p-5 shadow-card">
                    <dt class="text-sm text-muted">Fechas</dt>
                    <dd class="mt-1 font-display text-lg font-bold text-earth">
                        <time datetime="{{ $settings->starts_on->toDateString() }}">{{ $settings->dateRangeLabel() }}</time>
                    </dd>
                </div>
                <div class="rounded-card bg-white p-5 shadow-card">
                    <dt class="text-sm text-muted">Sede</dt>
                    <dd class="mt-1 font-display text-lg font-bold text-earth">
                        {{ $mainVenue ? $mainVenue->name.', ' : '' }}{{ $settings->city }}
                    </dd>
                </div>
            </dl>

            <div class="mt-8 flex flex-wrap gap-3">
                <x-ui.button href="{{ route('program') }}">Ver programa</x-ui.button>
                <x-ui.button href="{{ route('venue') }}" variant="secondary">Cómo llegar</x-ui.button>
            </div>
        </div>
    </section>

    @if ($settings->summary)
        <section class="container-content py-12">
            <div class="max-w-3xl text-lg leading-relaxed whitespace-pre-line">{{ $settings->summary }}</div>
        </section>
    @endif

    <section class="container-content py-12" aria-labelledby="accesos">
        <h2 id="accesos" class="text-2xl font-bold">Todo lo que necesitas</h2>
        @php
            $shortcuts = [
                ['route' => 'program', 'title' => 'Programa', 'text' => 'Horarios y actividades de cada día.'],
                ['route' => 'venue', 'title' => 'Sede y cómo llegar', 'text' => 'Ubicación, mapa e indicaciones.'],
                ['route' => 'donations', 'title' => 'Donaciones', 'text' => 'Métodos oficiales para tu aporte.'],
                ['route' => 'accommodations', 'title' => 'Hospedaje', 'text' => 'Opciones de alojamiento en la ciudad.'],
                ['route' => 'tourism', 'title' => 'Descubre Huaraz', 'text' => 'Lugares para conocer durante tu visita.'],
                ['route' => 'contact', 'title' => 'Contacto', 'text' => 'Teléfonos, WhatsApp y preguntas frecuentes.'],
            ];
        @endphp
        <ul class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($shortcuts as $shortcut)
                <li>
                    <a href="{{ route($shortcut['route']) }}" class="group block h-full rounded-card border border-line bg-white p-5 transition-shadow hover:shadow-card">
                        <span class="font-display text-lg font-bold text-earth group-hover:text-brand-strong">{{ $shortcut['title'] }}</span>
                        <span class="mt-1 block text-sm text-muted">{{ $shortcut['text'] }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </section>

    @if ($faqs->isNotEmpty())
        <section class="bg-stone-50" aria-labelledby="preguntas">
            <div class="container-content py-12">
                <h2 id="preguntas" class="text-2xl font-bold">Preguntas frecuentes</h2>
                <div class="mt-6 max-w-3xl">
                    @include('pages.partials.faq-list', ['faqs' => $faqs])
                </div>
                <a href="{{ route('contact') }}" class="mt-4 inline-block font-semibold text-brand-strong underline-offset-4 hover:underline">Ver todas las preguntas</a>
            </div>
        </section>
    @endif
</x-layouts.public>
