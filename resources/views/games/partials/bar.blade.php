{{--
    Barra de un nivel: estadísticas en vivo y «Reiniciar» (vuelve a armar el nivel).
    $stats: [[icono, etiqueta, expresión de Alpine]].
--}}
<div class="flex flex-wrap items-center gap-2">
    @foreach ($stats as [$icon, $label, $expression])
        <span class="inline-flex min-h-9 items-center gap-1.5 rounded-full bg-white px-3 text-sm font-semibold text-primary tabular-nums ring-1 ring-line" title="{{ $label }}">
            <x-ui.icon :name="$icon" class="size-4 text-brand-strong" />
            <span class="sr-only">{{ $label }}:</span>
            <span x-text="{{ $expression }}"></span>
        </span>
    @endforeach
    <a href="{{ url()->current() }}" x-show="! finished" class="ms-auto inline-flex min-h-11 items-center gap-2 rounded-full bg-white px-4 text-sm font-semibold text-primary ring-1 ring-line transition hover:bg-cream">
        <x-ui.icon name="refresh" class="size-4" /> Reiniciar
    </a>
</div>
<p x-show="error" x-cloak class="mt-2 rounded-control bg-red-50 px-4 py-2 text-sm font-semibold text-danger" role="alert" x-text="error"></p>
