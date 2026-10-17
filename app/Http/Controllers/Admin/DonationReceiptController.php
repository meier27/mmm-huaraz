<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Donation;
use App\Services\DonationService;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Sirve el comprobante desde el disco privado, solo a quien puede verificar
 * aportes (middleware de la ruta), y deja constancia en la auditoría.
 */
class DonationReceiptController extends Controller
{
    public function __invoke(Donation $donation, DonationService $donations): StreamedResponse
    {
        $disk = Storage::disk(Donation::RECEIPT_DISK);

        abort_unless($donation->receipt_path && $disk->exists($donation->receipt_path), 404);

        $donations->logReceiptView($donation);

        return $disk->response($donation->receipt_path, $donation->reference.'.'.pathinfo($donation->receipt_path, PATHINFO_EXTENSION), [
            'Cache-Control' => 'private, no-store',
            'Content-Security-Policy' => "default-src 'none'; img-src 'self'; style-src 'unsafe-inline'",
        ]);
    }
}
