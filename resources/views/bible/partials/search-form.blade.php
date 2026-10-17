{{-- Búsqueda por palabras. Opcional: $query. --}}
<form method="GET" action="{{ route('bible.search') }}" role="search" class="flex gap-2">
    <label for="biblia-q" class="sr-only">Buscar en la Biblia</label>
    <input id="biblia-q" type="search" name="q" value="{{ $query ?? '' }}" minlength="3" maxlength="80" required
           placeholder="Buscar palabras, p. ej. «esperanza»"
           class="block min-h-12 w-full rounded-control border border-stone-300 bg-white px-4 py-2 focus:border-brand-strong">
    <x-ui.button type="submit" class="shrink-0"><x-ui.icon name="search" class="size-5" /><span class="sr-only sm:not-sr-only">Buscar</span></x-ui.button>
</form>
