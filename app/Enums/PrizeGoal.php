<?php

namespace App\Enums;

/** Meta para ganar un premio. */
enum PrizeGoal: string
{
    case Stars = 'stars';            // estrellas sumadas en todos los juegos
    case Levels = 'levels';          // niveles superados en todos los juegos
    case GameLevels = 'game_levels'; // niveles superados en un juego
    case Weekly = 'weekly';          // lo asigna el equipo al cerrar el ranking semanal

    public function label(): string
    {
        return match ($this) {
            self::Stars => 'Estrellas en total',
            self::Levels => 'Niveles superados (todos los juegos)',
            self::GameLevels => 'Niveles superados en un juego',
            self::Weekly => 'Premio del ranking semanal (lo asigna el equipo)',
        };
    }
}
