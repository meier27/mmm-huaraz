<div>
    <x-admin.page-header title="Aportes" description="Aportes declarados por los donantes. Verifícalos con el estado de cuenta, Yape o Plin: la constancia por sí sola no confirma el pago." />

    <dl class="mb-6 grid gap-3 sm:grid-cols-2">
        <div class="rounded-card bg-white p-4 shadow-card">
            <dt class="text-sm text-muted">Por verificar</dt>
            <dd class="font-display text-3xl font-bold text-primary">{{ $pendingCount }}</dd>
        </div>
        <div class="rounded-card bg-white p-4 shadow-card">
            <dt class="text-sm text-muted">Total verificado</dt>
            <dd class="font-display text-3xl font-bold text-primary tabular-nums">S/ {{ number_format((float) $verifiedTotal, 2) }}</dd>
        </div>
    </dl>

    @if ($reviewing)
        <section class="mb-8 rounded-card bg-white p-5 shadow-card sm:p-6" aria-labelledby="revision">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 id="revision" class="text-lg font-bold">Aporte {{ $reviewing->reference }}</h2>
                    <p class="text-sm text-muted">Registrado el {{ $reviewing->created_at->translatedFormat('j M Y, H:i') }} · {{ $reviewing->status->label() }}</p>
                </div>
                <x-ui.button variant="ghost" wire:click="closeReview">Cerrar</x-ui.button>
            </div>

            @if ($duplicates->isNotEmpty())
                <x-ui.alert type="warning" class="mt-4">
                    Otro aporte usa el mismo n.º de operación en este método: {{ $duplicates->pluck('reference')->join(', ') }}. Revisa que no sea un duplicado.
                </x-ui.alert>
            @endif

            <dl class="mt-4 grid gap-x-6 gap-y-3 text-sm sm:grid-cols-2 lg:grid-cols-3">
                @foreach ([
                    'Método' => $reviewing->method_label,
                    'Monto declarado' => 'S/ '.number_format((float) $reviewing->declared_amount, 2),
                    'N.º de operación' => $reviewing->operation_number,
                    'Fecha declarada' => $reviewing->paid_on->translatedFormat('j M Y'),
                    'Donante' => $reviewing->donor_name ?: 'Anónimo',
                    'Celular' => $reviewing->donor_phone,
                    'Correo' => $reviewing->donor_email,
                    'Monto verificado' => $reviewing->verified_amount !== null ? 'S/ '.number_format((float) $reviewing->verified_amount, 2) : null,
                    'Revisado por' => $reviewing->reviewer ? $reviewing->reviewer->name.' · '.$reviewing->reviewed_at->translatedFormat('j M Y, H:i') : null,
                ] as $label => $value)
                    @if ($value)
                        <div>
                            <dt class="text-muted">{{ $label }}</dt>
                            <dd class="font-semibold break-all">{{ $value }}</dd>
                        </div>
                    @endif
                @endforeach
            </dl>

            @if ($reviewing->message)
                <p class="mt-3 text-sm"><span class="text-muted">Mensaje:</span> {{ $reviewing->message }}</p>
            @endif
            @if ($reviewing->review_note)
                <p class="mt-3 text-sm"><span class="text-muted">Nota de revisión:</span> {{ $reviewing->review_note }}</p>
            @endif

            @if ($reviewing->receipt_path)
                <p class="mt-4">
                    <a href="{{ route('admin.donations.receipt', $reviewing) }}" target="_blank" rel="noopener" class="font-semibold text-brand-strong underline-offset-4 hover:underline">
                        Ver constancia<span class="sr-only"> (se abre en una pestaña nueva; queda registrado en la auditoría)</span>
                    </a>
                </p>
            @else
                <p class="mt-4 text-sm text-muted">Sin constancia adjunta.</p>
            @endif

            <div class="mt-6 grid gap-4 border-t border-line pt-5 sm:grid-cols-2">
                @if ($reviewing->isPending())
                    <x-admin.input label="Monto realmente recibido (S/)" model="verifiedAmount" type="number" step="0.01" min="0.01" inputmode="decimal"
                                   hint="Compruébalo en el estado de cuenta antes de verificar." />
                @endif
                <x-admin.textarea label="Nota (obligatoria para rechazar o reabrir)" model="note" rows="2" :class="$reviewing->isPending() ? '' : 'sm:col-span-2'" />

                <div class="flex flex-wrap gap-2 sm:col-span-2">
                    @if ($reviewing->isPending())
                        <x-ui.button wire:click="verify" wire:loading.attr="disabled" wire:confirm="¿Confirmas que el dinero está en la cuenta?">Verificar</x-ui.button>
                        <x-ui.button variant="secondary" class="text-danger! border-danger!" wire:click="reject" wire:loading.attr="disabled">Rechazar</x-ui.button>
                    @else
                        <x-ui.button variant="secondary" wire:click="reopen" wire:loading.attr="disabled">Reabrir revisión</x-ui.button>
                    @endif
                </div>
            </div>
        </section>
    @endif

    <div class="mb-4 flex flex-col gap-3 sm:flex-row">
        <label class="sr-only" for="buscar-aportes">Buscar</label>
        <input id="buscar-aportes" type="search" wire:model.live.debounce.400ms="search" placeholder="Código, n.º de operación o nombre"
               class="block min-h-11 w-full rounded-control border border-stone-300 bg-white px-3 py-2 sm:max-w-sm">
        <label class="sr-only" for="evento-aportes">Evento</label>
        <select id="evento-aportes" wire:model.live="event" class="block min-h-11 rounded-control border border-stone-300 bg-white px-3 py-2">
            <option value="">Todos los eventos</option>
            @foreach ($events as $id => $eventTitle)
                <option value="{{ $id }}">{{ $eventTitle }}</option>
            @endforeach
        </select>
        <label class="sr-only" for="estado-aportes">Estado</label>
        <select id="estado-aportes" wire:model.live="status" class="block min-h-11 rounded-control border border-stone-300 bg-white px-3 py-2">
            @foreach ($statuses as $s)
                <option value="{{ $s->value }}">{{ $s->label() }}</option>
            @endforeach
            <option value="all">Todos</option>
        </select>
    </div>

    <ul class="divide-y divide-line rounded-card bg-white shadow-card">
        @forelse ($donations as $donation)
            <li wire:key="donation-{{ $donation->id }}" class="flex flex-col gap-2 p-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <p class="font-semibold">
                        <span class="tabular-nums">S/ {{ number_format((float) $donation->declared_amount, 2) }}</span>
                        · {{ $donation->method_label }}
                        <span class="font-normal text-muted">· {{ $donation->donor_name ?: 'Anónimo' }}</span>
                    </p>
                    <p class="text-sm text-muted">
                        {{ $donation->reference }} · Op. {{ $donation->operation_number }} · {{ $donation->paid_on->translatedFormat('j M Y') }}
                        @if ($donation->receipt_path) · con constancia @endif
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <span @class([
                        'inline-flex min-h-8 items-center rounded-full px-3 text-xs font-semibold',
                        'bg-amber-100 text-warning' => $donation->status === \App\Enums\DonationStatus::Pending,
                        'bg-green-100 text-success' => $donation->status === \App\Enums\DonationStatus::Verified,
                        'bg-red-100 text-danger' => $donation->status === \App\Enums\DonationStatus::Rejected,
                    ])>{{ $donation->status->label() }}</span>
                    <x-ui.button variant="ghost" class="min-h-9! px-3! py-1! text-sm" wire:click="review({{ $donation->id }})">Revisar</x-ui.button>
                </div>
            </li>
        @empty
            <li class="p-6 text-center text-muted">No hay aportes con este filtro.</li>
        @endforelse
    </ul>

    <div class="mt-4">{{ $donations->links() }}</div>
</div>
