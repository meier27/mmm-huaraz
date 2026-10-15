<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Reservation;
use App\Models\User;

/**
 * Permisos por rol. Las reglas de estado y plazos (p. ej. no entregar dos
 * veces) las aplica ReservationService.
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

    public function registerPayment(User $user, Reservation $reservation): bool
    {
        return $user->hasRole(Role::Admin, Role::Cashier);
    }

    public function deliver(User $user, Reservation $reservation): bool
    {
        return $user->hasRole(Role::Admin, Role::Cashier, Role::Delivery);
    }

    public function markDonated(User $user, Reservation $reservation): bool
    {
        return $user->hasRole(Role::Admin, Role::Cashier);
    }

    /**
     * Caja cancela reservas sin pagos; con pagos registrados, solo un administrador.
     */
    public function cancel(User $user, Reservation $reservation): bool
    {
        return $reservation->hasPayments()
            ? $user->isAdmin()
            : $user->hasRole(Role::Admin, Role::Cashier);
    }
}
