{{--
    Carrusel de banners. Requiere $banners (no vacío).
    La imagen es la protagonista: velo suave solo detrás del texto y poco texto
    encima (título, subtítulo breve y un botón).
--}}
<section class="relative isolate overflow-hidden bg-primary text-white"
         aria-roledescription="carrusel" aria-label="Destacados"
         x-data="{
             active: 0,
             total: {{ $banners->count() }},
             // Automático siempre que haya más de una diapositiva; el botón de pausa
             // lo detiene (WCAG 2.2.2). Con «reducir movimiento» cambia sin fundido.
             playing: {{ $banners->count() > 1 ? 'true' : 'false' }},
             interval: 5000,
             timer: null,
             init() {
                 this.start();
                 // Sin trabajo en segundo plano: se pausa mientras la pestaña está oculta.
                 document.addEventListener('visibilitychange', () => document.hidden ? this.stop() : this.start());
             },
             start() { this.stop(); if (this.playing) this.timer = setInterval(() => this.next(), this.interval) },
             stop() { clearInterval(this.timer) },
             toggle() { this.playing = ! this.playing; this.start() },
             next() { this.active = (this.active + 1) % this.total },
             prev() { this.active = (this.active - 1 + this.total) % this.total },
             go(i) { this.active = i; this.start() },
         }"
         x-on:keydown.arrow-right="next(); start()" x-on:keydown.arrow-left="prev(); start()">

    @php
        // Solo afiches (sin texto encima): en móvil se muestran completos, con su
        // proporción, debajo de la cabecera; recortarlos a lo alto cortaría su texto.
        $posters = $banners->every(fn ($b) => $b->imageUrl() && ! $b->title && ! $b->subtitle && ! $b->cta_label);
    @endphp
    <div @class(['relative overflow-hidden sm:min-h-192 lg:min-h-208', 'min-h-172' => ! $posters, 'mt-24 aspect-[5/2] sm:mt-0 sm:aspect-auto' => $posters])>
        @foreach ($banners as $banner)
            <div
                 role="group" aria-roledescription="diapositiva" aria-label="{{ $loop->iteration }} de {{ $banners->count() }}"
                 @class(['absolute inset-0 transition-opacity duration-700 ease-out motion-reduce:transition-none', 'opacity-100 z-10' => $loop->first, 'opacity-0 z-0' => ! $loop->first])
                 {{-- Objeto (no cadena): así Alpine también quita las clases puestas por el servidor. --}}
                 x-bind:class="{ 'opacity-100 z-10': active === {{ $loop->index }}, 'opacity-0 z-0': active !== {{ $loop->index }} }"
                 x-bind:inert="active !== {{ $loop->index }}">
                @if ($banner->imageUrl())
                    <img src="{{ $banner->imageUrl() }}" alt="{{ $banner->image_alt ?? '' }}" width="1920" height="900"
                         class="absolute inset-0 -z-10 size-full object-cover"
                         @if ($loop->first) fetchpriority="high" @else loading="lazy" @endif decoding="async">
                    {{-- Velo mínimo: legibilidad del texto (izquierda, solo si lo hay) y de la cabecera (arriba). --}}
                    @if ($banner->title || $banner->subtitle || $banner->cta_label)
                        <div class="absolute inset-0 -z-10 bg-linear-to-r from-black/55 via-black/15 to-transparent"></div>
                    @endif
                    <div class="absolute inset-x-0 top-0 -z-10 h-32 bg-linear-to-b from-black/35 to-transparent"></div>
                @else
                    <div class="absolute inset-0 -z-10 bg-linear-to-br from-primary via-primary-soft to-primary"></div>
                    <x-ui.mountains class="absolute inset-x-0 bottom-0 -z-10 h-40 text-primary-deep sm:h-56" />
                @endif

                <div @class(['container-content flex h-full flex-col justify-center pt-36 pb-16 transition duration-700 ease-out-soft motion-reduce:transition-none sm:pt-40 sm:pb-20', 'opacity-100 translate-y-0' => $loop->first, 'opacity-0 translate-y-6' => ! $loop->first])
                     x-bind:class="{ 'opacity-100 translate-y-0 delay-200': active === {{ $loop->index }}, 'opacity-0 translate-y-6': active !== {{ $loop->index }} }">
                    @if ($banner->title)
                        <h2 class="max-w-xl font-display text-3xl leading-tight font-bold text-white [text-shadow:0_2px_16px_rgb(0_0_0/0.45)] sm:text-4xl lg:text-5xl">{{ $banner->title }}</h2>
                    @endif
                    @if ($banner->subtitle)
                        <p class="mt-4 line-clamp-2 hidden max-w-lg text-lg text-white/90 [text-shadow:0_1px_8px_rgb(0_0_0/0.5)] sm:block">{{ $banner->subtitle }}</p>
                    @endif
                    <div class="mt-7 flex flex-wrap gap-3">
                        @if ($banner->cta_label && $banner->cta_url)
                            {{-- Las rutas internas («/nosotros») pasan por url(): funcionan también en un subdirectorio. --}}
                            <a href="{{ str_starts_with($banner->cta_url, 'https://') ? $banner->cta_url : url($banner->cta_url) }}"
                               class="inline-flex min-h-12 items-center gap-2 rounded-full bg-brand-strong px-6 py-3 text-sm font-bold tracking-wide text-white uppercase shadow-lg transition hover:bg-brand hover:text-primary"
                               @if (str_starts_with($banner->cta_url, 'https://')) target="_blank" rel="noopener" @endif>
                                {{ $banner->cta_label }}
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Controles debajo de la imagen (no encima): los afiches se ven completos. --}}
    @if ($banners->count() > 1)
        <div class="relative z-20 bg-cream pt-5 text-primary">
            <div class="container-content flex items-center justify-center gap-3">
                <button type="button" x-on:click="prev(); start()" class="inline-flex size-10 items-center justify-center rounded-full bg-white text-primary shadow-sm ring-1 ring-black/10 transition-colors hover:bg-primary hover:text-white" aria-label="Diapositiva anterior">
                    <x-ui.icon name="chevron-left" class="size-5" />
                </button>
                <div class="flex items-center gap-1.5">
                    @foreach ($banners as $banner)
                        <button type="button" x-on:click="go({{ $loop->index }})" class="flex size-6 items-center justify-center" aria-label="Ir a la diapositiva {{ $loop->iteration }}"
                                x-bind:aria-current="active === {{ $loop->index }} ? 'true' : 'false'">
                            <span class="block h-2 rounded-full transition-all" x-bind:class="active === {{ $loop->index }} ? 'w-7 bg-brand-strong' : 'w-2 bg-primary/30'"></span>
                        </button>
                    @endforeach
                </div>
                <button type="button" x-on:click="next(); start()" class="inline-flex size-10 items-center justify-center rounded-full bg-white text-primary shadow-sm ring-1 ring-black/10 transition-colors hover:bg-primary hover:text-white" aria-label="Diapositiva siguiente">
                    <x-ui.icon name="chevron-right" class="size-5" />
                </button>
                <button type="button" x-on:click="toggle()" class="ms-2 inline-flex size-10 items-center justify-center rounded-full bg-white text-primary shadow-sm ring-1 ring-black/10 transition-colors hover:bg-primary hover:text-white"
                        x-bind:aria-label="playing ? 'Pausar carrusel' : 'Reproducir carrusel'" aria-label="Pausar carrusel">
                    <span x-show="playing"><x-ui.icon name="pause" class="size-4" /></span>
                    <span x-show="! playing" x-cloak><x-ui.icon name="play" class="size-4" /></span>
                </button>
            </div>
        </div>
    @endif
</section>
