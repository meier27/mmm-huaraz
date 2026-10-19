<div>
    <x-ui.page-intro :title="$event->reservationsTitle()" :eyebrow="$event->title">
        Platos preparados por los hermanos de la iglesia{{ $event->city ? ' de '.$event->city : '' }}. Elige tus platos y horario, paga el adelanto y recoge tu pedido con tu código de reserva. Lo recaudado apoya la obra.
    </x-ui.page-intro>

    <div class="container-content py-10">
        @if (! $event->reservations_open)
            <x-ui.empty-state>Las reservas aún no están abiertas. Vuelve pronto: publicaremos aquí el menú en cuanto esté confirmado.</x-ui.empty-state>
        @elseif ($groups->isEmpty())
            <x-ui.empty-state>No hay platos disponibles para reservar en este momento.</x-ui.empty-state>
        @else
            @php
                // Adelanto que exige el evento (el servidor lo vuelve a calcular al aceptar la captura).
                $deposit = ['fixed' => $event->hasFixedDeposit() ? (float) $event->deposit_amount : null, 'percent' => $event->deposit_min_percent];
            @endphp
            <form wire:submit="submit" novalidate
                  x-data="{
                      prices: @js($prices),
                      deposit: @js($deposit),
                      get total() { return Object.entries($wire.quantities).reduce((sum, [id, qty]) => sum + (this.prices[id] ?? 0) * (parseInt(qty) || 0), 0) },
                      get portions() { return Object.values($wire.quantities).reduce((sum, qty) => sum + (parseInt(qty) || 0), 0) },
                      // Barra inferior (solo móvil) mientras se eligen platos: el formulario queda más abajo.
                      inPicker: false,
                      get advance() {
                          if (this.deposit.fixed) return this.total > 0 ? Math.min(this.deposit.fixed, this.total) : this.deposit.fixed;
                          if (this.deposit.percent) return Math.ceil(this.total * this.deposit.percent) / 100;
                          return null;
                      },
                  }"
                  class="grid grid-cols-1 gap-8 lg:grid-cols-[1fr_24rem] lg:items-start">

                <div class="min-w-0 space-y-10" x-intersect:enter="inPicker = true" x-intersect:leave="inPicker = false">
                    @error('quantities')
                        <x-ui.alert type="error">{{ $message }}</x-ui.alert>
                    @enderror

                    @include("livewire.partials.offering-picker")
                </div>

                <div x-show="inPicker && total > 0" x-cloak
                     x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
                     x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full"
                     class="fixed inset-x-0 bottom-0 z-30 border-t border-white/10 bg-primary-deep px-4 pt-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] text-white shadow-[0_-12px_30px_-12px_rgb(0_0_0/0.5)] lg:hidden">
                    <div class="mx-auto flex max-w-xl items-center justify-between gap-3">
                        <p class="leading-tight">
                            <span class="block text-xs text-cream/80" x-text="portions === 1 ? '1 plato elegido' : portions + ' platos elegidos'"></span>
                            <span class="font-display text-xl font-extrabold tabular-nums" x-text="'S/ ' + total.toFixed(2)"></span>
                        </p>
                        <button type="button" x-on:click="document.getElementById('paso-datos').scrollIntoView({ behavior: 'smooth', block: 'start' })"
                                class="inline-flex min-h-12 items-center gap-2 rounded-full bg-accent-light px-5 font-bold text-primary-deep shadow-lg">
                            Continuar <x-ui.icon name="arrow-right" class="size-5" />
                        </button>
                    </div>
                </div>

                <aside class="overflow-hidden rounded-card bg-white shadow-card lg:sticky lg:top-24" aria-label="Completa tu reserva">
                    {{-- Resumen del importe --}}
                    <div class="bg-primary-deep px-5 py-4 text-white">
                        <div class="flex items-baseline justify-between gap-3">
                            <span class="text-sm text-cream/80">Total del pedido</span>
                            <span class="font-display text-2xl font-extrabold text-white tabular-nums" aria-live="polite">S/ <span x-text="total.toFixed(2)">0.00</span></span>
                        </div>
                        <div class="mt-1 flex items-baseline justify-between gap-3 text-sm">
                            <span class="text-cream/80">Adelanto para reservar</span>
                            <span class="font-bold text-accent-light tabular-nums" x-text="advance === null ? 'Según instrucciones' : 'S/ ' + advance.toFixed(2)"></span>
                        </div>
                        <p class="mt-1 text-xs text-cream/70">El saldo lo pagas al recoger tu pedido.</p>
                    </div>

                    <div class="divide-y divide-line">
                        {{-- 1. Datos --}}
                        <section class="p-5" aria-labelledby="paso-datos">
                            <h2 id="paso-datos" class="flex scroll-mt-48 items-center gap-2 text-lg font-bold">
                                <span class="inline-flex size-7 items-center justify-center rounded-full bg-brand-strong text-sm text-white" aria-hidden="true">1</span> Tus datos
                            </h2>
                            <div class="mt-4 space-y-4">
                                <div>
                                    <label for="customer_name" class="block text-sm font-medium">Nombre y apellido</label>
                                    <input id="customer_name" type="text" wire:model="customer_name" autocomplete="name" required
                                           @error('customer_name') aria-invalid="true" aria-describedby="customer_name-error" @enderror
                                           class="mt-1 block min-h-11 w-full rounded-control border border-stone-300 px-3 py-2 focus:border-brand-strong">
                                    @error('customer_name')<p id="customer_name-error" class="mt-1 text-sm text-danger">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label for="customer_phone" class="block text-sm font-medium">Celular</label>
                                    <input id="customer_phone" type="tel" wire:model="customer_phone" autocomplete="tel" inputmode="tel" required
                                           @error('customer_phone') aria-invalid="true" aria-describedby="customer_phone-error" @enderror
                                           class="mt-1 block min-h-11 w-full rounded-control border border-stone-300 px-3 py-2 focus:border-brand-strong">
                                    @error('customer_phone')<p id="customer_phone-error" class="mt-1 text-sm text-danger">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label for="notes" class="block text-sm font-medium">Observaciones (opcional)</label>
                                    <textarea id="notes" rows="2" wire:model="notes" placeholder="Ej.: Listo para la 1:00 p. m., sin ají" class="mt-1 block w-full rounded-control border border-stone-300 px-3 py-2 placeholder:text-stone-400 focus:border-brand-strong"></textarea>
                                    @error('notes')<p class="mt-1 text-sm text-danger">{{ $message }}</p>@enderror
                                </div>
                            </div>
                        </section>

                        {{-- 2. Pago del adelanto: solo los métodos oficiales publicados en el panel --}}
                        <section class="p-5" aria-labelledby="paso-pago">
                            <h2 id="paso-pago" class="flex items-center gap-2 text-lg font-bold">
                                <span class="inline-flex size-7 items-center justify-center rounded-full bg-brand-strong text-sm text-white" aria-hidden="true">2</span> Paga tu adelanto
                            </h2>
                            @if ($paymentMethods->isEmpty())
                                <p class="mt-2 text-sm text-muted">La organización publicará aquí los datos de pago.</p>
                            @else
                                <ul class="mt-3 space-y-3">
                                    @foreach ($paymentMethods as $method)
                                        @php
                                            $number = $method->phone ?: $method->account_number;
                                            // Mismos colores de marca que la página de donaciones.
                                            $accent = match ($method->type) {
                                                \App\Enums\DonationMethodType::Yape => 'from-[#742284] to-[#9b3fb0]',
                                                \App\Enums\DonationMethodType::Plin => 'from-[#00a3a6] to-[#25c2b8]',
                                                default => 'from-primary to-primary-soft',
                                            };
                                        @endphp
                                        <li class="overflow-hidden rounded-card bg-white ring-1 ring-sand-dark" x-data="{ copied: false, copy() { navigator.clipboard?.writeText(@js($number)).then(() => { this.copied = true; setTimeout(() => this.copied = false, 2000) }) } }">
                                            <div class="flex items-center justify-between gap-3 bg-linear-to-r {{ $accent }} px-4 py-2.5 text-white">
                                                <p class="font-bold">{{ $method->label }}</p>
                                                <p class="text-right text-xs leading-tight">
                                                    <span class="block opacity-80">Monto a pagar</span>
                                                    <span class="font-display text-base font-extrabold tabular-nums" x-text="advance === null ? 'Ver instrucciones' : 'S/ ' + advance.toFixed(2)"></span>
                                                </p>
                                            </div>

                                            <div class="flex items-center gap-4 p-4">
                                                @if ($method->imageUrl())
                                                    <button type="button" x-on:click="$refs.qr.showModal()"
                                                            class="group flex shrink-0 flex-col items-center gap-1 rounded-control bg-white p-1.5 ring-1 ring-sand-dark transition hover:ring-2 hover:ring-brand-strong">
                                                        <img src="{{ $method->imageUrl() }}" alt="" width="96" height="96" loading="lazy" class="size-24 object-contain">
                                                        <span class="inline-flex items-center gap-1 text-xs font-semibold text-brand-strong"><x-ui.icon name="search" class="size-3.5" /> Ampliar</span>
                                                        <span class="sr-only">el código QR de {{ $method->label }}</span>
                                                    </button>
                                                @endif
                                                <dl class="min-w-0 space-y-2">
                                                    @if ($method->holder_name)
                                                        <div>
                                                            <dt class="text-[0.7rem] font-semibold tracking-wide text-muted uppercase">Titular</dt>
                                                            <dd class="font-semibold text-primary">{{ $method->holder_name }}</dd>
                                                        </div>
                                                    @endif
                                                    @if ($number)
                                                        <div>
                                                            <dt class="text-[0.7rem] font-semibold tracking-wide text-muted uppercase">{{ $method->phone ? 'Celular' : 'Número de cuenta' }}</dt>
                                                            <dd class="flex flex-wrap items-center gap-2">
                                                                <span class="font-display text-xl font-bold tracking-wide break-all text-primary tabular-nums">{{ $number }}</span>
                                                                <button type="button" x-on:click="copy()" class="inline-flex min-h-8 items-center rounded-full bg-cream px-3 text-xs font-semibold text-brand-strong ring-1 ring-sand-dark transition hover:bg-brand-strong hover:text-white">
                                                                    <span x-show="!copied">Copiar</span><span x-show="copied" x-cloak role="status">Copiado</span>
                                                                </button>
                                                            </dd>
                                                        </div>
                                                    @endif
                                                </dl>
                                            </div>

                                            @if ($method->imageUrl())
                                                {{-- Modal nativo: Esc y el fondo lo cierran; el foco queda dentro. --}}
                                                <dialog x-ref="qr" aria-labelledby="qr-{{ $method->id }}-titulo" x-on:click="if ($event.target === $el) $el.close()"
                                                        class="m-auto w-[min(24rem,calc(100%-2rem))] overflow-hidden rounded-card p-0 shadow-2xl backdrop:bg-primary-deep/70 backdrop:backdrop-blur-sm">
                                                    <div class="bg-linear-to-r {{ $accent }} flex items-center justify-between gap-3 px-5 py-3 text-white">
                                                        <h3 id="qr-{{ $method->id }}-titulo" class="font-sans text-lg font-bold text-white">Paga con {{ $method->label }}</h3>
                                                        <button type="button" x-on:click="$refs.qr.close()" class="inline-flex size-9 items-center justify-center rounded-full bg-white/15 transition hover:bg-white/30">
                                                            <x-ui.icon name="close" class="size-5" /><span class="sr-only">Cerrar</span>
                                                        </button>
                                                    </div>
                                                    <div class="p-5 text-center">
                                                        <p class="text-sm text-muted">Monto a pagar</p>
                                                        <p class="font-display text-3xl font-extrabold text-primary tabular-nums" x-text="advance === null ? 'Ver instrucciones' : 'S/ ' + advance.toFixed(2)"></p>
                                                        <img src="{{ $method->imageUrl() }}" alt="Código QR de {{ $method->label }}" width="320" height="320"
                                                             class="mx-auto mt-4 aspect-square w-full max-w-72 rounded-control bg-white object-contain p-2 ring-1 ring-sand-dark">
                                                        <p class="mt-3 text-sm text-muted">Escanéalo desde tu aplicación de {{ $method->label }}.</p>
                                                        @if ($number)
                                                            <div class="mt-4 flex items-center justify-between gap-3 rounded-control bg-cream px-4 py-3 text-left">
                                                                <div class="min-w-0">
                                                                    <p class="text-[0.7rem] font-semibold tracking-wide text-muted uppercase">{{ $method->holder_name ?: ($method->phone ? 'Celular' : 'Número de cuenta') }}</p>
                                                                    <p class="font-display text-xl font-bold tracking-wide break-all text-primary tabular-nums">{{ $number }}</p>
                                                                </div>
                                                                <button type="button" x-on:click="copy()" class="inline-flex min-h-10 shrink-0 items-center rounded-full bg-white px-4 text-sm font-semibold text-brand-strong ring-1 ring-sand-dark transition hover:bg-brand-strong hover:text-white">
                                                                    <span x-show="!copied">Copiar</span><span x-show="copied" x-cloak role="status">Copiado</span>
                                                                </button>
                                                            </div>
                                                        @endif
                                                        <x-ui.button type="button" class="mt-5 w-full" x-on:click="$refs.qr.close(); document.getElementById('deposit_proof')?.closest('section')?.scrollIntoView({ behavior: 'smooth' })">
                                                            Ya pagué, subir captura
                                                        </x-ui.button>
                                                    </div>
                                                </dialog>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                            @if ($event->reservation_instructions)
                                <p class="mt-3 rounded-control border border-line p-3 text-sm whitespace-pre-line">{{ $event->reservation_instructions }}</p>
                            @endif
                        </section>

                        {{-- 3. Captura del pago --}}
                        <section class="p-5" aria-labelledby="paso-captura">
                            <h2 id="paso-captura" class="flex items-center gap-2 text-lg font-bold">
                                <span class="inline-flex size-7 items-center justify-center rounded-full bg-brand-strong text-sm text-white" aria-hidden="true">3</span> Sube la captura
                            </h2>
                            <label for="deposit_proof" class="mt-3 flex cursor-pointer flex-col items-center gap-1 rounded-control border-2 border-dashed border-sand-dark bg-cream-light px-4 py-5 text-center transition hover:border-brand-strong focus-within:border-brand-strong">
                                <x-ui.icon name="image" class="size-7 text-brand-strong" />
                                <span class="text-sm font-semibold text-primary">
                                    @if ($deposit_proof && ! $errors->has('deposit_proof'))
                                        Captura adjunta · cambiar
                                    @else
                                        Elegir la imagen del pago
                                    @endif
                                </span>
                                <span class="text-xs text-muted">JPG, PNG o WebP · máx. 4 MB</span>
                                <input id="deposit_proof" type="file" wire:model="deposit_proof" accept="image/jpeg,image/png,image/webp" class="sr-only"
                                       @error('deposit_proof') aria-invalid="true" aria-describedby="deposit_proof-error" @enderror>
                            </label>
                            <div wire:loading wire:target="deposit_proof" class="mt-2 text-sm text-muted">Subiendo imagen…</div>
                            @error('deposit_proof')<p id="deposit_proof-error" class="mt-1 text-sm text-danger">{{ $message }}</p>@enderror
                            <p class="mt-2 text-xs text-muted">Solo la verá el equipo que confirma las reservas.</p>

                            <x-ui.button type="submit" class="mt-5 w-full" wire:loading.attr="disabled" wire:target="submit,deposit_proof" x-bind:disabled="total <= 0">
                                <span wire:loading.remove wire:target="submit">Enviar reserva</span>
                                <span wire:loading wire:target="submit">Enviando…</span>
                            </x-ui.button>
                            <p class="mt-2 text-center text-xs text-muted">Recibirás un código. La reserva queda confirmada cuando el equipo verifique tu adelanto.</p>
                        </section>

                        <div class="bg-cream p-5">
                            <p class="mb-2 text-sm font-bold text-primary">Antes de reservar</p>
                            @include('reservations.partials.rules')
                        </div>
                    </div>
                </aside>
            </form>
        @endif
    </div>
</div>
