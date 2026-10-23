<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Una partida de un nivel. El servidor la arma y guarda la solución en
 * «secret» (nunca se envía al navegador); cada jugada se comprueba aquí.
 */
class GameAttempt extends Model
{
    use HasUlids;

    protected $guarded = [];

    protected $hidden = ['secret'];

    protected function casts(): array
    {
        return [
            'secret' => 'array',
            'state' => 'array',
            'level' => 'integer',
            'stars' => 'integer',
            'seconds' => 'integer',
            'finished_at' => 'datetime',
        ];
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function isFinished(): bool
    {
        return $this->finished_at !== null;
    }

    /** Segundos desde que se abrió el nivel (medidos en el servidor). */
    public function elapsed(): int
    {
        return (int) max(0, $this->created_at->diffInSeconds(now()));
    }
}
