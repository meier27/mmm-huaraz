<?php

namespace App\Games;

use App\Models\GameAttempt;

/**
 * Rompecabezas: 3×3 (niveles 1–8), 4×4 (9–18), 5×5 (19–26) y 6×6 (27–30).
 * Las piezas se mueven en el navegador; al terminar se envían los cambios y
 * el servidor los repite desde la mezcla inicial para comprobarlo.
 */
class PuzzleEngine extends Engine
{
    public static function side(int $level): int
    {
        return match (true) {
            $level <= 8 => 3,
            $level <= 18 => 4,
            $level <= 26 => 5,
            default => 6,
        };
    }

    public function describe(int $level): string
    {
        return (self::side($level) ** 2).' piezas';
    }

    public function start(int $level): array
    {
        $n = self::side($level);
        $images = $this->catalog->images();
        $image = $images[($level - 1) % max(1, $images->count())];
        $count = $n * $n;

        // Bien mezclado: casi ninguna pieza en su sitio.
        do {
            $tiles = range(0, $count - 1);
            shuffle($tiles);
        } while (count(array_filter($tiles, fn ($p, $i) => $p === $i, ARRAY_FILTER_USE_BOTH)) > intdiv($count, 4));

        return ['public' => ['image' => $image, 'n' => $n, 'tiles' => $tiles], 'secret' => ['tiles' => $tiles], 'state' => ['moves' => 0]];
    }

    public function act(GameAttempt $attempt, string $action, array $input): array
    {
        $tiles = $attempt->secret['tiles'];
        $swaps = $input['swaps'] ?? null;

        if ($action !== 'submit' || ! is_array($swaps) || count($swaps) > 5000) {
            $this->fail('Jugada no válida.');
        }

        foreach ($swaps as $swap) {
            [$a, $b] = is_array($swap) && count($swap) === 2 ? array_values($swap) : [null, null];
            if (! is_int($a) || ! is_int($b) || ! isset($tiles[$a], $tiles[$b])) {
                $this->fail('Jugada no válida.');
            }
            [$tiles[$a], $tiles[$b]] = [$tiles[$b], $tiles[$a]];
        }

        $solved = $tiles === range(0, count($tiles) - 1);
        if ($solved) {
            $attempt->state = ['moves' => count($swaps)];
        }

        $verse = $solved ? $this->catalog->verses()->shuffle()->first() : null;

        return ['solved' => $solved, 'verse' => $verse, 'done' => $solved];
    }

    public function stars(GameAttempt $attempt): int
    {
        // Mínimo de cambios posible: piezas menos ciclos de la mezcla inicial.
        $tiles = $attempt->secret['tiles'];
        $seen = [];
        $cycles = 0;
        foreach (array_keys($tiles) as $start) {
            if (isset($seen[$start])) {
                continue;
            }
            $cycles++;
            for ($i = $start; ! isset($seen[$i]); $i = $tiles[$i]) {
                $seen[$i] = true;
            }
        }
        $optimal = count($tiles) - $cycles;
        $n = (int) sqrt(count($tiles));
        $moves = $attempt->state['moves'];

        return $moves <= $optimal + $n ? 3 : ($moves <= $optimal * 2 + $n ? 2 : 1);
    }
}
