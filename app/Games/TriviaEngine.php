<?php

namespace App\Games;

use App\Models\GameAttempt;

/**
 * Trivia: de 5 preguntas (nivel 1) a 9 (nivel 25 en adelante). Se responde
 * una por una y el servidor dice si es correcta; para superar el nivel hay que
 * acertar al menos el 60 %.
 */
class TriviaEngine extends Engine
{
    public static function count(int $level): int
    {
        return 5 + self::stage($level, 6);
    }

    public function describe(int $level): string
    {
        return self::count($level).' preguntas';
    }

    public function start(int $level): array
    {
        $questions = $this->catalog->questions()->shuffle()->take(self::count($level))->values();

        return [
            'public' => ['questions' => $questions->map(fn ($q) => ['prompt' => $q['prompt'], 'choices' => collect([$q['answer'], ...$q['options']])->shuffle()->values()->all()])->all()],
            'secret' => ['answers' => $questions->map(fn ($q) => ['answer' => $q['answer'], 'reference' => $q['reference']])->all()],
            'state' => ['results' => []],
        ];
    }

    public function act(GameAttempt $attempt, string $action, array $input): array
    {
        $state = $attempt->state;
        $answers = $attempt->secret['answers'];
        $index = count($state['results']);

        if ($action !== 'answer' || ($input['index'] ?? null) !== $index || ! isset($answers[$index])) {
            $this->fail('Responde las preguntas en orden.');
        }

        $correct = ($input['choice'] ?? null) === $answers[$index]['answer'];
        $state['results'][] = $correct;
        $attempt->state = $state;

        return [
            'correct' => $correct,
            'answer' => $answers[$index]['answer'],
            'reference' => $answers[$index]['reference'],
            'score' => count(array_filter($state['results'])),
            'done' => count($state['results']) === count($answers),
        ];
    }

    public function stars(GameAttempt $attempt): int
    {
        $results = $attempt->state['results'];
        $rate = count(array_filter($results)) / max(1, count($results));

        return match (true) {
            $rate >= 1 => 3,
            $rate >= 0.8 => 2,
            $rate >= 0.6 => 1,
            default => 0,
        };
    }
}
