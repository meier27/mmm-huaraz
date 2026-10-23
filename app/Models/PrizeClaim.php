<?php

namespace App\Models;

use App\Enums\ClaimStatus;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Canje de un premio: el jugador muestra el código en la iglesia y el equipo
 * lo marca como entregado en el panel (queda en la auditoría).
 */
class PrizeClaim extends Model
{
    use Auditable;

    /** Sin 0/O ni 1/I/L: se dicta y se lee sin confusiones. */
    private const ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    protected $fillable = ['prize_id', 'player_id', 'player_nickname', 'code', 'status', 'notes'];

    protected function casts(): array
    {
        return [
            'status' => ClaimStatus::class,
            'delivered_at' => 'datetime',
            'follow_declared_at' => 'datetime',
            'follow_verified_at' => 'datetime',
        ];
    }

    public function prize(): BelongsTo
    {
        return $this->belongsTo(Prize::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function deliveredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delivered_by');
    }

    /** «K7PM-29QX» */
    public static function newCode(): string
    {
        do {
            $raw = collect(range(1, 8))->map(fn () => self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)])->implode('');
            $code = substr($raw, 0, 4).'-'.substr($raw, 4);
        } while (static::query()->where('code', $code)->exists());

        return $code;
    }
}
