<div>
    <x-admin.page-header title="Datos del sitio" description="Identidad, textos institucionales y contacto del MMM Huaraz. Aparecen en toda la web." />

    <x-admin.form-panel title="Identidad" :cancel="false">
        <x-admin.input label="Nombre de la iglesia" model="form.church_name" class="sm:col-span-2" />
        <x-admin.input label="Frase de presentación (opcional)" model="form.tagline" class="sm:col-span-2" hint="Una línea bajo el nombre en la portada. Usa solo textos aprobados por la iglesia." />
        <x-admin.textarea label="Bienvenida (opcional)" model="form.welcome_text" rows="4" class="sm:col-span-2" hint="Texto de bienvenida de la portada." />

        <p class="text-sm text-muted sm:col-span-2">
            Los textos de «Nosotros» se editan en
            <a href="{{ route('admin.about') }}" wire:navigate class="font-semibold text-brand-strong underline">Página «Nosotros»</a>.
        </p>
        <x-admin.textarea label="Horario de cultos" model="form.service_schedule" rows="4" class="sm:col-span-2" hint="Una línea por culto, p. ej. «Domingo · 10:00 a. m. · Escuela dominical»." />

        <h3 class="pt-2 font-display font-bold text-primary sm:col-span-2">Contacto</h3>
        <x-admin.input label="Teléfono" model="form.contact_phone" type="tel" />
        <x-admin.input label="Correo electrónico" model="form.contact_email" type="email" />

        <h3 class="pt-2 font-display font-bold text-primary sm:col-span-2">Dirección del templo</h3>
        <x-admin.input label="Dirección" model="form.address" class="sm:col-span-2" hint="Por ejemplo, «Av. Luzuriaga 234, Huaraz». Se muestra en Visítanos, Contacto y el pie, con su mapa." />
        <x-admin.input label="Enlace de Google Maps (opcional)" model="form.maps_link" type="url" class="sm:col-span-2"
                       hint="Para marcar el punto exacto: en Google Maps busca el templo › Compartir › Copiar enlace, y pégalo aquí. Se guardan sus coordenadas." />
        <x-admin.input label="Latitud (opcional)" model="form.latitude" inputmode="decimal" />
        <x-admin.input label="Longitud (opcional)" model="form.longitude" inputmode="decimal" />
        <p class="text-sm text-muted sm:col-span-2">
            WhatsApp, Facebook, Instagram, YouTube y TikTok se editan en
            <a href="{{ route('admin.social') }}" wire:navigate class="font-semibold text-brand-strong underline">Redes sociales</a>.
        </p>

        <h3 class="pt-2 font-display font-bold text-primary sm:col-span-2">Biblia y Bethel TV</h3>
        <x-admin.select label="Versión de la Biblia" model="form.bible_version" :options="$versions" />
        <x-admin.input label="Canal de YouTube de Bethel TV (id)" model="form.bethel_channel_id" hint="Vacío = canal oficial @Betheloficial." />
        <p class="text-xs text-muted sm:col-span-2">
            La Reina-Valera 1909 es de dominio público. Las otras versiones tienen derechos reservados: úsalas solo si la iglesia cuenta con autorización.
        </p>
    </x-admin.form-panel>
</div>
