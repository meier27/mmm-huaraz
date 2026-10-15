<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Reservation;
use App\Models\User;

/**
 * Permisos por rol. Las reglas de estado (p. ej. no entregar dos veces)
 * las aplica ReservationService.
 */
class ReservationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(Role::Admin, Role::Cashier, Role::Delivery);
    }

    public function view(User $user, Reservation $reservation): bool
    {
        return $this->viewAny($user);
    }

    public function verifyPayment(User $user, Reservation $reservation): bool
    {
        return $user->hasRole(Role::Admin, Role::Cashier);
    }

    public function deliver(User $user, Reservation $reservation): bool
    {
        return $user->hasRole(Role::Admin, Role::Cashier, Role::Delivery);
    }

    /**
     * Caja cancela reservas sin pagar; una reserva pagada solo la cancela un administrador.
     */
    public function cancel(User $user, Reservation $reservation): bool
    {
        return $reservation->isPaid()
            ? $user->isAdmin()
            : $user->hasRole(Role::Admin, Role::Cashier);
    }
}
