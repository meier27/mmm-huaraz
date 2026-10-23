<?php

namespace App\Services;

use App\Enums\GameItemType;
use App\Games\BooksEngine;
use App\Games\CrosswordEngine;
use App\Games\Engine;
use App\Games\PuzzleEngine;
use App\Games\TriviaEngine;
use App\Games\VerseEngine;
use App\Games\WordSearchEngine;
use App\Models\Banner;
use App\Models\GalleryPhoto;
use App\Models\GameItem;
use App\Models\Motto;
use App\Models\Verse;
use Illuminate\Support\Collection;

/**
 * Catálogo de los juegos bíblicos y el contenido con el que se arman sus
 * niveles: Panel › Juegos (preguntas y palabras), Versículos y Fotos.
 */
class GameCatalog
{
    /** Niveles de cada juego. */
    public const LEVELS = 30;

    /** Niveles que se pueden probar sin cuenta (no se guardan). */
    public const GUEST_LEVELS = 3;

    /** slug => [vista, título, descripción, icono, contenido mínimo para jugar, motor] */
    public const GAMES = [
        'crucigrama' => ['crossword', 'Crucigrama bíblico', 'Resuelve las pistas y completa la cuadrícula.', 'grid', 4, CrosswordEngine::class],
        'sopa-de-letras' => ['wordsearch', 'Sopa de letras', 'Encuentra los nombres y lugares escondidos.', 'search', 4, WordSearchEngine::class],
        'trivia' => ['trivia', 'Trivia bíblica', 'Preguntas con su cita para comprobar la respuesta.', 'help', 4, TriviaEngine::class],
        'completa-el-versiculo' => ['verse', 'Completa el versículo', 'Pon cada palabra en su lugar y memoriza la Palabra.', 'quote', 1, VerseEngine::class],
        'ordena-los-libros' => ['books', 'Ordena los libros', 'Toca los libros de la Biblia en el orden correcto.', 'book', 1, BooksEngine::class],
        'rompecabezas' => ['puzzle', 'Rompecabezas', 'Arma la imagen y descubre un versículo.', 'puzzle', 1, PuzzleEngine::class],
    ];

    /** @return Collection<string, array{slug: string, view: string, title: string, text: string, icon: string, ready: bool}> */
    public function catalog(): Collection
    {
        return collect(self::GAMES)->map(function (array $game, string $slug) {
            [$view, $title, $text, $icon, $minimum] = $game;

            return ['slug' => $slug, 'view' => $view, 'title' => $title, 'text' => $text, 'icon' => $icon, 'ready' => $this->amount($view) >= $minimum];
        });
    }

    public function engine(string $slug): Engine
    {
        return app(self::GAMES[$slug][5]);
    }

    private function amount(string $view): int
    {
        return match ($view) {
            'crossword', 'wordsearch' => $this->words()->count(),
            'trivia' => $this->questions()->count(),
            'verse' => $this->verses()->count(),
            'books' => count(BibleApi::books()),
            'puzzle' => $this->images()->count(),
        };
    }

    /** @return Collection<int, array{word: string, display: string, clue: string, reference: string}> */
    public function words(): Collection
    {
        return once(fn () => GameItem::query()->published()->ofType(GameItemType::Word)->ordered()->get()
            ->map(fn (GameItem $item) => ['word' => GameItem::gridWord($item->answer), 'display' => $item->answer, 'clue' => $item->prompt, 'reference' => (string) $item->reference])
            ->filter(fn ($w) => mb_strlen($w['word']) >= 3)
            ->unique('word')
            ->values());
    }

    /** @return Collection<int, array{prompt: string, answer: string, options: list<string>, reference: string}> */
    public function questions(): Collection
    {
        return once(fn () => GameItem::query()->published()->ofType(GameItemType::Question)->ordered()->get()
            ->filter(fn (GameItem $item) => count($item->options ?? []) >= 1)
            ->map(fn (GameItem $item) => ['prompt' => $item->prompt, 'answer' => $item->answer, 'options' => array_values($item->options), 'reference' => (string) $item->reference])
            ->values());
    }

    /** Versículos de Panel › Versículos y el del lema vigente. */
    public function verses(): Collection
    {
        return once(function () {
            $verses = Verse::query()->published()->ordered()->get()
                ->map(fn (Verse $v) => ['text' => $v->text, 'reference' => $v->reference.($v->version ? " ({$v->version})" : '')]);

            $motto = Motto::current();
            if ($motto?->verse_text && $motto->verse_reference) {
                $verses->push(['text' => $motto->verse_text, 'reference' => $motto->verse_reference]);
            }

            return $verses->unique('text')->values();
        });
    }

    /** Imágenes del rompecabezas: fotos «Nuestra familia», lema y banners. */
    public function images(): Collection
    {
        return once(fn () => collect()
            ->merge(GalleryPhoto::query()->published()->whereNotNull('image_path')->ordered()->get())
            ->merge(array_filter([Motto::current()]))
            ->merge(Banner::query()->published()->ordered()->get())
            ->filter(fn ($model) => $model->imageUrl())
            ->map(fn ($model) => ['url' => $model->imageUrl(), 'alt' => $model->image_alt ?? ''])
            ->unique('url')
            ->take(12)
            ->values());
    }

    /** @return list<array{name: string, testament: string}> en el orden de la Biblia */
    public function books(): array
    {
        return array_values(array_map(fn ($b) => ['name' => $b['name'], 'testament' => $b['testament']], BibleApi::books()));
    }
}
