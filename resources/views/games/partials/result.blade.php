{{--
    Final del nivel (lo decide el servidor): estrellas, si se guardó, premios
    alcanzados y los botones para seguir. $summary: expresión de Alpine.
--}}
<div x-show="finished && outcome" x-cloak class="game-pop relative isolate overflow-hidden rounded-card bg-linear-to-br from-primary-deep via-primary to-gradient-end px-6 py-10 text-center text-white shadow-[0_30px_60px_-30px_rgb(57_49_45/0.8)]"
     role="status">
    <span class="absolute -top-16 -right-10 -z-10 size-56 rounded-full bg-brand/30 blur-3xl" aria-hidden="true"></span>
    <x-ui.mountains class="absolute inset-x-0 bottom-0 -z-10 h-16 text-white/5" />

    <template x-if="outcome">
        <div>
            <div class="flex justify-center gap-2" :aria-label="`${outcome.stars} de 3 estrellas`">
                <template x-for="s in 3" :key="s">
                    <span class="game-pop" :style="`animation-delay: ${s * 120}ms`">
                        <x-ui.icon name="star" class="size-12 sm:size-14" x-bind:class="s <= outcome.stars ? 'fill-accent-light text-accent-light' : 'text-white/25'" />
                    </span>
                </template>
            </div>
            <h2 class="mt-4 text-3xl font-bold text-white" x-text="outcome.stars === 3 ? '¡Perfecto!' : (outcome.stars > 0 ? '¡Nivel superado!' : 'Casi lo logras')"></h2>
            <p class="mt-2 text-lg text-cream/90" x-text="{{ $summary }}"></p>
            <p class="mt-1 text-sm text-cream/70" x-text="`Tiempo: ${clock(outcome.seconds)}`"></p>

            <p x-show="outcome.points" class="mt-4 inline-flex items-center gap-2 rounded-full bg-accent-light px-4 py-1.5 text-sm font-bold text-primary-deep">
                <x-ui.icon name="star" class="size-4" /> <span x-text="`+${outcome.points} puntos esta semana`"></span>
            </p>
            <p x-show="outcome.saved" class="mt-4 inline-flex items-center gap-2 rounded-full bg-white/15 px-4 py-1.5 text-sm font-semibold">
                <x-ui.icon name="check" class="size-4" /> Guardado en tu avance
            </p>
            <template x-if="outcome.prizes.length">
                <a :href="outcome.account + '#premios'" class="mx-auto mt-4 flex max-w-md items-center gap-3 rounded-control bg-accent-light px-4 py-3 text-left text-primary-deep shadow-lg">
                    <x-ui.icon name="trophy" class="size-8 shrink-0" />
                    <span><span class="block font-bold">¡Alcanzaste un premio!</span><span class="text-sm" x-text="outcome.prizes.join(', ') + '. Reclámalo en Mi avance.'"></span></span>
                </a>
            </template>
            <template x-if="outcome.guest">
                <p class="mx-auto mt-4 max-w-md rounded-control bg-white/10 px-4 py-3 text-sm">
                    Sin cuenta, este nivel no se guarda.
                    <a :href="outcome.register" class="font-bold text-accent-light underline">Crea tu cuenta</a> para guardar tu avance, jugar los 30 niveles y ganar premios.
                </p>
            </template>

            <div class="mt-6 flex flex-wrap justify-center gap-3">
                <template x-if="outcome.next">
                    <a :href="outcome.next" x-ref="nextLevel" class="inline-flex min-h-12 items-center gap-2 rounded-full bg-accent-light px-6 font-bold text-primary-deep transition hover:bg-white">
                        Siguiente nivel <x-ui.icon name="arrow-right" class="size-5" />
                    </a>
                </template>
                <a :href="outcome.retry" class="inline-flex min-h-12 items-center gap-2 rounded-full bg-white px-6 font-bold text-primary-deep transition hover:bg-accent-light">
                    <x-ui.icon name="refresh" class="size-5" /> <span x-text="outcome.stars === 3 ? 'Jugar de nuevo' : 'Intentar otra vez'"></span>
                </a>
                <a :href="outcome.map" class="inline-flex min-h-12 items-center gap-2 rounded-full px-6 font-bold text-white ring-1 ring-white/40 transition hover:bg-white/10">
                    Niveles
                </a>
            </div>
        </div>
    </template>
</div>
