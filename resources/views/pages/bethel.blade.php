<x-layouts.public title="Bethel TV" description="Señal en vivo de Bethel Televisión, canal oficial del Movimiento Misionero Mundial.">
    <x-ui.page-intro title="Bethel Televisión" eyebrow="En vivo">
        Señal del canal oficial de YouTube del Movimiento Misionero Mundial.
    </x-ui.page-intro>

    <div class="container-content py-10">
        <div class="overflow-hidden rounded-card bg-black shadow-xl">
            <iframe src="{{ $liveUrl }}" title="Bethel Televisión en vivo" class="aspect-video w-full"
                    allow="autoplay; encrypted-media; picture-in-picture; fullscreen" allowfullscreen
                    referrerpolicy="strict-origin-when-cross-origin" loading="lazy"></iframe>
        </div>
        <p class="mt-3 text-sm text-muted">Si en este momento no hay transmisión, el reproductor mostrará un aviso de YouTube. Puedes ver los programas anteriores en el canal.</p>

        <ul class="mt-8 flex flex-wrap gap-3">
            <li><x-ui.button href="{{ $channelUrl }}" target="_blank" rel="noopener">Canal de YouTube<span class="sr-only"> (se abre en una pestaña nueva)</span></x-ui.button></li>
            <li><x-ui.button href="{{ config('bible.bethel_tv_url') }}" variant="secondary" target="_blank" rel="noopener">betheltv.tv<span class="sr-only"> (se abre en una pestaña nueva)</span></x-ui.button></li>
            <li><x-ui.button href="{{ config('bible.bethel_radio_url') }}" variant="secondary" target="_blank" rel="noopener">Bethel Radio<span class="sr-only"> (se abre en una pestaña nueva)</span></x-ui.button></li>
        </ul>
    </div>
</x-layouts.public>
