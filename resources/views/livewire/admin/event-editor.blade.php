<div>
    <x-admin.event-tabs :event="$this->event" />

    <x-admin.form-panel title="Datos del evento" :cancel="false">
        <x-admin.input label="Título" model="form.title" class="sm:col-span-2" />
        <x-admin.input label="Lema o tema (opcional)" model="form.theme" hint="Solo textos aprobados por la organización." />
        <x-admin.input label="Organiza (opcional)" model="form.organization" />
        <x-admin.input label="Fecha de inicio (opcional)" model="form.starts_on" type="date" hint="Si la dejas vacía, el evento se muestra sin fecha." />
        <x-admin.input label="Fecha de fin (opcional)" model="form.ends_on" type="date" />
        <x-admin.input label="Horario (opcional)" model="form.time_label" hint="Por ejemplo, «7:00 p. m.»." />
        <x-admin.input label="Ciudad (opcional)" model="form.city" />
        <x-admin.input label="Lugar (opcional)" model="form.location" class="sm:col-span-2" hint="Si activas «Sedes», se muestran las sedes publicadas." />
        <x-admin.textarea label="Resumen (opcional)" model="form.summary" rows="2" class="sm:col-span-2" />
        <x-admin.textarea label="Descripción (opcional)" model="form.body" rows="6" class="sm:col-span-2" hint="Admite Markdown sencillo. En eventos con módulos aparece como bienvenida." />
        <x-admin.input label="Enlace externo (opcional)" model="form.external_url" type="url" class="sm:col-span-2" hint="Debe empezar por https://" />

        <div class="sm:col-span-2">
            <x-admin.input label="Imagen (opcional, JPG, PNG o WebP, máx. 4 MB)" model="form.image" type="file" accept="image/jpeg,image/png,image/webp" />
            <div wire:loading wire:target="form.image" class="mt-1 text-sm text-muted">Subiendo imagen…</div>
            @if ($this->event->imageUrl() && ! $form->image)
                <div class="mt-3 flex items-center gap-3">
                    <img src="{{ $this->event->imageUrl() }}" alt="" class="h-16 w-28 rounded-control object-cover" width="112" height="64">
                    <x-ui.button variant="ghost" class="text-sm text-danger!" wire:click="removeImage" wire:confirm="¿Quitar la imagen actual?">Quitar imagen</x-ui.button>
                </div>
            @endif
        </div>
        <x-admin.input label="Texto alternativo de la imagen" model="form.image_alt" class="sm:col-span-2" />

        {{-- Portada del evento --}}
        <fieldset class="rounded-card border border-line p-4 sm:col-span-2">
            <legend class="px-1 font-display font-bold text-primary">Portada del evento</legend>
            <p class="text-sm text-muted">La imagen de arriba se usa como fondo de la portada (recomendado 1920 × 900 px). Todo lo de aquí es opcional.</p>
            <div class="mt-3 grid gap-4 sm:grid-cols-2">
                <x-admin.input label="Texto bajo el título (opcional)" model="form.hero_text" class="sm:col-span-2" hint="Una frase breve, p. ej. «Te esperamos con tu familia»." />
                <x-admin.input label="Texto del botón (opcional)" model="form.hero_button_label" hint="Por ejemplo, «Inscríbete»." />
                <x-admin.input label="Enlace del botón" model="form.hero_button_url" hint="Ruta interna (/eventos/…) o https://…" />
                <div class="sm:col-span-2">
                    <p class="text-sm font-medium">Qué mostrar sobre la imagen</p>
                    <div class="mt-1 flex flex-wrap gap-x-8">
                        <x-admin.checkbox label="Título y lema" model="form.hero_show_title" />
                        <x-admin.checkbox label="Botones (programa, cómo llegar)" model="form.hero_show_buttons" />
                    </div>
                    <p class="mt-1 text-xs text-muted">Si la imagen ya es un afiche con su propio texto, desmarca lo que sobre: se verá limpia.</p>
                </div>
            </div>
        </fieldset>

        <div class="flex flex-wrap gap-x-8 sm:col-span-2">
            <x-admin.checkbox label="Publicado" model="form.is_published" />
            <x-admin.checkbox label="Destacar en la portada" model="form.is_featured" />
        </div>
        <p class="-mt-3 text-xs text-muted sm:col-span-2">Solo hay un evento destacado: al marcar este, se desmarca el anterior.</p>

        {{-- Módulos --}}
        <fieldset class="rounded-card border border-line p-4 sm:col-span-2">
            <legend class="px-1 font-display font-bold text-primary">Módulos del evento</legend>
            <p class="text-sm text-muted">Activa solo lo que este evento necesita. Cada módulo añade una pestaña aquí y una sección en la web.</p>
            <div class="mt-3 grid gap-x-6 sm:grid-cols-3">
                @foreach ($modules as $key => $module)
                    <x-admin.checkbox :label="$module['label']" :model="'form.'.$module['column']" :live="$key === 'donations'" />
                @endforeach
            </div>
            <p class="mt-2 text-xs text-muted">Los métodos de donación son generales de la iglesia: se gestionan en «Métodos de donación».</p>
            @if ($form->has_donations)
                <div class="mt-3 border-t border-line pt-3">
                    <x-admin.checkbox label="Permitir que los donantes registren su aporte (formulario con n.º de operación y constancia)" model="form.donation_reports_enabled" />
                    <p class="mt-1 text-xs text-muted">Desmarcado, la página de donaciones solo muestra los métodos y sus datos.</p>
                </div>
            @endif
        </fieldset>

    </x-admin.form-panel>
</div>
