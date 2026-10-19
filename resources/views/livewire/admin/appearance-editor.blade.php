@php
    $colorFields = [
        'color_primary' => ['Color principal', 'Cabecera, pie, títulos, menú y cajas oscuras (como el total del pedido). Lleva texto blanco encima.'],
        'color_accent' => ['Color de acento', 'Botón de menú, iconos, etiquetas y detalles destacados.'],
        'color_button' => ['Color de botones', 'Botones con texto blanco, enlaces y el borde del foco del teclado.'],
        'color_background' => ['Color de fondo', 'Fondo claro de las secciones (crema).'],
        'color_gradient_end' => ['Color final del degradado', 'Las tarjetas destacadas (próximo evento, «Sé parte de esta obra»…) van del color principal a este. Lleva texto blanco encima.'],
    ];
    // Valor efectivo: lo elegido o, si está vacío, el de diseño.
    $value = fn (string $field) => \App\Support\Theme::validHex($form->{$field}) ? $form->{$field} : $defaultColors[$field];
    $font = fn (string $field) => $fonts[isset($fonts[$form->{$field}]) ? $form->{$field} : $defaultFonts[$field]]['stack'];
    // Vista previa: mismas variables que App\Support\Theme, aplicadas solo a este recuadro.
    $previewVars = '--color-primary:'.$value('color_primary').';--color-primary-deep:'.$value('color_primary')
        .';--color-primary-soft:color-mix(in srgb, '.$value('color_primary').' 73%, white)'
        .';--color-accent-light:'.$value('color_accent').';--color-brand:'.$value('color_accent')
        .';--color-brand-strong:'.$value('color_button')
        .';--color-cream:'.$value('color_background').';--color-gradient-end:'.$value('color_gradient_end')
        .";--font-sans:{$font('font_body')};--font-display:{$font('font_heading')}";
@endphp

<div>
    <x-admin.page-header title="Apariencia" description="Colores principales y fuentes de toda la web y del panel. Deja un campo vacío para usar el valor de diseño." />

    <div class="grid gap-6 xl:grid-cols-[1fr_24rem] xl:items-start">
        <form wire:submit="save" class="rounded-card bg-white shadow-card" novalidate>
            <fieldset class="p-5 sm:p-6">
                <legend class="font-display text-lg font-bold text-primary">Colores</legend>
                <ul class="mt-4 space-y-5">
                    @foreach ($colorFields as $field => [$label, $help])
                        <li class="grid gap-3 sm:grid-cols-[1fr_auto] sm:items-start">
                            <div>
                                <label for="form-{{ $field }}" class="block font-semibold">{{ $label }}</label>
                                <p class="text-sm text-muted">{{ $help }} <span class="whitespace-nowrap">Diseño: <span class="font-mono">{{ $defaultColors[$field] }}</span></span></p>
                                @error('form.'.$field)<p id="form-{{ $field }}-error" class="mt-1 text-sm text-danger">{{ $message }}</p>@enderror
                            </div>
                            <div class="flex items-center gap-2">
                                {{-- Sin wire:model: vacío mostraría negro; enseña el color efectivo. --}}
                                <input type="color" value="{{ $value($field) }}" x-on:change="$wire.set('form.{{ $field }}', $event.target.value)" aria-label="Elegir {{ mb_strtolower($label) }}"
                                       wire:key="picker-{{ $field }}-{{ $value($field) }}"
                                       class="h-11 w-14 cursor-pointer rounded-control border border-stone-300 bg-white p-1">
                                <input id="form-{{ $field }}" type="text" wire:model.live.debounce.500ms="form.{{ $field }}" placeholder="{{ $defaultColors[$field] }}" maxlength="7" spellcheck="false"
                                       @error('form.'.$field) aria-invalid="true" aria-describedby="form-{{ $field }}-error" @enderror
                                       class="block min-h-11 w-28 rounded-control border border-stone-300 px-3 font-mono uppercase focus:border-brand-strong">
                            </div>
                        </li>
                    @endforeach
                </ul>
            </fieldset>

            <fieldset class="border-t border-line p-5 sm:p-6">
                <legend class="font-display text-lg font-bold text-primary">Fuentes</legend>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    @foreach (['font_body' => 'Fuente del texto', 'font_heading' => 'Fuente de los títulos'] as $field => $label)
                        <x-admin.select :label="$label" :model="'form.'.$field" :live="true"
                                        :placeholder="'Diseño ('.$fonts[$defaultFonts[$field]]['label'].')'"
                                        :options="collect($fonts)->map(fn ($f) => $f['label'])->all()" />
                    @endforeach
                </div>
                <p class="mt-2 text-xs text-muted">Todas se sirven desde el propio sitio: no se envían datos de los visitantes a terceros.</p>
            </fieldset>

            <div class="flex flex-wrap items-center gap-3 border-t border-line p-5 sm:p-6">
                <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="save">
                    <span wire:loading.remove wire:target="save">Guardar</span>
                    <span wire:loading wire:target="save">Guardando…</span>
                </x-ui.button>
                <x-ui.button type="button" variant="secondary" wire:click="restoreDefaults">Restaurar diseño</x-ui.button>
                <p class="text-sm text-muted">Los cambios se ven en toda la web al guardar.</p>
            </div>
        </form>

        {{-- Vista previa en vivo (solo este recuadro) --}}
        <aside class="xl:sticky xl:top-6" aria-labelledby="vista-previa">
            <h2 id="vista-previa" class="mb-2 font-sans text-sm font-semibold tracking-wide text-muted uppercase">Vista previa</h2>
            <div style="{{ $previewVars }}" class="overflow-hidden rounded-card font-sans shadow-card ring-1 ring-line">
                <div class="flex items-center justify-between bg-primary-deep px-4 py-3 text-white">
                    <span class="font-display text-lg font-bold text-white">Huaraz</span>
                    <span class="inline-flex size-10 items-center justify-center rounded-full bg-accent-light text-primary-deep"><x-ui.icon name="menu" class="size-5" /></span>
                </div>
                <div class="space-y-3 bg-cream p-4">
                    <p class="text-xs font-bold tracking-[0.16em] text-brand-strong uppercase">Próximo evento</p>
                    <p class="font-display text-2xl leading-tight font-bold text-primary">Convención Nacional <span class="text-brand-strong">· Huaraz</span></p>
                    <p class="text-sm text-ink/80">Así se ve el texto de los párrafos con la fuente elegida.</p>
                    <div class="rounded-control bg-linear-to-br from-primary-deep via-primary to-gradient-end p-3 text-white">
                        <div class="flex justify-between text-sm"><span class="text-cream/80">Total del pedido</span><span class="font-display text-lg font-extrabold">S/ 18.00</span></div>
                        <div class="flex justify-between text-sm"><span class="text-cream/80">Adelanto</span><span class="font-bold text-accent-light">S/ 5.00</span></div>
                    </div>
                    <span class="inline-flex min-h-11 items-center rounded-full bg-brand-strong px-5 text-sm font-semibold text-white">Enviar reserva</span>
                </div>
            </div>
        </aside>
    </div>
</div>
