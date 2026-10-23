{{-- Medalla de un juego: bronce, plata u oro. Requiere $medal. --}}
@php
    $tone = ['bronce' => 'from-amber-700 to-orange-400 text-white', 'plata' => 'from-stone-400 to-stone-200 text-stone-800', 'oro' => 'from-yellow-500 to-amber-300 text-amber-950'][$medal];
@endphp
<span class="inline-flex min-h-10 items-center gap-1.5 rounded-full bg-linear-to-br px-4 text-sm font-bold shadow {{ $tone }}">
    <x-ui.icon name="trophy" class="size-4" /> Medalla de {{ $medal }}
</span>
