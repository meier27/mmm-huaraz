<?php

namespace App\Models;

use App\Enums\DonationStatus;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToEvent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Aporte declarado por un donante. Se crea y se revisa solo a través de
 * DonationService; por eso nada es asignable en masa.
 */
class Donation extends Model
{
    use Auditable, BelongsToEvent;

    public const RECEIPT_DISK = 'local';

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'status' => DonationStatus::class,
            'declared_amount' => 'decimal:2',
            'verified_amount' => 'decimal:2',
            'paid_on' => 'date:Y-m-d',
            'reviewed_at' => 'datetime',
            'consent_at' => 'datetime',
        ];
    }

    public function method(): BelongsTo
    {
        return $this->belongsTo(DonationMethod::class, 'donation_method_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopeStatus(Builder $query, DonationStatus $status): void
    {
        $query->where('status', $status);
    }

    public function isPending(): bool
    {
        return $this->status === DonationStatus::Pending;
    }

    /**
     * Otros aportes con el mismo n.º de operación en el mismo método:
     * posible duplicado que el revisor debe mirar con cuidado.
     */
    public function duplicates(): Builder
    {
        return static::query()
            ->whereKeyNot($this->id)
            ->where('donation_method_id', $this->donation_method_id)
            ->where('operation_number', $this->operation_number);
    }
}
