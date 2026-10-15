<div>
    <x-admin.page-header title="Datos de la convención" description="Información general y contactos que aparecen en toda la web." />

    <x-admin.form-panel title="Información general" :cancel="false">
        <x-admin.input label="Organización" model="form.organization" />
        <x-admin.input label="Nombre del evento" model="form.name" />
        <x-admin.input label="Tema" model="form.theme" />
        <x-admin.input label="Ciudad" model="form.city" />
        <x-admin.input label="Fecha de inicio" model="form.starts_on" type="date" />
        <x-admin.input label="Fecha de fin" model="form.ends_on" type="date" />
        <div class="sm:col-span-2">
            <x-admin.textarea label="Presentación (opcional)" model="form.summary" rows="4" hint="Texto breve de bienvenida para la página de inicio." />
        </div>

        <h3 class="pt-2 font-display font-bold text-earth sm:col-span-2">Contacto</h3>
        <x-admin.input label="Teléfono" model="form.contact_phone" type="tel" />
        <x-admin.input label="WhatsApp" model="form.contact_whatsapp" type="tel" hint="Con código de país, p. ej. +51 …" />
        <x-admin.input label="Correo electrónico" model="form.contact_email" type="email" class="sm:col-span-2" />

        <h3 class="pt-2 font-display font-bold text-earth sm:col-span-2">Redes sociales (enlaces https)</h3>
        <x-admin.input label="Facebook" model="form.facebook_url" type="url" />
        <x-admin.input label="Instagram" model="form.instagram_url" type="url" />
        <x-admin.input label="YouTube" model="form.youtube_url" type="url" />
        <x-admin.input label="TikTok" model="form.tiktok_url" type="url" />
    </x-admin.form-panel>
</div>
