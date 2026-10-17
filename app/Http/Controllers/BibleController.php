<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use App\Models\Verse;
use App\Services\BibleApi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BibleController extends Controller
{
    public function __construct(private readonly BibleApi $bible) {}

    public function index(): View
    {
        $books = collect(BibleApi::books());
        $verse = Verse::query()->published()->ordered()->first();

        return view('bible.index', [
            'oldTestament' => $books->where('testament', 'AT')->values(),
            'newTestament' => $books->where('testament', 'NT')->values(),
            'verse' => $verse,
            // Enlace del versículo del día a su capítulo, si la cita se reconoce.
            'verseLocation' => BibleApi::parseReference($verse?->reference),
            'versionName' => $this->versionName(),
            'versionShort' => $this->versionShort(),
            // Solo la RVR1909 es de dominio público (las cacheables, ver config/bible.php).
            'publicDomain' => (bool) config('bible.versions.'.SiteSetting::current()->bibleVersion().'.cacheable'),
        ]);
    }

    public function chapter(string $book, int $chapter = 1): View
    {
        $bookData = BibleApi::bookBySlug($book) ?? abort(404);
        abort_if($chapter < 1 || $chapter > $bookData['chapters'], 404);

        $version = SiteSetting::current()->bibleVersion();

        return view('bible.chapter', [
            'book' => $bookData,
            'chapter' => $chapter,
            'verses' => $this->bible->chapter($bookData['id'], $chapter, $version),
            'versionName' => $this->versionName(),
            'highlight' => (int) request()->query('v'),
            'versionShort' => $this->versionShort(),
            // Para el selector de libro y capítulo.
            'books' => collect(BibleApi::books())->map(fn ($b) => ['name' => $b['name'], 'slug' => $b['slug'], 'chapters' => $b['chapters'], 'testament' => $b['testament']])->values(),
            'previous' => $this->neighbour($bookData, $chapter, -1),
            'next' => $this->neighbour($bookData, $chapter, 1),
        ]);
    }

    /**
     * Capítulo anterior o siguiente, pasando al libro contiguo en los extremos.
     *
     * @return array{book: array, chapter: int}|null
     */
    private function neighbour(array $book, int $chapter, int $step): ?array
    {
        $target = $chapter + $step;

        if ($target >= 1 && $target <= $book['chapters']) {
            return ['book' => $book, 'chapter' => $target];
        }

        $ids = array_keys(BibleApi::books());
        $other = $ids[array_search($book['id'], $ids, true) + $step] ?? null;

        if ($other === null) {
            return null;
        }

        $otherBook = BibleApi::books()[$other];

        return ['book' => $otherBook, 'chapter' => $step > 0 ? 1 : $otherBook['chapters']];
    }

    public function search(Request $request): View|RedirectResponse
    {
        $query = trim((string) $request->query('q', ''));

        if ($query === '') {
            return redirect()->route('bible.index');
        }

        $results = mb_strlen($query) >= 3
            ? $this->bible->search($query, SiteSetting::current()->bibleVersion())
            : [];

        return view('bible.search', [
            'query' => $query,
            'results' => $results,
            'tooShort' => mb_strlen($query) < 3,
            'versionName' => $this->versionName(),
        ]);
    }

    private function versionShort(): string
    {
        return config('bible.versions.'.SiteSetting::current()->bibleVersion().'.short');
    }

    private function versionName(): string
    {
        return config('bible.versions.'.SiteSetting::current()->bibleVersion().'.name');
    }
}
