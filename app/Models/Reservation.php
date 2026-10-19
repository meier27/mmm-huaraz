<?php

namespace App\Models;

use App\Enums\DeliveryStatus;
use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Los cambios de estado se hacen exclusivamente a través de ReservationService.
 */
class Reservation extends Model
{
    use Auditable, BelongsToEvent;

    /** Disco privado de las capturas del adelanto. */
    public const PROOF_DISK = 'local';

    protected $guarded = ['*'];

    protected $hidden = ['token_hash', 'token_encrypted', 'idempotency_key', 'deposit_proof_path'];

    protected function casts(): array
    {
        return [
            'token_encrypted' => 'encrypted',
            'total' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'status' => ReservationStatus::class,
            'payment_status' => PaymentStatus::class,
            'delivery_status' => DeliveryStatus::class,
            'delivered_at' => 'datetime',
            'donated_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'expires_at' => 'datetime',
            'changed_at' => 'datetime',
        ];
    }

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    public static function findByToken(string $token): ?self
    {
        return static::query()->where('token_hash', static::hashToken($token))->first();
    }

    public function publicUrl(): string
    {
        return route('reservations.show', $this->token_encrypted);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ReservationItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(ReservationPayment::class)->orderBy('id');
    }

    public function deliverer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delivered_by');
    }

    public function donor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'donated_by');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function isCancelled(): bool
    {
        return $this->status === ReservationStatus::Cancelled;
    }

    public function hasPayments(): bool
    {
        return bccomp((string) $this->amount_paid, '0', 2) > 0;
    }

    /** El cliente envió la captura del adelanto y el personal aún no la revisó. */
    public function awaitsDepositReview(): bool
    {
        return $this->deposit_proof_path !== null && ! $this->hasPayments() && ! $this->isClosed();
    }

    public function isFullyPaid(): bool
    {
        return $this->payment_status === PaymentStatus::Paid;
    }

    public function isDelivered(): bool
    {
        return $this->delivery_status === DeliveryStatus::Delivered;
    }

    public function isDonated(): bool
    {
        return $this->delivery_status === DeliveryStatus::Donated;
    }

    /** Pedido cerrado: ya no admite pagos, cambios ni entregas. */
    public function isClosed(): bool
    {
        return $this->isCancelled() || $this->isDelivered() || $this->isDonated();
    }

    public function balance(): string
    {
        return bcsub((string) $this->total, (string) $this->amount_paid, 2);
    }

    /** Inicio del primer horario reservado (requiere items.offering cargados o los consulta). */
    public function firstSlotStart(): ?Carbon
    {
        return $this->items->map(fn (ReservationItem $item) => $item->offering->startsAt())->min();
    }

    public function lastSlotEnd(): ?Carbon
    {
        return $this->items->map(fn (ReservationItem $item) => $item->offering->endsAt())->max();
    }

    /** Límite para que el cliente cambie o cancele su pedido. */
    public function changeDeadline(): ?Carbon
    {
        return $this->firstSlotStart()?->copy()->subHours(config('reservations.change_deadline_hours'));
    }

    public function customerCanChange(?Carbon $now = null): bool
    {
        $deadline = $this->changeDeadline();

        return ! $this->isClosed() && $deadline !== null && ($now ?? now())->lt($deadline);
    }

    public function maskedPhone(): string
    {
        $digits = preg_replace('/\D+/', '', $this->customer_phone);

        return str_repeat('•', max(0, strlen($digits) - 3)).substr($digits, -3);
    }
}
