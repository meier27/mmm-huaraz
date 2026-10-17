<div>
    <x-admin.page-header title="Redes sociales" description="Se muestran como iconos en la cabecera, el menú y el pie de la web. Deja un campo vacío para ocultar esa red." />

    <form wire:submit="save" class="rounded-card bg-white shadow-card" novalidate>
        <ul class="divide-y divide-line">
            @foreach ($networks as $field => $network)
                <li class="flex flex-col gap-3 p-5 sm:flex-row sm:items-start sm:gap-4 sm:p-6">
                    <span @class(['mt-6 hidden size-11 shrink-0 items-center justify-center rounded-full sm:inline-flex', 'bg-primary text-white' => filled($form->{$field}), 'bg-sand text-primary-soft' => blank($form->{$field})])>
                        <x-ui.icon :name="$network['icon']" class="size-5" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <x-admin.input :label="$network['label']" :model="'form.'.$field" type="url" :placeholder="$network['example']" inputmode="url" />
                    </div>
                    <div class="flex items-center gap-3 sm:mt-7">
                        {{-- Solo enlaces https: nunca se pone en href lo que aún no se validó. --}}
                        @if (str_starts_with((string) $form->{$field}, 'https://') && ! $errors->has('form.'.$field))
                            <a href="{{ $form->{$field} }}" target="_blank" rel="noopener" class="inline-flex min-h-10 items-center gap-1.5 text-sm font-semibold text-brand-strong hover:underline">
                                Probar <x-ui.icon name="external" class="size-3.5" /><span class="sr-only"> el enlace de {{ $network['label'] }} (pestaña nueva)</span>
                            </a>
                            <button type="button" wire:click="clear('{{ $field }}')" class="min-h-10 text-sm font-semibold text-danger hover:underline">Quitar</button>
                        @endif
                    </div>
                </li>
            @endforeach

            <li class="flex flex-col gap-3 p-5 sm:flex-row sm:items-start sm:gap-4 sm:p-6">
                <span @class(['mt-6 hidden size-11 shrink-0 items-center justify-center rounded-full sm:inline-flex', 'bg-primary text-white' => filled($form->contact_whatsapp), 'bg-sand text-primary-soft' => blank($form->contact_whatsapp)])>
                    <x-ui.icon name="whatsapp" class="size-5" />
                </span>
                <div class="min-w-0 flex-1">
                    <x-admin.input label="WhatsApp (número)" model="form.contact_whatsapp" type="tel" placeholder="+51 987 654 321" hint="Con el código de país. El botón abre un chat con este número." />
                </div>
                <div class="flex items-center gap-3 sm:mt-7">
                    @if (filled($form->contact_whatsapp) && $whatsappUrl)
                        <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="inline-flex min-h-10 items-center gap-1.5 text-sm font-semibold text-brand-strong hover:underline">
                            Probar <x-ui.icon name="external" class="size-3.5" /><span class="sr-only"> el chat de WhatsApp (pestaña nueva)</span>
                        </a>
                    @endif
                    @if (filled($form->contact_whatsapp))
                        <button type="button" wire:click="clear('contact_whatsapp')" class="min-h-10 text-sm font-semibold text-danger hover:underline">Quitar</button>
                    @endif
                </div>
            </li>
        </ul>

        <div class="flex flex-wrap items-center gap-3 border-t border-line p-5 sm:p-6">
            <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save">Guardar</span>
                <span wire:loading wire:target="save">Guardando…</span>
            </x-ui.button>
            <p class="text-sm text-muted">Usa solo cuentas oficiales de la iglesia o del movimiento.</p>
        </div>
    </form>
</div>
