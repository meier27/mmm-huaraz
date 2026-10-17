<div>
    <x-admin.page-header title="Auditoría" description="Registro de altas, cambios y bajas. No se puede modificar ni eliminar." />

    @php($actions = ['created' => 'Creó', 'updated' => 'Modificó', 'deleted' => 'Eliminó', 'changed' => 'Cambió el pedido', 'receipt_viewed' => 'Vio el comprobante'])

    <ul class="divide-y divide-line rounded-card bg-white shadow-card">
        @forelse ($logs as $log)
            <li wire:key="log-{{ $log->id }}" class="p-4">
                <details>
                    <summary class="cursor-pointer list-none">
                        <span class="font-semibold">{{ $log->user?->name ?? 'Sistema' }}</span>
                        {{ $actions[$log->action] ?? $log->action }}
                        <span class="font-mono text-sm">{{ $log->auditableLabel() }}</span>
                        <span class="block text-xs text-muted sm:inline sm:ps-2">
                            <time datetime="{{ $log->created_at->toIso8601String() }}">{{ $log->created_at->translatedFormat('d/m/Y H:i') }}</time>
                            @if ($log->ip_address) · IP {{ $log->ip_address }} @endif
                        </span>
                    </summary>
                    <div class="mt-3 grid gap-3 text-xs sm:grid-cols-2">
                        @foreach (['old_values' => 'Antes', 'new_values' => 'Después'] as $field => $heading)
                            @if ($log->{$field})
                                <div>
                                    <p class="font-semibold">{{ $heading }}</p>
                                    <pre class="mt-1 overflow-x-auto rounded-control bg-stone-50 p-2">{{ json_encode($log->{$field}, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </details>
            </li>
        @empty
            <li class="p-6 text-center text-muted">Aún no hay registros.</li>
        @endforelse
    </ul>

    <div class="mt-4">{{ $logs->links() }}</div>
</div>
