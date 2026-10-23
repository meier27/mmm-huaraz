<?php

namespace App\Enums;

/**
 * Contenido de los juegos bíblicos (Panel › Juegos).
 * - Pregunta: trivia de opción múltiple.
 * - Palabra: crucigrama (con su pista) y sopa de letras.
 */
enum GameItemType: string
{
    case Question = 'question';
    case Word = 'word';

    public function label(): string
    {
        return match ($this) {
            self::Question => 'Pregunta (trivia)',
            self::Word => 'Palabra (crucigrama y sopa de letras)',
        };
    }
}
