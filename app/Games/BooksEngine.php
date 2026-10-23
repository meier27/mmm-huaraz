<?php

namespace App\Games;

use App\Models\GameAttempt;
use Illuminate\Support\Arr;

/**
 * Ordena los libros: de 4 a 13 libros. Niveles 1–10: seguidos del Nuevo
 * Testamento; 11–20: seguidos del Antiguo; 21–30: salteados de toda la Biblia.
 */
class BooksEngine extends Engine
{
    /** @return array{size: int, scope: string} */
    public static function params(int $level): array
    {
        return ['size' => 4 + self::stage($level, 3), 'scope' => match (true) {
            $level <= 10 => 'NT',
            $level <= 20 => 'AT',
            default => 'todos',
        }];
    }

    public function describe(int $level): string
    {
        $p = self::params($level);

        return "{$p['size']} libros · ".['NT' => 'Nuevo Testamento', 'AT' => 'Antiguo Testamento', 'todos' => 'toda la Biblia, salteados'][$p['scope']];
    }

    public function start(int $level): array
    {
        $p = self::params($level);
        $books = $this->catalog->books();
        $names = array_column($p['scope'] === 'todos' ? $books : array_filter($books, fn ($b) => $b['testament'] === $p['scope']), 'name');
        $size = min($p['size'], count($names));

        if ($p['scope'] === 'todos') {
            // Salteados pero en el orden de la Biblia.
            $keys = Arr::random(array_keys($names), $size);
            sort($keys);
            $sequence = array_values(array_map(fn ($k) => $names[$k], $keys));
        } else {
            $from = random_int(0, count($names) - $size);
            $sequence = array_slice(array_values($names), $from, $size);
        }

        do {
            $pool = Arr::shuffle($sequence);
        } while ($size > 1 && $pool === $sequence);

        return ['public' => ['pool' => $pool, 'size' => $size], 'secret' => ['sequence' => $sequence], 'state' => ['placed' => 0, 'mistakes' => 0]];
    }

    public function act(GameAttempt $attempt, string $action, array $input): array
    {
        $state = $attempt->state;
        $sequence = $attempt->secret['sequence'];
        $name = $input['name'] ?? null;

        if ($action !== 'pick' || ! in_array($name, $sequence, true) || array_search($name, $sequence, true) < $state['placed']) {
            $this->fail('Ese libro no es parte de este nivel.');
        }

        $correct = $name === $sequence[$state['placed']];
        $correct ? $state['placed']++ : $state['mistakes']++;
        $attempt->state = $state;

        return [
            'correct' => $correct,
            'placed' => $state['placed'],
            'previous' => $correct ? null : ($sequence[$state['placed'] - 1] ?? null),
            'mistakes' => $state['mistakes'],
            'done' => $state['placed'] === count($sequence),
        ];
    }

    public function stars(GameAttempt $attempt): int
    {
        $mistakes = $attempt->state['mistakes'];

        return $mistakes === 0 ? 3 : ($mistakes <= 2 ? 2 : 1);
    }
}
