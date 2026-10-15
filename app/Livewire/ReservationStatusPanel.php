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
 * Se usa en la página pública del QR y en el panel. El visitante sin
 * permisos solo ve el estado.
 */
class ReservationStatusPanel extends Component
{
    #[Locked]
    public int $reservationId;

    public string $paymentMethod = 'cash';

    public string $paymentReference = '';

    public string $cancelReason = '';

    public function verifyPayment(ReservationService $service): void
    {
        $reservation = $this->reservation();
        $this->authorize('verifyPayment', $reservation);

        $data = $this->validate([
            'paymentMethod' => ['required', Rule::enum(PaymentMethod::class)],
            'paymentReference' => ['nullable', 'string', 'max:100'],
        ], [], ['paymentMethod' => 'método de pago', 'paymentReference' => 'referencia']);

        $this->run(fn () => $service->verifyPayment($reservation, auth()->user(), PaymentMethod::from($data['paymentMethod']), $data['paymentReference']), 'Pago verificado.');
    }

    public function deliver(ReservationService $service): void
    {
        $reservation = $this->reservation();
        $this->authorize('deliver', $reservation);

        $this->run(fn () => $service->deliver($reservation, auth()->user()), 'Entrega registrada.');
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
            'reservation' => $this->reservation()->load(['paymentVerifier:id,name', 'deliverer:id,name', 'canceller:id,name']),
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
            $this->reset('paymentReference', 'cancelReason');
            session()->flash('reservation-status', $success);
        } catch (ReservationException $e) {
            $this->addError('action', $e->getMessage());
        }
    }
}
