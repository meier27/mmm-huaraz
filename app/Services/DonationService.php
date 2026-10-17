<?php

namespace App\Services;

use App\Enums\DonationStatus;
use App\Exceptions\DonationException;
use App\Models\AuditLog;
use App\Models\ChurchEvent;
use App\Models\Donation;
use App\Models\DonationMethod;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Registro y verificación manual de aportes.
 *
 * - El donante solo declara: el aporte nace "por verificar".
 * - Verificar exige registrar el monto realmente recibido.
 * - Cada revisión guarda quién y cuándo; queda en la auditoría.
 * - Los comprobantes van al disco privado con nombre aleatorio.
 */
class DonationService
{
    private const CODE_ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    /**
     * @param  array{donation_method_id: int, declared_amount: string, operation_number: string, paid_on: string, donor_name?: ?string, donor_phone?: ?string, donor_email?: ?string, message?: ?string}  $data
     */
    public function report(array $data, ?UploadedFile $receipt, ?string $ip, ?ChurchEvent $event = null): Donation
    {
        // Solo métodos oficiales publicados, comprobado de nuevo en el servidor.
        $method = DonationMethod::query()->published()->find($data['donation_method_id'])
            ?? throw new DonationException('Elige uno de los métodos de donación publicados.');

        $path = $receipt?->store('donation-receipts', Donation::RECEIPT_DISK);

        try {
            return DB::transaction(fn () => Donation::query()->forceCreate([
                'reference' => $this->uniqueReference(),
                'church_event_id' => $event?->id,
                'donation_method_id' => $method->id,
                'method_label' => $method->label,
                'declared_amount' => $data['declared_amount'],
                'operation_number' => trim($data['operation_number']),
                'paid_on' => $data['paid_on'],
                'donor_name' => $data['donor_name'] ?? null,
                'donor_phone' => $data['donor_phone'] ?? null,
                'donor_email' => $data['donor_email'] ?? null,
                'message' => $data['message'] ?? null,
                'receipt_path' => $path,
                'status' => DonationStatus::Pending,
                'consent_at' => now(),
                'ip_address' => $ip,
            ]));
        } catch (\Throwable $e) {
            if ($path) {
                Storage::disk(Donation::RECEIPT_DISK)->delete($path);
            }

            throw $e;
        }
    }

    public function verify(Donation $donation, User $reviewer, string $amount, ?string $note = null): Donation
    {
        if (bccomp($amount, '0', 2) <= 0) {
            throw new DonationException('Indica el monto realmente recibido (mayor que cero).');
        }

        return $this->review($donation, $reviewer, DonationStatus::Verified, $amount, $note);
    }

    public function reject(Donation $donation, User $reviewer, string $note): Donation
    {
        if (trim($note) === '') {
            throw new DonationException('Indica el motivo del rechazo.');
        }

        return $this->review($donation, $reviewer, DonationStatus::Rejected, null, $note);
    }

    /** Devuelve a "por verificar" un aporte ya revisado (para corregir un error). */
    public function reopen(Donation $donation, User $reviewer, string $note): Donation
    {
        if (trim($note) === '') {
            throw new DonationException('Indica por qué se reabre la revisión.');
        }

        return DB::transaction(function () use ($donation, $reviewer, $note) {
            $locked = $this->lock($donation);

            if ($locked->isPending()) {
                throw new DonationException('Este aporte ya está por verificar.');
            }

            $locked->forceFill([
                'status' => DonationStatus::Pending,
                'verified_amount' => null,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'review_note' => trim($note),
            ])->save();

            return $locked;
        });
    }

    /** Registra en la auditoría cada vez que alguien abre un comprobante. */
    public function logReceiptView(Donation $donation): void
    {
        AuditLog::record('receipt_viewed', $donation);
    }

    private function review(Donation $donation, User $reviewer, DonationStatus $status, ?string $amount, ?string $note): Donation
    {
        return DB::transaction(function () use ($donation, $reviewer, $status, $amount, $note) {
            // Bloqueo: dos revisores no pueden resolver el mismo aporte a la vez.
            $locked = $this->lock($donation);

            if (! $locked->isPending()) {
                throw new DonationException("Este aporte ya fue revisado ({$locked->status->label()}).");
            }

            $locked->forceFill([
                'status' => $status,
                'verified_amount' => $amount,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'review_note' => filled($note) ? trim($note) : null,
            ])->save();

            return $locked;
        });
    }

    private function lock(Donation $donation): Donation
    {
        return Donation::query()->lockForUpdate()->findOrFail($donation->id);
    }

    private function uniqueReference(): string
    {
        do {
            $code = 'AP-';
            for ($i = 0; $i < 7; $i++) {
                $code .= self::CODE_ALPHABET[random_int(0, strlen(self::CODE_ALPHABET) - 1)];
            }
        } while (Donation::where('reference', $code)->exists());

        return $code;
    }
}
