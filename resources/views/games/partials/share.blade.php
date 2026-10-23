{{-- Compartir la tarjeta de logros. Requiere $player y $stats. --}}
@php
    $url = route('games.profile', $player->nickname);
    $text = "¡Llevo {$stats['stars']} estrellas y {$stats['levels']} niveles en los Juegos bíblicos del MMM Huaraz! Mira mi tarjeta de logros:";
@endphp
<div x-data="{ copied: false, canShare: !! navigator.share }" class="flex flex-wrap items-center gap-2">
    <button type="button" x-show="canShare" x-cloak x-on:click="navigator.share({ title: 'Mis logros', text: @js($text), url: @js($url) }).catch(() => {})"
            class="inline-flex min-h-11 items-center gap-2 rounded-full bg-primary px-4 text-sm font-semibold text-white hover:bg-brand-strong">
        <x-ui.icon name="share" class="size-4" /> Compartir
    </button>
    <a href="https://wa.me/?text={{ rawurlencode($text.' '.$url) }}" target="_blank" rel="noopener"
       class="inline-flex min-h-11 items-center gap-2 rounded-full bg-[#1f7a4d] px-4 text-sm font-semibold text-white hover:brightness-110">
        <x-ui.icon name="whatsapp" class="size-4" /> WhatsApp<span class="sr-only"> (pestaña nueva)</span>
    </a>
    <a href="https://www.facebook.com/sharer/sharer.php?u={{ rawurlencode($url) }}" target="_blank" rel="noopener"
       class="inline-flex min-h-11 items-center gap-2 rounded-full bg-[#1d4f9c] px-4 text-sm font-semibold text-white hover:brightness-110">
        <x-ui.icon name="facebook" class="size-4" /> Facebook<span class="sr-only"> (pestaña nueva)</span>
    </a>
    <button type="button" x-on:click="navigator.clipboard?.writeText(@js($url)).then(() => { copied = true; setTimeout(() => copied = false, 2500) })"
            class="inline-flex min-h-11 items-center gap-2 rounded-full bg-white px-4 text-sm font-semibold text-primary ring-1 ring-line hover:bg-cream">
        <x-ui.icon name="copy" class="size-4" /> <span x-text="copied ? '¡Enlace copiado!' : 'Copiar enlace'">Copiar enlace</span>
    </button>
</div>
