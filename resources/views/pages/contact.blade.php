<x-layouts.public title="Contacto">
    <x-ui.page-intro title="Contacto" eyebrow="Estamos para ayudarte">
        Comunícate con la iglesia o con la organización de la convención.
    </x-ui.page-intro>

    <div class="container-content grid gap-10 py-10 lg:grid-cols-[1fr_2fr]">
        <section aria-labelledby="datos-contacto">
            <h2 id="datos-contacto" class="text-xl font-bold">Datos de contacto</h2>

            @if ($site->hasContact())
                <ul class="mt-4 space-y-3">
                    @if ($site->whatsappUrl())
                        <li>
                            <x-ui.button href="{{ $site->whatsappUrl() }}" target="_blank" rel="noopener" class="w-full sm:w-auto">
                                Escríbenos por WhatsApp<span class="sr-only"> (se abre en una pestaña nueva)</span>
                            </x-ui.button>
                        </li>
                    @endif
                    @if ($site->contact_phone)
                        <li>
                            <span class="block text-sm text-muted">Teléfono</span>
                            <a href="tel:{{ preg_replace('/[^0-9+]/', '', $site->contact_phone) }}" class="font-semibold text-brand-strong underline-offset-4 hover:underline">{{ $site->contact_phone }}</a>
                        </li>
                    @endif
                    @if ($site->contact_email)
                        <li>
                            <span class="block text-sm text-muted">Correo electrónico</span>
                            <a href="mailto:{{ $site->contact_email }}" class="font-semibold break-all text-brand-strong underline-offset-4 hover:underline">{{ $site->contact_email }}</a>
                        </li>
                    @endif
                </ul>
            @else
                <p class="mt-4 text-muted">Los datos de contacto se publicarán aquí en cuanto la organización los confirme.</p>
            @endif

            <div class="mt-8">
                @include('pages.partials.church-location')
            </div>

            @if ($site->scheduleLines())
                <h3 class="mt-8 font-sans text-base font-semibold text-ink">Horario de cultos</h3>
                <ul class="mt-2 space-y-1">
                    @foreach ($site->scheduleLines() as $line)
                        <li>{{ $line }}</li>
                    @endforeach
                </ul>
            @endif

            @if ($site->socialLinks())
                <h3 class="mt-8 font-sans text-base font-semibold text-ink">Redes sociales</h3>
                <ul class="mt-2 flex flex-wrap gap-2">
                    @foreach ($site->socialLinks() as $network => $url)
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
