<x-layouts.public :title="$current ? $current->label().' · Enseñanzas' : 'Enseñanzas'" description="Predicaciones, estudios bíblicos y devocionales del MMM Huaraz.">
    <x-ui.page-intro title="Enseñanzas" eyebrow="Palabra de Dios">
        Predicaciones, estudios bíblicos y devocionales para tu crecimiento espiritual.
    </x-ui.page-intro>

    <div class="container-content pb-6">
        {{-- Categorías --}}
        <nav aria-label="Categorías" class="-mx-4 overflow-x-auto px-4 pb-2 sm:mx-0 sm:px-0">
            <ul class="inline-flex gap-1 rounded-full bg-white p-1.5 text-sm font-semibold shadow-[0_10px_30px_-22px_rgb(57_49_45/0.6)] ring-1 ring-sand-dark">
                @foreach ([null, ...$categories] as $category)
                    @php
                        $active = $current === $category;
                    @endphp
                    <li class="shrink-0">
                        <a href="{{ $category ? route('teachings.index', ['categoria' => $category->value]) : route('teachings.index') }}"
                           @if ($active) aria-current="page" @endif
                           @class(['inline-flex min-h-10 items-center rounded-full px-4 transition', 'bg-primary text-white shadow' => $active, 'text-primary-soft hover:bg-cream hover:text-primary' => ! $active])>
                            {{ $category?->label() ?? 'Todas' }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>

        @if ($teachings->isEmpty())
            <x-ui.empty-state class="mt-8">{{ $current ? 'Aún no hay enseñanzas publicadas en esta categoría.' : 'Aún no hay enseñanzas publicadas.' }}</x-ui.empty-state>
        @else
            @php
                // En la primera página, la más reciente va destacada en grande.
                $highlight = $teachings->onFirstPage() && $teachings->count() >= 4 ? $teachings->first() : null;
                $rest = $highlight ? $teachings->slice(1) : $teachings;
            @endphp

            @if ($highlight)
                <div class="mt-10" data-reveal>
                    @include('teachings.partials.card', ['teaching' => $highlight, 'featured' => true])
                </div>
            @endif

            <ul class="mt-8 grid gap-7 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($rest as $teaching)
                    <li>@include('teachings.partials.card', ['teaching' => $teaching, 'featured' => false])</li>
                @endforeach
            </ul>
            <div class="mt-12">{{ $teachings->links() }}</div>
        @endif
    </div>
</x-layouts.public>
