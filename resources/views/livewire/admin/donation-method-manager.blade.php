<div>
    <x-admin.page-header title="Métodos de donación" description="Registra únicamente cuentas y códigos QR oficiales proporcionados por la organización.">
        <x-slot:actions>
            <x-ui.button wire:click="create">Nuevo método</x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    <x-ui.alert type="warning" class="mb-6">
        Verifica cada dato con la tesorería antes de publicarlo. Un error en una cuenta puede desviar aportes.
    </x-ui.alert>

    @if ($showForm)
        <x-admin.form-panel :title="$editingId ? 'Editar método' : 'Nuevo método'">
            <x-admin.select label="Tipo" model="form.type" :live="true" :options="collect($types)->mapWithKeys(fn ($t) => [$t->value => $t->label()])" />
            <x-admin.input :label="$form->type === 'bank_transfer' ? 'Banco' : 'Nombre visible'" model="form.label"
                           :hint="$form->type === 'bank_transfer' ? 'Por ejemplo, «BCP» o «Interbank».' : 'Se muestra como título de la tarjeta.'" />

            @if (in_array('holder_name', $fields, true))
                <x-admin.input label="Titular" model="form.holder_name" :class="in_array('phone', $fields, true) ? '' : 'sm:col-span-2'" />
            @endif
            @if (in_array('phone', $fields, true))
                <x-admin.input :label="'Celular'.(in_array($form->type, ['yape', 'plin'], true) ? ' de '.ucfirst($form->type) : '')" model="form.phone" type="tel" />
            @endif
            @if (in_array('account_number', $fields, true))
                <x-admin.input label="Número de cuenta" model="form.account_number" inputmode="numeric" />
            @endif
            @if (in_array('cci', $fields, true))
                <x-admin.input label="CCI (cuenta interbancaria)" model="form.cci" inputmode="numeric" hint="20 dígitos, para transferir desde otros bancos." />
            @endif
            @if (in_array('instructions', $fields, true))
                <div class="sm:col-span-2">
                    <x-admin.textarea label="Instrucciones" model="form.instructions" rows="3" />
                </div>
            @endif

            @if (in_array('qr', $fields, true))
                <div class="sm:col-span-2">
                    <x-admin.input label="Imagen del QR oficial (JPG, PNG o WebP, máx. 2 MB)" model="form.qr_image" type="file" accept="image/jpeg,image/png,image/webp" />
                    <div wire:loading wire:target="form.qr_image" class="mt-1 text-sm text-muted">Subiendo imagen…</div>
                    @if ($editing?->imageUrl() && ! $form->qr_image)
                        <div class="mt-3 flex items-center gap-3">
                            <img src="{{ $editing->imageUrl() }}" alt="" class="size-20 rounded-control object-contain" width="80" height="80">
                            <x-ui.button variant="ghost" class="text-sm text-danger!" wire:click="removeImage({{ $editing->id }})" wire:confirm="¿Quitar el código QR actual?">Quitar QR</x-ui.button>
                        </div>
                    @endif
                </div>
            @endif

            <x-admin.input label="Orden" model="form.sort_order" type="number" min="0" />
            <x-admin.checkbox label="Publicado" model="form.is_published" />
        </x-admin.form-panel>
    @endif

    <ul class="divide-y divide-line rounded-card bg-white shadow-card">
        @forelse ($records as $record)
            <li wire:key="donation-{{ $record->id }}" class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <p class="font-semibold">{{ $record->label }} <span class="text-sm font-normal text-muted">· {{ $record->type->label() }}</span></p>
                    <p class="mt-1 text-sm text-muted">{{ collect([$record->holder_name, $record->account_number, $record->phone])->filter()->join(' · ') ?: 'Sin datos' }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <x-admin.publish-toggle :record="$record" />
                    <x-admin.row-actions :id="$record->id" />
                </div>
            </li>
        @empty
            <li class="p-6 text-center text-muted">Aún no hay métodos de donación registrados.</li>
        @endforelse
    </ul>
</div>
