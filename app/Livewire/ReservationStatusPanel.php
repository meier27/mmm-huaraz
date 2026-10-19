<?php

namespace App\Livewire;

use App\Enums\PaymentMethod;
use App\Exceptions\ReservationException;
use App\Models\Reservation;
use App\Services\ReservationService;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Estado de la reserva y, para el personal autorizado, sus acciones.
 * Se usa en la página pública de la reserva, en el panel y en el verificador. El visitante sin
 * permisos solo ve el estado.
 */
class ReservationStatusPanel extends Component
{
    #[Locked]
    public int $reservationId;

    public string $paymentAmount = '';

    public string $paymentMethod = 'cash';

    public string $paymentReference = '';

    public string $cancelReason = '';

    /** Medio por el que llegó el adelanto de la captura. */
    public string $depositMethod = 'yape';

    public string $rejectReason = '';

    public function mount(): void
    {
        $this->paymentAmount = $this->reservation()->balance();
    }

    public function registerPayment(ReservationService $service): void
    {
        $reservation = $this->reservation();
        $this->authorize('registerPayment', $reservation);

        $data = $this->validate([
            'paymentAmount' => ['required', 'decimal:0,2', 'min:0.01', 'max:99999.99'],
            'paymentMethod' => ['required', Rule::enum(PaymentMethod::class)],
            'paymentReference' => ['nullable', 'string', 'max:100'],
        ], [], ['paymentAmount' => 'monto', 'paymentMethod' => 'método de pago', 'paymentReference' => 'referencia']);

        $this->run(fn () => $service->registerPayment($reservation, auth()->user(), (string) $data['paymentAmount'], PaymentMethod::from($data['paymentMethod']), $data['paymentReference']), 'Pago registrado.');
    }

    public function approveDeposit(ReservationService $service): void
    {
        $reservation = $this->reservation();
        $this->authorize('registerPayment', $reservation);

        $data = $this->validate(['depositMethod' => ['required', Rule::enum(PaymentMethod::class)]], [], ['depositMethod' => 'medio de pago']);

        $this->run(fn () => $service->approveDeposit($reservation, auth()->user(), PaymentMethod::from($data['depositMethod'])), 'Adelanto aceptado: la reserva está confirmada.');
    }

    /** Rechazar la captura cancela la reserva y libera el stock; el motivo lo ve el cliente. */
    public function rejectDeposit(ReservationService $service): void
    {
        $reservation = $this->reservation();
        $this->authorize('cancel', $reservation);

        $data = $this->validate(['rejectReason' => ['required', 'string', 'min:3', 'max:200']], [], ['rejectReason' => 'motivo']);

        $this->run(fn () => $service->cancel($reservation, auth()->user(), 'Adelanto no válido: '.$data['rejectReason']), 'Captura rechazada: la reserva se canceló y el stock quedó libre.');
    }

    public function deliver(ReservationService $service): void
    {
        $reservation = $this->reservation();
        $this->authorize('deliver', $reservation);

        $this->run(fn () => $service->deliver($reservation, auth()->user()), 'Entrega registrada.');
    }

    public function markDonated(ReservationService $service): void
    {
        $reservation = $this->reservation();
        $this->authorize('markDonated', $reservation);

        $this->run(fn () => $service->markDonated($reservation, auth()->user()), 'Pedido registrado como donación.');
    }

    public function cancel(ReservationService $service): void
    {
        $reservation = $this->reservation();
        $this->authorize('cancel', $reservation);

        $data = $this->validate(['cancelReason' => ['required', 'string', 'min:3', 'max:255']], [], ['cancelReason' => 'motivo']);

        $this->run(fn () => $service->cancel($reservation, auth()->user(), $data['cancelReason']), 'Reserva cancelada y stock liberado.');
    }

    public function render(): View
    {
        return view('livewire.reservation-status-panel', [
            'reservation' => $this->reservation()->load(['items.offering', 'payments.receiver:id,name', 'deliverer:id,name', 'donor:id,name', 'canceller:id,name']),
            'methods' => PaymentMethod::cases(),
        ]);
    }

    private function reservation(): Reservation
    {
        return Reservation::findOrFail($this->reservationId);
    }

    private function run(callable $action, string $success): void
    {
        try {
            $action();
            $this->reset('paymentReference', 'cancelReason', 'rejectReason');
            $this->paymentAmount = $this->reservation()->balance();
            session()->flash('reservation-status', $success);
        } catch (ReservationException $e) {
            $this->addError('action', $e->getMessage());
        }
    }
}
