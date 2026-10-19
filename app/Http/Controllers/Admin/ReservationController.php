<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\View\View;

class ReservationController extends Controller
{
    public function show(Reservation $reservation): View
    {
        $reservation->load('items.offering.dish');

        try {
            // Para reenviar el enlace al cliente si lo perdió.
            $publicUrl = $reservation->publicUrl();
        } catch (DecryptException) {
            // Cifrado con otra APP_KEY (p. ej. base importada de otro servidor):
            // la ficha se sigue mostrando; solo falta el enlace.
            $publicUrl = null;
        }

        return view('admin.reservations.show', [
            'reservation' => $reservation,
            'publicUrl' => $publicUrl,
        ]);
    }
}
