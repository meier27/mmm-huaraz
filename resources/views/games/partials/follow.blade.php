{{--
    Requisito de los premios: seguir las páginas del Movimiento (Panel › Redes
    sociales). No se puede comprobar desde la web: el jugador lo declara aquí y
    el equipo lo verifica en su celular al entregar el premio.
--}}
@php
    $links = \App\Models\SiteSetting::current()->followLinks();
@endphp
<div class="text-sm">
    <p class="font-semibold text-primary">Requisito: seguir al Movimiento</p>
    <div class="mt-2 flex flex-wrap gap-2">
        @foreach ($links as $network => $url)
            <a href="{{ $url }}" target="_blank" rel="noopener"
               class="inline-flex min-h-10 items-center gap-1.5 rounded-full bg-white px-3 font-semibold text-primary ring-1 ring-line hover:bg-cream">
                <x-ui.icon :name="strtolower($network)" class="size-4" /> {{ $network }}<span class="sr-only"> (pestaña nueva)</span>
            </a>
        @endforeach
    </div>
    <label for="{{ $id }}" class="mt-3 flex items-start gap-3">
        <input id="{{ $id }}" type="checkbox" name="{{ $name }}" value="1" @if ($required ?? true) required @endif class="mt-0.5 size-5 shrink-0 rounded border-stone-300">
        <span>Sigo las páginas del Movimiento en {{ implode(' e ', array_keys($links)) }}. Lo mostraré en mi celular al recoger el premio.</span>
    </label>
    @error($name)<p class="mt-1 text-danger">{{ $message }}</p>@enderror
</div>
