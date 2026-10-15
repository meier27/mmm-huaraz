@php($deadlineHours = config('reservations.change_deadline_hours'))
<ul class="space-y-2 text-sm">
    <li class="flex gap-2">
        <x-ui.icon name="check" class="mt-0.5 size-4 text-brand-strong" />
        <span>
            Confirma tu reserva pagando un <strong>adelanto</strong>@if ($settings->deposit_min_percent) (mínimo {{ $settings->deposit_min_percent }} %)@endif; el saldo lo pagas al recoger.
        </span>
    </li>
    <li class="flex gap-2">
        <x-ui.icon name="clock" class="mt-0.5 size-4 text-brand-strong" />
        <span>¿No podrás recogerlo? Puedes <strong>cambiar tu pedido</strong> por otra comida hasta <strong>{{ $deadlineHours }} horas antes</strong> de tu horario.</span>
    </li>
    <li class="flex gap-2">
        <x-ui.icon name="heart" class="mt-0.5 size-4 text-brand-strong" />
        <span>Si no lo cambias a tiempo ni lo recoges, tu pedido se destinará a <strong>donación</strong>.</span>
    </li>
</ul>
