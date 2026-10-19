<x-layouts.public :event="$event" title="Donaciones">
    <x-ui.page-intro title="Donaciones" :eyebrow="$event->title">
        Tu aporte ayuda a hacer posible este evento.
    </x-ui.page-intro>

    <div class="container-content pb-6">
        @if ($methods->isEmpty())
            <x-ui.empty-state>Los métodos oficiales de donación se publicarán aquí en cuanto la organización los confirme.</x-ui.empty-state>
        @else
            <div class="grid gap-10 lg:grid-cols-[minmax(0,5fr)_minmax(0,7fr)] lg:items-start">
                {{-- Invitación y pasos --}}
                <div class="relative isolate overflow-hidden rounded-card bg-linear-to-br from-primary-deep via-primary to-gradient-end p-7 text-white shadow-[0_30px_60px_-30px_rgb(57_49_45/0.7)] sm:p-9 order-2 lg:order-none lg:sticky lg:top-28">
                    <div class="absolute -top-20 -right-20 -z-10 size-72 rounded-full bg-brand/25 blur-3xl" aria-hidden="true"></div>
                    <span class="inline-flex size-14 items-center justify-center rounded-full bg-white/10 text-accent-light ring-1 ring-white/15">
                        <x-ui.icon name="heart" class="size-7" />
                    </span>
                    <h2 class="mt-5 text-3xl leading-tight font-bold text-white">Sé parte de esta obra</h2>
                    <blockquote class="mt-4 border-s-2 border-accent-light ps-4 font-display text-cream/90 italic">
                        «Cada uno dé como propuso en su corazón: no con tristeza, ó por necesidad; porque Dios ama al dador alegre.»
                        <cite class="mt-1 block font-sans text-sm font-semibold not-italic text-accent-light">2 Corintios 9:7</cite>
                    </blockquote>

                    <ol class="mt-7 space-y-4">
                        @foreach ([
                            ['Elige un método', 'Escanea el código QR o copia el número de celular o de cuenta.'],
                            ['Haz tu aporte', 'Desde tu aplicación de Yape, Plin o tu banco.'],
                            ['Guarda la constancia', 'Te servirá si la organización necesita verificar tu aporte.'],
                        ] as [$title, $text])
                            <li class="flex gap-4">
                                <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-accent-light font-display font-bold text-primary-deep">{{ $loop->iteration }}</span>
                                <span>
                                    <span class="block font-semibold text-white">{{ $title }}</span>
                                    <span class="block text-sm text-cream/75">{{ $text }}</span>
                                </span>
                            </li>
                        @endforeach
                    </ol>

                    <p class="mt-7 flex items-start gap-2 border-t border-white/15 pt-5 text-sm text-cream/80">
                        <x-ui.icon name="shield" class="mt-0.5 size-4 shrink-0 text-accent-light" />
                        Aporta solo a los métodos publicados aquí. Si alguien te pide otra cuenta, confírmalo con la organización.
                    </p>
                </div>

                {{-- Métodos --}}
                <ul class="order-1 space-y-6 lg:order-none">
                    @foreach ($methods as $method)
                        @php
                            $accent = match ($method->type) {
                                \App\Enums\DonationMethodType::Yape => 'from-[#742284] to-[#9b3fb0]',
                                \App\Enums\DonationMethodType::Plin => 'from-[#00a3a6] to-[#25c2b8]',
                                default => 'from-primary to-primary-soft',
                            };
                            $fields = collect(['phone' => 'Celular', 'account_number' => 'Número de cuenta', 'cci' => 'CCI (interbancaria)'])
                                ->filter(fn ($label, $field) => filled($method->{$field}));
                        @endphp
                        <li class="overflow-hidden rounded-card bg-white shadow-[0_24px_50px_-30px_rgb(57_49_45/0.5)] ring-1 ring-black/5">
                            <div class="flex items-center justify-between gap-4 bg-linear-to-r {{ $accent }} px-6 py-4 text-white">
                                <div>
                                    @if ($method->label !== $method->type->label())
                                        <p class="text-xs font-semibold tracking-[0.14em] uppercase opacity-80">{{ $method->type->label() }}</p>
                                    @endif
                                    <h2 class="text-2xl font-bold text-white">{{ $method->label }}</h2>
                                </div>
                                <x-ui.icon :name="$method->imageUrl() ? 'qr' : 'coins'" class="size-8 opacity-80" />
                            </div>

                            <div @class(['grid gap-6 p-6 sm:p-7', 'sm:grid-cols-[13rem_1fr] sm:items-center' => $method->imageUrl()])>
                                @if ($method->imageUrl())
                                    <figure class="mx-auto w-52 sm:w-full">
                                        <img src="{{ $method->imageUrl() }}" alt="Código QR de {{ $method->label }}" width="208" height="208"
                                             class="aspect-square w-full rounded-control bg-white object-contain p-2 ring-1 ring-sand-dark" loading="lazy">
                                        <figcaption class="mt-2 text-center text-xs text-muted">Escanéalo desde tu aplicación</figcaption>
                                    </figure>
                                @endif

                                <dl class="space-y-3">
                                    @if ($method->holder_name)
                                        <div>
                                            <dt class="text-xs font-semibold tracking-wide text-muted uppercase">Titular</dt>
                                            <dd class="mt-0.5 text-lg font-semibold text-primary">{{ $method->holder_name }}</dd>
                                        </div>
                                    @endif
                                    @foreach ($fields as $field => $fieldLabel)
                                        <div x-data="{ copied: false }" class="flex items-center justify-between gap-3 rounded-control bg-cream px-4 py-3">
                                            <div class="min-w-0">
                                                <dt class="text-xs font-semibold tracking-wide text-muted uppercase">{{ $fieldLabel }}</dt>
                                                <dd class="font-display text-xl font-bold tracking-wide break-all text-primary tabular-nums">{{ $method->{$field} }}</dd>
                                            </div>
                                            <button type="button" class="inline-flex min-h-10 shrink-0 items-center gap-1.5 rounded-full bg-white px-4 text-sm font-semibold text-brand-strong ring-1 ring-sand-dark transition hover:bg-brand-strong hover:text-white"
                                                    x-on:click="navigator.clipboard?.writeText(@js($method->{$field})).then(() => { copied = true; setTimeout(() => copied = false, 2000) })"
                                                    aria-label="Copiar {{ mb_strtolower($fieldLabel) }}">
                                                <span x-show="!copied">Copiar</span>
                                                <span x-show="copied" x-cloak role="status" class="inline-flex items-center gap-1"><x-ui.icon name="check" class="size-4" /> Copiado</span>
                                            </button>
                                        </div>
                                    @endforeach
                                    @if ($method->instructions)
                                        <p class="text-sm whitespace-pre-line text-muted">{{ $method->instructions }}</p>
                                    @endif
                                </dl>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>

            @if ($event->donation_reports_enabled)
                <div class="mt-14">
                    <livewire:donation-report-form :event="$event" />
                </div>
            @endif
        @endif
    </div>
</x-layouts.public>
