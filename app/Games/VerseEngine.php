<?php

namespace App\Games;

use App\Models\GameAttempt;
use Illuminate\Support\Arr;

/**
 * Completa el versículo: más versículos, más espacios en blanco y palabras de
 * más (que no van) a medida que sube el nivel.
 */
class VerseEngine extends Engine
{
    /** @return array{verses: int, blanks: int, extra: int} */
    public static function params(int $level): array
    {
        return ['verses' => 1 + self::stage($level, 10), 'blanks' => 2 + self::stage($level, 4), 'extra' => self::stage($level, 6)];
    }

    public function describe(int $level): string
    {
        $p = self::params($level);

        return ($p['verses'] === 1 ? '1 versículo' : "{$p['verses']} versículos").", hasta {$p['blanks']} palabras".($p['extra'] ? ", {$p['extra']} de más" : '');
    }

    public function start(int $level): array
    {
        $p = self::params($level);
        $pool = $this->catalog->verses();
        $verses = $pool->shuffle()->take($p['verses'])->values();
        $public = [];
        $secret = [];

        foreach ($verses as $verse) {
            $tokens = $this->tokens($verse['text']);
            $candidates = array_keys(array_filter($tokens, fn ($t) => isset($t['word']) && mb_strlen($t['word']) >= 4));
            $candidates = $candidates ?: array_keys(array_filter($tokens, fn ($t) => isset($t['word'])));
            $chosen = $this->spread($candidates, min($p['blanks'], max(1, (int) ceil(count($candidates) * 0.6))));

            $words = [];
            foreach ($chosen as $n => $i) {
                $words[] = $tokens[$i]['word'];
                $tokens[$i] = ['pre' => $tokens[$i]['pre'], 'post' => $tokens[$i]['post'], 'blank' => $n];
            }

            // Palabras de más: de otros versículos, que no coincidan con las que faltan.
            $extra = $pool->flatMap(fn ($v) => array_column($this->tokens($v['text']), 'word'))
                ->filter(fn ($w) => $w !== null && mb_strlen($w) >= 4 && ! in_array(self::normalize($w), array_map(self::normalize(...), $words), true))
                ->unique()->shuffle()->take($p['extra'])->all();

            $public[] = ['tokens' => $tokens, 'chips' => Arr::shuffle([...$words, ...$extra])];
            $secret[] = ['words' => $words, 'reference' => $verse['reference']];
        }

        return ['public' => ['verses' => $public], 'secret' => ['verses' => $secret], 'state' => ['solved' => 0, 'mistakes' => 0]];
    }

    public function act(GameAttempt $attempt, string $action, array $input): array
    {
        $state = $attempt->state;
        $verse = $attempt->secret['verses'][$state['solved']] ?? null;
        $given = $input['words'] ?? null;

        if ($action !== 'check' || ! $verse || ($input['verse'] ?? null) !== $state['solved'] || ! is_array($given) || count($given) !== count($verse['words'])) {
            $this->fail('Completa todos los espacios del versículo.');
        }

        $results = array_map(fn ($word, $i) => is_string($given[$i] ?? null) && self::normalize($given[$i]) === self::normalize($word), $verse['words'], array_keys($verse['words']));
        $wrong = count(array_filter($results, fn ($ok) => ! $ok));
        $state['mistakes'] += $wrong;

        if ($wrong === 0) {
            $state['solved']++;
        }
        $attempt->state = $state;

        return [
            'results' => $results,
            'solved' => $wrong === 0,
            'reference' => $wrong === 0 ? $verse['reference'] : null,
            'mistakes' => $state['mistakes'],
            'done' => $state['solved'] === count($attempt->secret['verses']),
        ];
    }

    public function stars(GameAttempt $attempt): int
    {
        $mistakes = $attempt->state['mistakes'];

        return $mistakes === 0 ? 3 : ($mistakes <= 2 ? 2 : 1);
    }

    /** «Y vosotros, padres,» → [texto | {pre, word, post}] */
    private function tokens(string $text): array
    {
        return array_map(function (string $part) {
            return preg_match('/^([^\p{L}\p{N}]*)([\p{L}\p{N}]+(?:[-\'’][\p{L}\p{N}]+)*)([^\p{L}\p{N}]*)$/u', $part, $m)
                ? ['pre' => $m[1], 'word' => $m[2], 'post' => $m[3]]
                : ['text' => $part];
        }, preg_split('/(\s+)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY));
    }

    /** Elige $count posiciones repartidas a lo largo de la lista (una por tramo). */
    private function spread(array $candidates, int $count): array
    {
        $count = min($count, count($candidates));
        $step = count($candidates) / max(1, $count);

        return array_map(function ($k) use ($candidates, $step) {
            $from = (int) floor($k * $step);
            $to = max($from + 1, (int) floor(($k + 1) * $step));

            return $candidates[random_int($from, $to - 1)];
        }, range(0, $count - 1));
    }
}
