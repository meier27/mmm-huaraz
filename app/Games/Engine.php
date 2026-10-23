<?php

namespace App\Games;

use App\Models\GameAttempt;
use App\Services\GameCatalog;
use Illuminate\Validation\ValidationException;

/**
 * Motor de un juego. El servidor arma cada nivel (start), guarda la solución
 * en la partida y comprueba cada jugada (act): el navegador nunca recibe las
 * respuestas, así el avance y los premios no dependen de lo que diga el
 * navegador.
 */
abstract class Engine
{
    public function __construct(protected readonly GameCatalog $catalog) {}

    /**
     * Arma un nivel.
     *
     * @return array{public: array, secret: array, state: array}
     */
    abstract public function start(int $level): array;

    /**
     * Comprueba una jugada; puede cambiar $attempt->state. Devuelve lo que ve
     * el jugador (sin la solución) y «done» = true cuando termina la partida.
     *
     * @return array<string, mixed>
     */
    abstract public function act(GameAttempt $attempt, string $action, array $input): array;

    /** Estrellas (1 a 3) de una partida terminada; 0 si no superó el nivel. */
    abstract public function stars(GameAttempt $attempt): int;

    /** Qué tiene el nivel, en palabras (para el mapa de niveles). */
    abstract public function describe(int $level): string;

    /** Etapa de 1 a $stages según el nivel (para subir la dificultad por tramos). */
    protected static function stage(int $level, int $every): int
    {
        return intdiv(max(1, $level) - 1, $every);
    }

    protected function fail(string $message): never
    {
        throw ValidationException::withMessages(['move' => $message]);
    }

    /** Mayúsculas sin tildes, con la Ñ (igual que GameItem::gridWord). */
    protected static function normalize(string $text): string
    {
        $text = strtr(mb_strtoupper(trim($text)), ['Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ü' => 'U']);

        return preg_replace('/[^A-ZÑ]/u', '', $text);
    }

    /** @return list<string> letras de una palabra (la Ñ es una sola) */
    protected static function letters(string $word): array
    {
        return mb_str_split($word);
    }
}
