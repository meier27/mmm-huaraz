<x-layouts.public title="Donaciones">
    <x-ui.page-intro title="Donaciones" eyebrow="Apoya la convención">
        Tu aporte ayuda a hacer posible la convención.
    </x-ui.page-intro>

    <div class="container-content py-10">
        <x-ui.alert type="warning" class="mb-8 max-w-3xl">
            Utiliza únicamente los métodos publicados en esta página. Si alguien te pide aportar a otra cuenta, confírmalo antes con la organización.
        </x-ui.alert>

        @if ($methods->isEmpty())
            <x-ui.empty-state>Los métodos oficiales de donación se publicarán aquí en cuanto la organización los confirme.</x-ui.empty-state>
        @else
            <ul class="grid gap-6 md:grid-cols-2">
                @foreach ($methods as $method)
                    <li class="flex flex-col gap-4 rounded-card border border-line bg-white p-5 sm:flex-row sm:p-6">
                        @if ($method->imageUrl())
                            <img src="{{ $method->imageUrl() }}" alt="Código QR de {{ $method->label }}" width="160" height="160"
                                 class="size-40 shrink-0 self-center rounded-control border border-line object-contain" loading="lazy">
                        @endif
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-brand-strong">{{ $method->type->label() }}</p>
                            <h2 class="mt-1 text-xl font-bold">{{ $method->label }}</h2>
                            <dl class="mt-3 space-y-2 text-sm">
                                @foreach (['holder_name' => 'Titular', 'account_number' => 'Cuenta', 'cci' => 'CCI', 'phone' => 'Celular'] as $field => $fieldLabel)
                                    @if ($method->{$field})
                                        <div x-data="{ copied: false }">
                                            <dt class="text-muted">{{ $fieldLabel }}</dt>
                                            <dd class="flex flex-wrap items-center gap-2 font-semibold break-all">
                                                <span class="tabular-nums">{{ $method->{$field} }}</span>
                                                @if ($field !== 'holder_name')
                                                    <button type="button" class="rounded-control px-2 py-1 text-xs font-medium text-brand-strong hover:bg-cream"
                                                            x-on:click="navigator.clipboard?.writeText(@js($method->{$field})).then(() => { copied = true; setTimeout(() => copied = false, 2000) })">
                                                        <span x-show="!copied">Copiar</span>
                                                        <span x-show="copied" x-cloak role="status">Copiado</span>
                                                    </button>
                                                @endif
                                            </dd>
                                        </div>
                                    @endif
                                @endforeach
                            </dl>
                            @if ($method->instructions)
                                <p class="mt-3 text-sm text-muted whitespace-pre-line">{{ $method->instructions }}</p>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</x-layouts.public>
