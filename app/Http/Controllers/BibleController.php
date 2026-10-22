<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use App\Models\Verse;
use App\Services\BibleApi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BibleController extends Controller
{
    public function __construct(private readonly BibleApi $bible) {}

    public function index(Request $request): View
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
            // «?libro=juan» (desde el buscador): abre ese libro para elegir capítulo.
            'openBook' => BibleApi::bookBySlug((string) $request->query('libro', ''))['slug'] ?? null,
            'books' => $this->pickerBooks(),
        ]);
    }

    public function chapter(string $book, int $chapter = 1): View
    {
        $bookData = BibleApi::bookBySlug($book) ?? abort(404);
        abort_if($chapter < 1 || $chapter > $bookData['chapters'], 404);

        $version = SiteSetting::current()->bibleVersion();
        $verses = $this->bible->chapter($bookData['id'], $chapter, $version);

        return view('bible.chapter', [
            'book' => $bookData,
            'chapter' => $chapter,
            'verses' => $verses,
            'versionName' => $this->versionName(),
            'highlight' => $this->highlightRange((string) request()->query('v', ''), $verses ?? []),
            'versionShort' => $this->versionShort(),
            // Para el selector de libro, capítulo y versículo.
            'books' => $this->pickerBooks(),
            'previous' => $this->neighbour($bookData, $chapter, -1),
            'next' => $this->neighbour($bookData, $chapter, 1),
        ]);
    }

    /** Cantidad de versículos de un capítulo (para elegir versículo en el selector). */
    public function verseCount(string $book, int $chapter): JsonResponse
    {
        $bookData = BibleApi::bookBySlug($book) ?? abort(404);
        abort_if($chapter < 1 || $chapter > $bookData['chapters'], 404);

        $verses = $this->bible->chapter($bookData['id'], $chapter, SiteSetting::current()->bibleVersion());
        abort_if($verses === null, 503);

        // El número más alto (algunas versiones omiten versículos dentro del capítulo).
        return response()->json(['count' => (int) collect($verses)->max('verse')]);
    }

    /**
     * «?v=16» o «?v=16-18» → [desde, hasta]; null si no es válido o el
     * versículo inicial no existe en el capítulo.
     *
     * @param  list<array{verse: int, text: string}>  $verses
     * @return array{0: int, 1: int}|null
     */
    private function highlightRange(string $value, array $verses): ?array
    {
        if (! preg_match('/^(\d{1,3})(?:-(\d{1,3}))?$/', $value, $m)) {
            return null;
        }

        $from = (int) $m[1];
        $to = isset($m[2]) ? (int) $m[2] : $from;

        $numbers = array_column($verses, 'verse');

        return in_array($from, $numbers, true) && $to >= $from && $to <= max($numbers ?: [0]) ? [$from, $to] : null;
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

        // Un pasaje («Juan 3:16») o un libro («Juan») no se busca como palabra: se abre.
        if ($passage = BibleApi::parseQuery($query)) {
            return $passage['chapter']
                ? redirect()->to(route('bible.chapter', [$passage['book']['slug'], $passage['chapter']])
                    .($passage['verse'] ? '?v='.$passage['verse'].'#v'.strtok($passage['verse'], '-') : ''))
                : redirect()->to(route('bible.index', ['libro' => $passage['book']['slug']]).'#libros');
        }

        $results = mb_strlen($query) >= 3
            ? $this->bible->search($query, SiteSetting::current()->bibleVersion())
            : [];

        return view('bible.search', [
            'query' => $query,
            'results' => $results,
            'tooShort' => mb_strlen($query) < 3,
            'versionName' => $this->versionName(),
            'books' => $this->pickerBooks(),
        ]);
    }

    /** Datos mínimos de los 66 libros para el selector y el buscador. */
    private function pickerBooks(): array
    {
        return collect(BibleApi::books())
            ->map(fn ($b) => ['name' => $b['name'], 'slug' => $b['slug'], 'chapters' => $b['chapters'], 'testament' => $b['testament']])
            ->values()->all();
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
