<x-games.shell :title="$game['title'].' · Nivel '.$level" :description="$game['text']" :heading="$game['title'].' · Nivel '.$level" :icon="$game['icon']"
               :back="['url' => route('games.levels', $game['slug']), 'label' => 'Niveles de '.$game['title']]" :noindex="true">
    <x-slot:subtitle>{{ $description }}@unless ($player) · <span class="font-semibold text-brand-strong">sin cuenta, este nivel no se guarda</span>@endunless</x-slot:subtitle>

    {{-- data-game: el tablero no usa la animación de aparición al hacer scroll --}}
    <div data-game data-bible="{{ route('bible.search') }}" class="mt-6 sm:mt-8">
        @include('games.boards.'.$game['view'], ['config' => $config])
    </div>

    @vite('resources/js/games.js')
</x-games.shell>
