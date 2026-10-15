<?php

namespace App\Models;

use App\Enums\DeliveryStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Los cambios de estado se hacen exclusivamente a través de ReservationService.
 */
class Reservation extends Model
{
    use Auditable;

    protected $guarded = ['*'];

    protected $hidden = ['token_hash', 'token_encrypted', 'idempotency_key'];

    protected function casts(): array
    {
        return [
            'token_encrypted' => 'encrypted',
            'total' => 'decimal:2',
            'status' => ReservationStatus::class,
            'payment_status' => PaymentStatus::class,
            'delivery_status' => DeliveryStatus::class,
            'payment_method' => PaymentMethod::class,
            'payment_verified_at' => 'datetime',
            'delivered_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'expires_at' => 'datetime',
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

    public function paymentVerifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payment_verified_by');
    }

    public function deliverer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delivered_by');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function isCancelled(): bool
    {
        return $this->status === ReservationStatus::Cancelled;
    }

    public function isPaid(): bool
    {
        return $this->payment_status === PaymentStatus::Verified;
    }

    public function isDelivered(): bool
    {
        return $this->delivery_status === DeliveryStatus::Delivered;
    }

    public function maskedPhone(): string
    {
        $digits = preg_replace('/\D+/', '', $this->customer_phone);

        return str_repeat('•', max(0, strlen($digits) - 3)).substr($digits, -3);
    }
}
