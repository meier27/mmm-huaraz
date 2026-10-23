<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Ganador de una semana del ranking (queda con su apodo aunque borre la cuenta). */
class WeeklyWinner extends Model
{
    use Auditable;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['week' => 'date', 'position' => 'integer', 'points' => 'integer'];
    }

    public function claim(): BelongsTo
    {
        return $this->belongsTo(PrizeClaim::class, 'prize_claim_id');
    }
}
