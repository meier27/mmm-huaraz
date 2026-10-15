<?php

namespace App\Livewire\Concerns;

use App\Models\Reservation;

/**
 * Acciones del cliente autorizadas por la posesión del token (enlace o QR).
 * El token se vuelve a resolver en el servidor en cada petición.
 */
trait ResolvesReservationToken
{
    protected function reservationFromToken(string $token): Reservation
    {
        abort_unless(preg_match('/^[a-f0-9]{48}$/', $token), 404);

        return Reservation::findByToken($token) ?? abort(404);
    }
}
