<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Mejor resultado de un jugador en un nivel de un juego. */
class GameProgress extends Model
{
    protected $table = 'game_progress';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'stars' => 'integer',
            'best_seconds' => 'integer',
            'plays' => 'integer',
        ];
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }
}
