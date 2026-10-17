<section aria-labelledby="registrar-aporte" class="rounded-card bg-cream p-5 sm:p-8">
    @if ($reference)
        <div class="text-center" role="status">
            <span class="mx-auto flex size-14 items-center justify-center rounded-full bg-white text-success"><x-ui.icon name="check" class="size-7" /></span>
            <h2 id="registrar-aporte" class="mt-4 text-2xl font-extrabold">¡Gracias por tu aporte!</h2>
            <p class="mt-2 text-muted">Lo registramos como <strong>por verificar</strong>. La organización lo confirmará manualmente con el estado de cuenta.</p>
            <p class="mt-5 text-sm text-muted">Tu código de referencia</p>
            <p class="font-display text-3xl font-extrabold tracking-widest text-primary tabular-nums">{{ $reference }}</p>
            <p class="mt-2 text-sm text-muted">Guárdalo por si necesitas consultar con la organización.</p>
            <p class="mt-3 text-sm">
                @if ($receiptAttached)
                    Constancia adjunta.
                @else
                    <strong>Sin constancia adjunta.</strong> Si quieres enviarla, comunícate con la organización indicando tu código.
                @endif
            </p>
            <x-ui.button variant="secondary" class="mt-6" wire:click="another">Registrar otro aporte</x-ui.button>
        </div>
    @else
        <h2 id="registrar-aporte" class="text-2xl font-extrabold">¿Ya donaste? Registra tu aporte</h2>
        <p class="mt-2 max-w-2xl text-muted">
            Así la organización puede identificarlo y verificarlo. Registrar el aporte no lo confirma: se revisa manualmente con el estado de cuenta.
        </p>

        <form wire:submit="submit" class="mt-6 grid gap-4 sm:grid-cols-2" novalidate>
            <x-admin.select label="Método que usaste" model="donation_method_id" placeholder="Elige un método"
                            :options="$methods->mapWithKeys(fn ($m) => [$m->id => $m->type->label().' · '.$m->label])->all()" class="sm:col-span-2" />
            <x-admin.input label="Monto (S/)" model="declared_amount" type="number" min="1" step="0.01" inputmode="decimal" />
            <x-admin.input label="Fecha del aporte" model="paid_on" type="date" max="{{ today()->toDateString() }}" />
            <x-admin.input label="N.º de operación" model="operation_number" class="sm:col-span-2" autocomplete="off"
                           hint="Aparece en la constancia de tu banco, Yape o Plin." />

            <div class="sm:col-span-2">
                <x-admin.input label="Foto de la constancia (opcional, JPG, PNG o WebP, máx. 4 MB)" model="receipt" type="file" accept="image/jpeg,image/png,image/webp"
                               hint="Solo la verá el equipo que verifica los aportes." />
                <div wire:loading wire:target="receipt" class="mt-1 text-sm text-muted">Subiendo imagen…</div>
            </div>

            <h3 class="pt-2 font-sans text-base font-semibold text-ink sm:col-span-2">Tus datos (opcionales: puedes aportar en anónimo)</h3>
            <x-admin.input label="Nombre" model="donor_name" autocomplete="name" />
            <x-admin.input label="Celular" model="donor_phone" type="tel" autocomplete="tel" />
            <x-admin.input label="Correo electrónico" model="donor_email" type="email" autocomplete="email" class="sm:col-span-2" />
            <x-admin.textarea label="Mensaje (opcional)" model="message" rows="2" class="sm:col-span-2" />

            <div class="sm:col-span-2">
                <label for="consent" class="inline-flex items-start gap-2 text-sm">
                    <input id="consent" type="checkbox" wire:model="consent" class="mt-0.5 size-5 shrink-0 rounded border-stone-300"
                           @error('consent') aria-invalid="true" aria-describedby="consent-error" @enderror>
                    <span>Acepto que la organización use estos datos únicamente para verificar mi aporte y comunicarse conmigo sobre él.</span>
                </label>
                @error('consent')<p id="consent-error" class="mt-1 text-sm text-danger">{{ $message }}</p>@enderror
            </div>

            <div class="sm:col-span-2">
                <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="submit,receipt">
                    <span wire:loading.remove wire:target="submit">Registrar mi aporte</span>
                    <span wire:loading wire:target="submit">Registrando…</span>
                </x-ui.button>
            </div>
        </form>
    @endif
</section>
