<?php

namespace App\Models;

use App\Notifications\PlayerResetPassword;
use App\Services\GameCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Jugador de los juegos bíblicos (guard «player»). No tiene ningún acceso al
 * panel: es otra tabla y otro guard que los usuarios del equipo.
 * Público: solo el apodo y su avance. Privado: nombre y correo.
 */
class Player extends Authenticatable
{
    use Notifiable;

    /** Niveles superados en un juego para cada medalla. */
    public const MEDALS = [10 => 'bronce', 20 => 'plata', 30 => 'oro'];

    protected $fillable = ['nickname', 'name', 'email', 'password', 'is_public'];

    protected $attributes = ['is_public' => true, 'is_active' => true, 'streak_days' => 0, 'last_active_on' => null, 'remember_token' => null];

    protected $hidden = ['password', 'remember_token', 'email', 'name'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_public' => 'boolean',
            'is_active' => 'boolean',
            'age_confirmed_at' => 'datetime',
            'terms_accepted_at' => 'datetime',
            'streak_days' => 'integer',
            'last_active_on' => 'date',
        ];
    }

    public function progress(): HasMany
    {
        return $this->hasMany(GameProgress::class);
    }

    public function points(): HasMany
    {
        return $this->hasMany(GamePoint::class);
    }

    public function claims(): HasMany
    {
        return $this->hasMany(PrizeClaim::class);
    }

    /** Visibles en el ranking y con tarjeta pública. */
    public function scopeListed(Builder $query): void
    {
        $query->where('is_active', true)->where('is_public', true);
    }

    /**
     * Estrellas y niveles, en total y por juego (una consulta por petición).
     *
     * @return array{stars: int, levels: int, games: array<string, array{levels: int, stars: int, medal: ?string}>}
     */
    public function stats(): array
    {
        return once(function () {
            $rows = $this->progress()->get(['game', 'level', 'stars']);
            $games = [];

            foreach (array_keys(GameCatalog::GAMES) as $slug) {
                $mine = $rows->where('game', $slug);
                $levels = $mine->where('stars', '>', 0)->count();
                $games[$slug] = ['levels' => $levels, 'stars' => (int) $mine->sum('stars'), 'medal' => self::medalFor($levels)];
            }

            return ['stars' => (int) $rows->sum('stars'), 'levels' => $rows->where('stars', '>', 0)->count(), 'games' => $games];
        });
    }

    public static function medalFor(int $levels): ?string
    {
        return collect(self::MEDALS)->filter(fn ($medal, $needed) => $levels >= $needed)->last();
    }

    /** Nivel más alto que puede jugar en un juego (el siguiente al último superado). */
    public function unlockedLevel(string $game): int
    {
        $passed = $this->progress()->where('game', $game)->where('stars', '>', 0)->pluck('level')->all();
        $level = 1;
        while (in_array($level, $passed, true) && $level < GameCatalog::LEVELS) {
            $level++;
        }

        return $level;
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new PlayerResetPassword($token));
    }
}
