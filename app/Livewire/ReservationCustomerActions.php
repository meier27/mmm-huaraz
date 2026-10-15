<?php

namespace App\Livewire;

use App\Exceptions\ReservationException;
use App\Livewire\Concerns\ResolvesReservationToken;
use App\Services\ReservationService;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Cambio o cancelación del pedido por el propio cliente, dentro del plazo.
 */
class ReservationCustomerActions extends Component
{
    use ResolvesReservationToken;

    #[Locked]
    public string $token;

    public function cancel(ReservationService $service): void
    {
        try {
            $service->cancelByCustomer($this->reservationFromToken($this->token));
            $this->redirect(route('reservations.show', $this->token));
        } catch (ReservationException $e) {
            $this->addError('customer', $e->getMessage());
        }
    }

    public function render(): View
    {
        return view('livewire.reservation-customer-actions', [
            'reservation' => $this->reservationFromToken($this->token)->load('items.offering'),
        ]);
    }
}
