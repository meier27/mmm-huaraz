<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Puntos ganados por un jugador en una semana (nivel nuevo, reto diario o racha). */
class GamePoint extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['points' => 'integer', 'week' => 'date'];
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }
}
