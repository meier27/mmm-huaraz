<x-layouts.public title="Contacto">
    <x-ui.page-intro title="Contacto" eyebrow="Estamos para ayudarte">
        Comunícate con la organización de la convención.
    </x-ui.page-intro>

    <div class="container-content grid gap-10 py-10 lg:grid-cols-[1fr_2fr]">
        <section aria-labelledby="datos-contacto">
            <h2 id="datos-contacto" class="text-xl font-bold">Datos de contacto</h2>

            @if ($settings->contact_phone || $settings->whatsappUrl() || $settings->contact_email)
                <ul class="mt-4 space-y-3">
                    @if ($settings->whatsappUrl())
                        <li>
                            <x-ui.button href="{{ $settings->whatsappUrl() }}" target="_blank" rel="noopener" class="w-full sm:w-auto">
                                Escríbenos por WhatsApp<span class="sr-only"> (se abre en una pestaña nueva)</span>
                            </x-ui.button>
                        </li>
                    @endif
                    @if ($settings->contact_phone)
                        <li>
                            <span class="block text-sm text-muted">Teléfono</span>
                            <a href="tel:{{ preg_replace('/[^0-9+]/', '', $settings->contact_phone) }}" class="font-semibold text-brand-strong underline-offset-4 hover:underline">{{ $settings->contact_phone }}</a>
                        </li>
                    @endif
                    @if ($settings->contact_email)
                        <li>
                            <span class="block text-sm text-muted">Correo electrónico</span>
                            <a href="mailto:{{ $settings->contact_email }}" class="font-semibold break-all text-brand-strong underline-offset-4 hover:underline">{{ $settings->contact_email }}</a>
                        </li>
                    @endif
                </ul>
            @else
                <p class="mt-4 text-muted">Los datos de contacto se publicarán aquí en cuanto la organización los confirme.</p>
            @endif

            @if ($settings->socialLinks())
                <h3 class="mt-8 font-sans text-base font-semibold text-ink">Redes sociales</h3>
                <ul class="mt-2 flex flex-wrap gap-2">
                    @foreach ($settings->socialLinks() as $network => $url)
                        <li><x-ui.button href="{{ $url }}" variant="secondary" target="_blank" rel="noopener">{{ $network }}</x-ui.button></li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section aria-labelledby="faq">
            <h2 id="faq" class="text-xl font-bold">Preguntas frecuentes</h2>
            <div class="mt-4">
                @if ($faqs->isEmpty())
                    <x-ui.empty-state>Aún no hay preguntas frecuentes publicadas.</x-ui.empty-state>
                @else
                    @include('pages.partials.faq-list', ['faqs' => $faqs])
                @endif
            </div>
        </section>
    </div>
</x-layouts.public>
