@props(['position' => 'bottom'])

{{--
    Separador de sección en curva, como los del portal oficial. Se coloca dentro
    de una sección con "relative"; el color es el de la sección vecina
    (text-*, porque el trazo usa currentColor).
--}}
<div {{ $attributes->class(['pointer-events-none absolute inset-x-0 overflow-hidden leading-none', 'top-0' => $position === 'top', 'bottom-0' => $position === 'bottom']) }} aria-hidden="true">
    <svg viewBox="0 0 1000 100" preserveAspectRatio="none" class="block h-10 w-[115%] -translate-x-[6.5%] sm:h-16">
        @if ($position === 'top')
            <path fill="currentColor" d="M1000,4.3V0H0v4.3C0.9,23.1,126.7,99.2,500,100S1000,22.7,1000,4.3z" />
        @else
            <path fill="currentColor" d="M500,97C126.7,96.3,0.8,19.8,0,0v100l1000,0V1C1000,19.4,873.3,97.8,500,97z" />
        @endif
    </svg>
</div>
