<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Reservation;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Sirve la captura del adelanto desde el disco privado, solo a quien puede
 * registrar pagos (middleware de la ruta), y deja constancia en la auditoría.
 */
class ReservationProofController extends Controller
{
    public function __invoke(Reservation $reservation): StreamedResponse
    {
        $disk = Storage::disk(Reservation::PROOF_DISK);
        $path = $reservation->deposit_proof_path;

        abort_unless($path && $disk->exists($path), 404);

        AuditLog::record('deposit_proof_viewed', $reservation);

        return $disk->response($path, 'adelanto-'.$reservation->code.'.'.pathinfo($path, PATHINFO_EXTENSION), [
            'Cache-Control' => 'private, no-store',
            'Content-Security-Policy' => "default-src 'none'; img-src 'self'; style-src 'unsafe-inline'",
        ]);
    }
}
