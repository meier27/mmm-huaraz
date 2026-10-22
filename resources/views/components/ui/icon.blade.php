@props(['name'])

{{-- Iconos de trazo propios (24×24). Decorativos: el texto adyacente aporta el significado. --}}
<svg {{ $attributes->class('size-6 shrink-0') }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
    @switch($name)
        {{-- Redes y contacto --}}
        @case('facebook')
            <rect x="3.5" y="3.5" width="17" height="17" rx="4" /><path d="M15.5 8H14a2.5 2.5 0 0 0-2.5 2.5v10M9 13.5h5.5" />
            @break
        @case('instagram')
            <rect x="3.5" y="3.5" width="17" height="17" rx="5" /><circle cx="12" cy="12" r="3.8" /><circle cx="17.2" cy="6.8" r="0.4" fill="currentColor" />
            @break
        @case('youtube')
            <rect x="2.5" y="5.5" width="19" height="13" rx="4" /><path d="m10.2 9.4 4.6 2.6-4.6 2.6z" fill="currentColor" />
            @break
        @case('tiktok')
            <path d="M14 3.5v11.2a3.6 3.6 0 1 1-3.6-3.6M14 3.5c.4 2.6 2.1 4.3 4.6 4.6" />
            @break
        @case('mail')
            <rect x="3" y="5.5" width="18" height="13" rx="2" /><path d="m3.5 7 8.5 6 8.5-6" />
            @break
        @case('whatsapp')
            <path d="M4 20.5 5.3 16A8.5 8.5 0 1 1 8.4 19z" /><path d="M9.2 8.8c-.4 2.9 2.4 6 5.4 5.8l.9-1.4-1.9-1-1 .7a4 4 0 0 1-1.7-1.7l.7-1-1-1.9z" />
            @break
        @case('home')
            <path d="M3.5 11 12 4l8.5 7" /><path d="M5.5 9.5V20h13V9.5M10 20v-5.5h4V20" />
            @break
        @case('image')
            <rect x="3.5" y="4.5" width="17" height="15" rx="2" /><circle cx="9" cy="9.5" r="1.8" /><path d="m4 17.5 5-5 4 4 2.5-2.5 4.5 4.5" />
            @break
        @case('settings')
            <circle cx="12" cy="12" r="3" /><path d="M12 2.8v2.4M12 18.8v2.4M2.8 12h2.4M18.8 12h2.4M5.5 5.5l1.7 1.7M16.8 16.8l1.7 1.7M5.5 18.5l1.7-1.7M16.8 7.2l1.7-1.7" />
            @break
        @case('shield')
            <path d="M12 3 4.5 6v5.5c0 4.6 3.2 8.4 7.5 9.5 4.3-1.1 7.5-4.9 7.5-9.5V6z" /><path d="m9 12 2 2 4-4" />
            @break
        @case('coins')
            <ellipse cx="9" cy="7" rx="5.5" ry="2.5" /><path d="M3.5 7v4c0 1.4 2.5 2.5 5.5 2.5S14.5 12.4 14.5 11V7" /><path d="M9.5 16.3c.6.1 1.3.2 2 .2 3 0 5.5-1.1 5.5-2.5" /><ellipse cx="15" cy="13" rx="5.5" ry="2.5" /><path d="M9.5 13v4c0 1.4 2.5 2.5 5.5 2.5s5.5-1.1 5.5-2.5v-4" />
            @break
        @case('help')
            <circle cx="12" cy="12" r="8.5" /><path d="M9.5 9.5a2.5 2.5 0 1 1 3.5 2.3c-.6.3-1 .9-1 1.6v.6M12 17h.01" />
            @break
        @case('quote')
            <path d="M5 17.5c0-4 1-7.5 5-10M14 17.5c0-4 1-7.5 5-10" /><path d="M5 13.5h4v4H5zM14 13.5h4v4h-4z" />
            @break
        @case('star')
            <path d="m12 3.5 2.6 5.3 5.9.9-4.3 4.1 1 5.8L12 16.9l-5.2 2.7 1-5.8-4.3-4.1 5.9-.9z" />
            @break
        @case('user')
            <circle cx="12" cy="8" r="3.8" /><path d="M4.5 20.5c.7-4 3.7-6.5 7.5-6.5s6.8 2.5 7.5 6.5" />
            @break
        @case('logout')
            <path d="M14 4.5h4.5a1.5 1.5 0 0 1 1.5 1.5v12a1.5 1.5 0 0 1-1.5 1.5H14M10 16l-4-4 4-4M6 12h10" />
            @break
        @case('external')
            <path d="M13.5 4.5H19.5V10.5M19.5 4.5l-8 8M17 13.5v5a1.5 1.5 0 0 1-1.5 1.5h-10A1.5 1.5 0 0 1 4 18.5v-10A1.5 1.5 0 0 1 5.5 7h5" />
            @break
        @case('menu')
            <path d="M4 6.5h16M4 12h16M4 17.5h16" />
            @break
        @case('close')
            <path d="M6 6l12 12M18 6 6 18" />
            @break
        @case('book')
            <path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v15H6.5A2.5 2.5 0 0 0 4 20.5zM4 20.5A2.5 2.5 0 0 0 6.5 23H20v-5" /><path d="M12 7v6M9 9.5h6" />
            @break
        @case('info')
            <circle cx="12" cy="12" r="9" /><path d="M12 11v5M12 8h.01" />
            @break
        @case('phone')
            <path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2" />
            @break
        @case('search')
            <circle cx="11" cy="11" r="6.5" /><path d="m20 20-4.4-4.4" />
            @break
        @case('users')
            <circle cx="9" cy="8" r="3.5" /><path d="M2.5 20c.5-3.6 3.2-6 6.5-6s6 2.4 6.5 6M16 4.6a3.5 3.5 0 0 1 0 6.8M18 14.3c2 .8 3.3 2.9 3.5 5.7" />
            @break
        @case('tv')
            <rect x="2.5" y="6" width="19" height="13" rx="2" /><path d="m8 2.5 4 3.5 4-3.5M10 10.5v5l4-2.5z" />
            @break
        @case('calendar')
            <rect x="3.5" y="5" width="17" height="15" rx="2" /><path d="M3.5 10h17M8 3v4M16 3v4" />
            @break
        @case('map-pin')
            <path d="M12 21s-6.5-5.8-6.5-11a6.5 6.5 0 1 1 13 0c0 5.2-6.5 11-6.5 11z" /><circle cx="12" cy="10" r="2.5" />
            @break
        @case('clock')
            <circle cx="12" cy="12" r="8.5" /><path d="M12 7.5V12l3 2" />
            @break
        @case('heart')
            <path d="M12 20s-7.5-4.6-7.5-10A4.5 4.5 0 0 1 12 7a4.5 4.5 0 0 1 7.5 3c0 5.4-7.5 10-7.5 10z" />
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
