<?php

namespace App\Games;

use App\Models\GameAttempt;

/**
 * Sopa de letras: de 8×8 con 4 palabras a 12×12 con 9. Niveles 1–10:
 * horizontal y vertical; 11–20: también diagonal; 21–30: también al revés.
 */
class WordSearchEngine extends Engine
{
    private const FILL = 'AAAAABCDEEEEEFGHIIIJLLMNNÑOOOOPQRRSSSTTUUVYZ';

    /** @return array{size: int, words: int, dirs: list<array{int, int}>} */
    public static function params(int $level): array
    {
        $dirs = [[0, 1], [1, 0]];
        if ($level > 10) {
            $dirs = [...$dirs, [1, 1], [-1, 1]];
        }
        if ($level > 20) {
            $dirs = [...$dirs, [0, -1], [-1, 0], [-1, -1], [1, -1]];
        }

        return ['size' => 8 + self::stage($level, 6), 'words' => 4 + self::stage($level, 5), 'dirs' => $dirs];
    }

    public function describe(int $level): string
    {
        $p = self::params($level);

        return "{$p['words']} palabras en {$p['size']}×{$p['size']}".match (true) {
            $level > 20 => ' · diagonal y al revés',
            $level > 10 => ' · también diagonal',
            default => '',
        };
    }

    public function start(int $level): array
    {
        $p = self::params($level);
        $size = $p['size'];
        $pool = $this->catalog->words()->filter(fn ($w) => mb_strlen($w['word']) <= $size)->values();
        $best = null;

        for ($attempt = 0; $attempt < 25; $attempt++) {
            $grid = array_fill(0, $size * $size, '');
            $placed = [];

            foreach ($pool->shuffle()->take($p['words']) as $w) {
                $letters = self::letters($w['word']);
                for ($t = 0; $t < 150; $t++) {
                    [$dr, $dc] = $p['dirs'][array_rand($p['dirs'])];
                    $r = random_int(0, $size - 1);
                    $c = random_int(0, $size - 1);
                    $cells = [];
                    foreach ($letters as $i => $letter) {
                        [$y, $x] = [$r + $dr * $i, $c + $dc * $i];
                        if ($y < 0 || $y >= $size || $x < 0 || $x >= $size || ! in_array($grid[$y * $size + $x], ['', $letter], true)) {
                            continue 2;
                        }
                        $cells[] = $y * $size + $x;
                    }
                    foreach ($cells as $i => $cell) {
                        $grid[$cell] = $letters[$i];
                    }
                    $placed[] = [...$w, 'cells' => $cells];
                    break;
                }
            }

            if (! $best || count($placed) > count($best['placed'])) {
                $best = ['grid' => $grid, 'placed' => $placed];
            }
            if (count($placed) >= min($p['words'], $pool->count())) {
                break;
            }
        }

        $fill = self::letters(self::FILL);
        $letters = array_map(fn ($l) => $l !== '' ? $l : $fill[array_rand($fill)], $best['grid']);
        $targets = collect($best['placed'])->sortBy('display', SORT_NATURAL | SORT_FLAG_CASE)->values();

        return [
            'public' => ['size' => $size, 'letters' => $letters, 'targets' => $targets->pluck('display')->all()],
            'secret' => ['letters' => $letters, 'targets' => $targets->map(fn ($t) => ['word' => $t['word'], 'display' => $t['display'], 'reference' => $t['reference'], 'cells' => $t['cells']])->all()],
            'state' => ['found' => []],
        ];
    }

    public function act(GameAttempt $attempt, string $action, array $input): array
    {
        $secret = $attempt->secret;
        $size = (int) sqrt(count($secret['letters']));
        $cells = $input['cells'] ?? null;

        if ($action !== 'select' || ! is_array($cells) || count($cells) < 2 || count($cells) > $size || ! $this->isLine($cells, $size)) {
            $this->fail('Elige letras seguidas en línea recta.');
        }

        $text = implode('', array_map(fn ($i) => $secret['letters'][$i], $cells));
        $state = $attempt->state;

        foreach ($secret['targets'] as $index => $target) {
            if (in_array($index, $state['found'], true)) {
                continue;
            }
            if ($text === $target['word'] || $text === implode('', array_reverse(self::letters($target['word'])))) {
                $state['found'][] = $index;
                $attempt->state = $state;

                return ['found' => true, 'index' => $index, 'display' => $target['display'], 'reference' => $target['reference'], 'cells' => $cells, 'done' => count($state['found']) === count($secret['targets'])];
            }
        }

        return ['found' => false, 'text' => $text, 'done' => false];
    }

    public function stars(GameAttempt $attempt): int
    {
        $par = count($attempt->secret['targets']) * 20 + 30;

        return $attempt->seconds <= $par ? 3 : ($attempt->seconds <= $par * 2 ? 2 : 1);
    }

    /** Celdas contiguas en línea recta (horizontal, vertical o diagonal). */
    private function isLine(array $cells, int $size): bool
    {
        foreach ($cells as $cell) {
            if (! is_int($cell) || $cell < 0 || $cell >= $size * $size) {
                return false;
            }
        }

        $dr = intdiv($cells[1], $size) - intdiv($cells[0], $size);
        $dc = $cells[1] % $size - $cells[0] % $size;
        if (abs($dr) > 1 || abs($dc) > 1 || ($dr === 0 && $dc === 0)) {
            return false;
        }

        foreach ($cells as $i => $cell) {
            if (intdiv($cell, $size) !== intdiv($cells[0], $size) + $dr * $i || $cell % $size !== $cells[0] % $size + $dc * $i) {
                return false;
            }
        }

        return true;
    }
}
