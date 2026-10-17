<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Services\QrCode;
use Illuminate\Http\Response;

/**
 * Consulta pública de una reserva mediante su token (enlace o QR).
 */
class ReservationController extends Controller
{
    public function show(string $token): Response
    {
        $reservation = $this->resolve($token);

        return response()
            ->view('reservations.show', [
                'reservation' => $reservation,
                'event' => $reservation->event,
                'qrSvg' => QrCode::svg($reservation->publicUrl()),
                'token' => $token,
            ])
            ->header('Cache-Control', 'no-store, private')
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }

    public function qr(string $token): Response
    {
        $reservation = $this->resolve($token);

        return response(QrCode::svg($reservation->publicUrl(), 600), 200, [
            'Content-Type' => 'image/svg+xml',
            'Content-Disposition' => 'attachment; filename="reserva-'.$reservation->code.'.svg"',
            'Cache-Control' => 'no-store, private',
        ]);
    }

    private function resolve(string $token): Reservation
    {
        abort_unless(preg_match('/^[a-f0-9]{48}$/', $token), 404);

        $reservation = Reservation::findByToken($token);
        abort_if($reservation === null, 404);

        return $reservation->load('items.offering.dish');
    }
}
