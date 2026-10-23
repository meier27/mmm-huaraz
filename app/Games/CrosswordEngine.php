<?php

namespace App\Games;

use App\Models\GameAttempt;

/**
 * Crucigrama: de 4 palabras (nivel 1) a 11 (nivel 29 en adelante), armado en
 * el servidor. Las letras no salen de aquí: «Comprobar», «Revelar una letra»
 * y el final se resuelven con el servidor.
 */
class CrosswordEngine extends Engine
{
    /** Columnas y filas como máximo: casillas cómodas en el celular. */
    private const LIMIT = 11;

    public static function count(int $level): int
    {
        return 4 + self::stage($level, 4);
    }

    public function describe(int $level): string
    {
        return self::count($level).' palabras';
    }

    public function start(int $level): array
    {
        ['rows' => $rows, 'cols' => $cols, 'entries' => $entries] = $this->build($this->catalog->words()->all(), self::count($level));
        $letters = [];
        $cells = array_fill(0, $rows * $cols, null);

        foreach ($entries as $entry) {
            foreach ($entry['cells'] as $k => $cell) {
                $letters[$cell] = self::letters($entry['word'])[$k];
                $cells[$cell] ??= ['number' => null];
            }
            $cells[$entry['cells'][0]]['number'] = $entry['number'];
        }

        return [
            'public' => [
                'rows' => $rows,
                'cols' => $cols,
                'cells' => $cells,
                'entries' => array_map(fn ($e) => ['id' => $e['id'], 'number' => $e['number'], 'dir' => $e['dir'], 'clue' => $e['clue'], 'reference' => $e['reference'], 'cells' => $e['cells']], $entries),
            ],
            'secret' => ['letters' => $letters],
            'state' => ['hinted' => [], 'checks' => 0],
        ];
    }

    public function act(GameAttempt $attempt, string $action, array $input): array
    {
        $letters = $attempt->secret['letters'];
        $state = $attempt->state;

        if ($action === 'hint') {
            $cell = $input['cell'] ?? null;
            if (! is_int($cell) || ! isset($letters[$cell])) {
                $this->fail('Elige una casilla del crucigrama.');
            }
            if (! in_array($cell, $state['hinted'], true)) {
                $state['hinted'][] = $cell;
                $attempt->state = $state;
            }

            return ['cell' => $cell, 'letter' => $letters[$cell], 'hints' => count($state['hinted']), 'done' => false];
        }

        $given = $input['letters'] ?? null;
        if (! in_array($action, ['check', 'submit'], true) || ! is_array($given)) {
            $this->fail('Jugada no válida.');
        }

        $wrong = [];
        $empty = 0;
        foreach ($letters as $cell => $letter) {
            $value = is_string($given[$cell] ?? null) ? self::normalize($given[$cell]) : '';
            if ($value === '') {
                $empty++;
            } elseif ($value !== $letter) {
                $wrong[] = $cell;
            }
        }

        if ($action === 'check') {
            $state['checks']++;
            $attempt->state = $state;
        }

        return ['wrong' => $wrong, 'empty' => $empty, 'done' => $action === 'submit' && ! $wrong && ! $empty];
    }

    public function stars(GameAttempt $attempt): int
    {
        // Revelar letras y comprobar mucho (adivinar a prueba y error) restan.
        $penalty = count($attempt->state['hinted']) + intdiv($attempt->state['checks'], 3);

        return $penalty === 0 ? 3 : ($penalty <= 2 ? 2 : 1);
    }

    /**
     * Coloca las palabras cruzándolas (varios intentos; se queda con el mejor).
     *
     * @param  list<array{word: string, clue: string, reference: string}>  $words
     * @return array{rows: int, cols: int, entries: list<array>}
     */
    private function build(array $words, int $max): array
    {
        $best = null;

        for ($attempt = 0; $attempt < 40; $attempt++) {
            shuffle($words);
            $list = array_slice($words, 0, min(count($words), 18));
            // Se empieza por una de las más largas: da más cruces.
            usort($list, fn ($a, $b) => mb_strlen($b['word']) - mb_strlen($a['word']) + random_int(-2, 2));

            $cells = [];
            $dirsAt = [];
            $placed = [];
            $box = ['top' => 0, 'left' => 0, 'bottom' => 0, 'right' => 0];

            $fits = function (array $letters, int $r, int $c, string $dir) use (&$cells, &$dirsAt, &$box): int {
                [$dr, $dc] = $dir === 'down' ? [1, 0] : [0, 1];
                $n = count($letters);
                if (isset($cells[($r - $dr).','.($c - $dc)]) || isset($cells[($r + $dr * $n).','.($c + $dc * $n)])) {
                    return 0;
                }
                if (max($box['bottom'], $r + $dr * ($n - 1)) - min($box['top'], $r) + 1 > self::LIMIT
                    || max($box['right'], $c + $dc * ($n - 1)) - min($box['left'], $c) + 1 > self::LIMIT) {
                    return 0;
                }

                $crossings = 0;
                foreach ($letters as $i => $letter) {
                    [$y, $x] = [$r + $dr * $i, $c + $dc * $i];
                    $current = $cells["$y,$x"] ?? null;
                    if ($current !== null) {
                        if ($current !== $letter || in_array($dir, $dirsAt["$y,$x"], true)) {
                            return 0;
                        }
                        $crossings++;
                    } elseif (isset($cells[($y + $dc).','.($x + $dr)]) || isset($cells[($y - $dc).','.($x - $dr)])) {
                        return 0; // letras pegadas a otra palabra paralela
                    }
                }

                return $crossings;
            };

            $put = function (array $w, int $r, int $c, string $dir) use (&$cells, &$dirsAt, &$placed, &$box): void {
                [$dr, $dc] = $dir === 'down' ? [1, 0] : [0, 1];
                $letters = self::letters($w['word']);
                foreach ($letters as $i => $letter) {
                    $key = ($r + $dr * $i).','.($c + $dc * $i);
                    $cells[$key] = $letter;
                    $dirsAt[$key][] = $dir;
                }
                $placed[] = [...$w, 'r' => $r, 'c' => $c, 'dir' => $dir];
                $box = [
                    'top' => min($box['top'], $r), 'left' => min($box['left'], $c),
                    'bottom' => max($box['bottom'], $r + $dr * (count($letters) - 1)), 'right' => max($box['right'], $c + $dc * (count($letters) - 1)),
                ];
            };

            $put($list[0], 0, 0, 'across');

            foreach (array_slice($list, 1) as $w) {
                if (count($placed) >= $max) {
                    break;
                }
                $letters = self::letters($w['word']);
                $options = [];
                foreach ($placed as $p) {
                    foreach (self::letters($p['word']) as $i => $letter) {
                        foreach ($letters as $j => $mine) {
                            if ($letter !== $mine) {
                                continue;
                            }
                            $dir = $p['dir'] === 'across' ? 'down' : 'across';
                            $r = $p['r'] + ($p['dir'] === 'down' ? $i : 0) - ($dir === 'down' ? $j : 0);
                            $c = $p['c'] + ($p['dir'] === 'across' ? $i : 0) - ($dir === 'across' ? $j : 0);
                            if ($score = $fits($letters, $r, $c, $dir)) {
                                $options[] = [$r, $c, $dir, $score + random_int(0, 99) / 100];
                            }
                        }
                    }
                }
                if ($options) {
                    usort($options, fn ($a, $b) => $b[3] <=> $a[3]);
                    $put($w, ...array_slice($options[0], 0, 3));
                }
            }

            $area = ($box['bottom'] - $box['top'] + 1) * ($box['right'] - $box['left'] + 1);
            $value = count($placed) * 1000 - $area;
            if (! $best || $value > $best['value']) {
                $best = ['value' => $value, 'placed' => $placed, 'box' => $box];
            }
            if (count($placed) >= min($max, count($words))) {
                break;
            }
        }

        // Coordenadas desde 0 y numeración de lectura (de arriba abajo, de izquierda a derecha).
        $box = $best['box'];
        $cols = $box['right'] - $box['left'] + 1;
        $entries = array_map(fn ($p) => [...$p, 'r' => $p['r'] - $box['top'], 'c' => $p['c'] - $box['left']], $best['placed']);
        usort($entries, fn ($a, $b) => [$a['r'], $a['c']] <=> [$b['r'], $b['c']]);

        $numbers = [];
        foreach ($entries as $n => &$entry) {
            $start = $entry['r'] * $cols + $entry['c'];
            $numbers[$start] ??= count($numbers) + 1;
            $entry['number'] = $numbers[$start];
            $entry['id'] = $n;
            $entry['cells'] = array_map(fn ($i) => ($entry['r'] + ($entry['dir'] === 'down' ? $i : 0)) * $cols + $entry['c'] + ($entry['dir'] === 'across' ? $i : 0), array_keys(self::letters($entry['word'])));
        }
        unset($entry);

        return ['rows' => $box['bottom'] - $box['top'] + 1, 'cols' => $cols, 'entries' => $entries];
    }
}
