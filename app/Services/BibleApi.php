<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Cliente de esbiblia.net. Nunca lanza excepciones hacia la vista: ante un
 * fallo devuelve null y la página muestra un estado de error. Los capítulos
 * de versiones de dominio público se cachean durante mucho tiempo, para que
 * la lectura siga funcionando aunque el servicio externo caiga.
 */
class BibleApi
{
    private const CHAPTER_TTL = 60 * 60 * 24 * 30;

    private const SEARCH_TTL = 60 * 60;

    /**
     * @return array<string, array{name: string, testament: string, chapters: int, slug: string}>
     */
    public static function books(): array
    {
        static $books = null;

        return $books ??= collect(config('bible.books'))
            ->map(fn (array $b, string $id) => ['id' => $id, 'name' => $b[0], 'testament' => $b[1], 'chapters' => $b[2], 'slug' => Str::slug($b[0])])
            ->all();
    }

    /**
     * @return array{id: string, name: string, testament: string, chapters: int, slug: string}|null
     */
    public static function bookBySlug(string $slug): ?array
    {
        return collect(static::books())->first(fn (array $b) => $b['slug'] === $slug);
    }

    /**
     * Lo que alguien escribe en el buscador: «Juan», «juan 3», «gen 1:1»,
     * «1 Co 13:4-7». Con capítulo se admite el comienzo del nombre si es de un
     * solo libro; sin capítulo, solo el nombre completo (para no confundir una
     * palabra como «juez» con el libro Jueces). Null si no es un pasaje.
     *
     * @return array{book: array, chapter: ?int, verse: ?string}|null
     */
    public static function parseQuery(string $text): ?array
    {
        $query = static::normalize($text);

        if (preg_match('/^(.+?)\s*(\d{1,3})(?:\s*[:.,]\s*(\d{1,3})(?:\s*-\s*(\d{1,3}))?)?$/', $query, $m)
            && ($book = static::findBook($m[1], allowPrefix: true))) {
            $chapter = (int) $m[2];

            if ($chapter < 1 || $chapter > $book['chapters']) {
                return null;
            }

            $verse = ($m[3] ?? '') !== '' ? $m[3].(($m[4] ?? '') !== '' ? '-'.$m[4] : '') : null;

            return ['book' => $book, 'chapter' => $chapter, 'verse' => $verse];
        }

        $book = static::findBook($query);

        return $book ? ['book' => $book, 'chapter' => null, 'verse' => null] : null;
    }

    /** Libro por su nombre (sin tildes ni mayúsculas) o, si se permite, por un comienzo único. */
    public static function findBook(string $name, bool $allowPrefix = false): ?array
    {
        $name = static::normalize($name);

        if ($name === '') {
            return null;
        }

        $books = collect(static::books());

        if ($exact = $books->first(fn (array $b) => static::normalize($b['name']) === $name)) {
            return $exact;
        }

        $candidates = $allowPrefix ? $books->filter(fn (array $b) => str_starts_with(static::normalize($b['name']), $name)) : collect();

        return $candidates->count() === 1 ? $candidates->first() : null;
    }

    private static function normalize(string $text): string
    {
        return trim((string) preg_replace('/\s+/', ' ', Str::lower(Str::ascii($text))));
    }

    /**
     * Convierte una cita escrita («Juan 3:16», «1 Juan 1:7», «Salmos 23») en
     * libro, capítulo y versículo, para enlazarla al lector. Null si no se reconoce.
     *
     * @return array{book: array, chapter: int, verse: ?int}|null
     */
    public static function parseReference(?string $reference): ?array
    {
        $normalized = Str::lower(Str::ascii((string) $reference));

        // Nombres largos primero: «1 Juan» antes que «Juan».
        $books = collect(static::books())->sortByDesc(fn (array $b) => mb_strlen($b['name']));

        foreach ($books as $book) {
            $name = preg_quote(Str::lower(Str::ascii($book['name'])), '/');

            if (preg_match('/(?:^|[^a-z0-9])'.$name.'\s+(\d{1,3})(?::(\d{1,3}))?/', $normalized, $m)) {
                $chapter = (int) $m[1];

                if ($chapter < 1 || $chapter > $book['chapters']) {
                    return null;
                }

                return ['book' => $book, 'chapter' => $chapter, 'verse' => isset($m[2]) ? (int) $m[2] : null];
            }
        }

        return null;
    }

    /**
     * @return list<array{verse: int, text: string}>|null
     */
    public function chapter(string $bookId, int $chapter, string $version): ?array
    {
        $book = static::books()[$bookId] ?? null;

        if (! $book || $chapter < 1 || $chapter > $book['chapters']) {
            return null;
        }

        // Devuelve null si la respuesta no es válida, y null nunca se cachea.
        $fetch = function () use ($bookId, $chapter, $version) {
            $data = $this->get("/view/{$bookId}/{$chapter}/", $version);

            return isset($data['verses']) && is_array($data['verses']) ? $data : null;
        };

        $data = config("bible.versions.$version.cacheable")
            ? Cache::remember("bible:$version:$bookId:$chapter", self::CHAPTER_TTL, $fetch)
            : $fetch();

        if (! is_array($data) || ! isset($data['verses'])) {
            return null;
        }

        return array_map(fn (array $v) => ['verse' => (int) $v['verse'], 'text' => (string) $v['text']], $data['verses']);
    }

    /**
     * @return list<array{book: array, chapter: int, verse: int}>|null
     */
    public function search(string $query, string $version): ?array
    {
        $query = Str::limit(trim($query), 80, '');

        if (mb_strlen($query) < 3) {
            return [];
        }

        // Solo se cachean respuestas válidas: un fallo puntual no debe durar una hora.
        $key = 'bible:search:'.$version.':'.md5(Str::lower($query));
        $data = Cache::get($key);

        if (! is_array($data)) {
            $data = $this->get('/search/', $version, ['q' => $query]);

            if (! is_array($data) || ! isset($data['results'])) {
                return null;
            }

            Cache::put($key, $data, self::SEARCH_TTL);
        }

        $books = static::books();

        return collect($data['results'])
            ->filter(fn (array $r) => isset($books[$r['book_id']]))
            ->map(fn (array $r) => ['book' => $books[$r['book_id']], 'chapter' => (int) $r['chapter'], 'verse' => (int) $r['verse']])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function get(string $path, string $version, array $query = []): ?array
    {
        try {
            $response = Http::baseUrl(config('bible.base_url'))
                ->timeout(config('bible.timeout'))
                ->acceptJson()
                ->retry(2, 300, throw: false)
                ->get($path, $query + ['v' => $version]);

            if ($response->status() === 404) {
                return null;
            }

            return $response->throw()->json();
        } catch (ConnectionException|RequestException $e) {
            Log::warning('Biblia: fallo al consultar la API', ['path' => $path, 'error' => $e->getMessage()]);

            return null;
        }
    }
}
