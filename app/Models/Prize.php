<?php

namespace App\Models;

use App\Enums\ClaimStatus;
use App\Enums\PrizeGoal;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasPublicImage;
use App\Models\Concerns\Publishable;
use App\Services\GameCatalog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Premio de los juegos (Biblia, revista…): se gana al alcanzar una meta y se
 * entrega en la iglesia con un código de canje. Hay una cantidad limitada.
 */
class Prize extends Model
{
    use Auditable, HasPublicImage, Publishable;

    protected string $imageColumn = 'image_path';

    protected array $imageBounds = [900, 900];

    protected $fillable = ['title', 'description', 'goal_type', 'goal_game', 'goal_value', 'stock', 'image_alt', 'is_published', 'sort_order'];

    protected function casts(): array
    {
        return [
            'goal_type' => PrizeGoal::class,
            'goal_value' => 'integer',
            'stock' => 'integer',
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function claims(): HasMany
    {
        return $this->hasMany(PrizeClaim::class);
    }

    /** Unidades que aún se pueden reclamar (los canjes anulados devuelven la suya). */
    public function remaining(): int
    {
        $taken = $this->claims_count ?? $this->claims()->where('status', '!=', ClaimStatus::Cancelled->value)->count();

        return max(0, $this->stock - $taken);
    }

    /** «Completa 30 niveles de Trivia bíblica», «Reúne 300 estrellas»… */
    public function goalText(): string
    {
        return match ($this->goal_type) {
            PrizeGoal::Stars => "Reúne {$this->goal_value} estrellas en los juegos",
            PrizeGoal::Levels => "Supera {$this->goal_value} niveles entre todos los juegos",
            PrizeGoal::GameLevels => "Supera {$this->goal_value} niveles de «".(GameCatalog::GAMES[$this->goal_game][1] ?? $this->goal_game).'»',
            PrizeGoal::Weekly => 'Para los primeros puestos del ranking semanal',
        };
    }

    /** Cuánto lleva el jugador hacia la meta. */
    public function progressOf(Player $player): int
    {
        $stats = $player->stats();

        return match ($this->goal_type) {
            PrizeGoal::Stars => $stats['stars'],
            PrizeGoal::Levels => $stats['levels'],
            PrizeGoal::GameLevels => $stats['games'][$this->goal_game]['levels'] ?? 0,
            PrizeGoal::Weekly => 0,
        };
    }

    public function isWeekly(): bool
    {
        return $this->goal_type === PrizeGoal::Weekly;
    }

    /** Los premios semanales no se reclaman: los asigna el equipo al cerrar la semana. */
    public function isReachedBy(Player $player): bool
    {
        return ! $this->isWeekly() && $this->progressOf($player) >= $this->goal_value;
    }
}
