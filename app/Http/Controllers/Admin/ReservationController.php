<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Services\QrCode;
use Illuminate\View\View;

class ReservationController extends Controller
{
    public function show(Reservation $reservation): View
    {
        $reservation->load('items.offering.dish');

        return view('admin.reservations.show', [
            'reservation' => $reservation,
            // Para reenviar el enlace al cliente si lo perdió.
            'publicUrl' => $reservation->publicUrl(),
            'qrSvg' => QrCode::svg($reservation->publicUrl(), 200),
        ]);
    }
}
