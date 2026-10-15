@props(['name'])

{{-- Iconos de trazo propios (24×24). Decorativos: el texto adyacente aporta el significado. --}}
<svg {{ $attributes->class('size-6 shrink-0') }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
    @switch($name)
        @case('calendar')
            <rect x="3.5" y="5" width="17" height="15" rx="2" /><path d="M3.5 10h17M8 3v4M16 3v4" />
            @break
        @case('map-pin')
            <path d="M12 21s-6.5-5.8-6.5-11a6.5 6.5 0 1 1 13 0c0 5.2-6.5 11-6.5 11z" /><circle cx="12" cy="10" r="2.5" />
            @break
        @case('clock')
            <circle cx="12" cy="12" r="8.5" /><path d="M12 7.5V12l3 2" />
            @break
        @case('utensils')
            <path d="M7 3v7a2 2 0 0 0 2 2v9M11 3v7a2 2 0 0 1-2 2M9 3v6M17 21V3c-2 1.5-3 4-3 7v3h3" />
            @break
        @case('heart')
            <path d="M12 20s-7.5-4.6-7.5-10A4.5 4.5 0 0 1 12 7a4.5 4.5 0 0 1 7.5 3c0 5.4-7.5 10-7.5 10z" />
            @break
        @case('bed')
            <path d="M3 18V7M3 14h18v4M21 14v-2a3 3 0 0 0-3-3h-7v5M7 11.5a1.5 1.5 0 1 0 0-.01" />
            @break
        @case('mountain')
            <path d="M2.5 20 9 9l4 6.5L15.5 12l6 8z" /><path d="m7.6 11.4 1.4 1.1 1.3-1.1" />
            @break
        @case('chat')
            <path d="M4 5.5h16v10H9l-5 4z" /><path d="M8 10h8" />
            @break
        @case('list')
            <path d="M9 6h11M9 12h11M9 18h11M4.5 6h.01M4.5 12h.01M4.5 18h.01" />
            @break
        @case('qr')
            <rect x="4" y="4" width="6" height="6" rx="1" /><rect x="14" y="4" width="6" height="6" rx="1" /><rect x="4" y="14" width="6" height="6" rx="1" /><path d="M14 14h2v2h-2zM18 18h2v2h-2zM18 14h2M14 18v2" />
            @break
        @case('check')
            <path d="m5 12.5 4.5 4.5L19 7.5" />
            @break
        @case('arrow-right')
            <path d="M5 12h14M13 6l6 6-6 6" />
            @break
        @case('chevron-left')
            <path d="m15 5-7 7 7 7" />
            @break
        @case('chevron-right')
            <path d="m9 5 7 7-7 7" />
            @break
        @case('pause')
            <path d="M9 6v12M15 6v12" />
            @break
        @case('play')
            <path d="M8 5.5v13l10-6.5z" />
            @break
        @case('download')
            <path d="M12 4v11M7 10l5 5 5-5M5 20h14" />
            @break
    @endswitch
</svg>
