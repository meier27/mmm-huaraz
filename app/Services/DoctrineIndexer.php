<?php

namespace App\Services;

use App\Models\DoctrineBook;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

/**
 * Arma el índice del Doctrinario leyendo el texto del PDF (pdftotext, de Poppler):
 * busca dónde empieza cada lección («LECCIÓN UNA», «Lección 4», «CAPÍTULO…»)
 * y toma los títulos bien escritos de la página «CONTENIDO»/«ÍNDICE», si la hay.
 * El resultado se puede corregir en el panel.
 */
class DoctrineIndexer
{
    private const NUMBERS = [
        'uno' => 1, 'una' => 1, 'primera' => 1, 'dos' => 2, 'segunda' => 2, 'tres' => 3, 'tercera' => 3, 'cuatro' => 4, 'cinco' => 5,
        'seis' => 6, 'siete' => 7, 'ocho' => 8, 'nueve' => 9, 'diez' => 10, 'once' => 11, 'doce' => 12, 'trece' => 13,
        'catorce' => 14, 'quince' => 15, 'dieciseis' => 16, 'diecisiete' => 17, 'dieciocho' => 18, 'diecinueve' => 19,
        'veinte' => 20, 'veintiuno' => 21, 'veintiuna' => 21, 'veintidos' => 22, 'veintitres' => 23, 'veinticuatro' => 24,
        'veinticinco' => 25, 'veintiseis' => 26, 'veintisiete' => 27, 'veintiocho' => 28, 'veintinueve' => 29, 'treinta' => 30,
    ];

    /** Secciones sueltas que también van al índice si encabezan una página. */
    private const SECTIONS = ['introduccion', 'prologo', 'prefacio', 'conclusion', 'epilogo', 'apendice', 'glosario', 'bibliografia'];

    /** @return list<array{page: int, title: string}> */
    public function detect(DoctrineBook $book): array
    {
        $pages = $this->pagesText($book);

        if ($pages === []) {
            return [];
        }

        [$contents, $contentsPage] = $this->contents($pages);
        $entries = [];

        foreach ($pages as $i => $text) {
            if ($i === $contentsPage) {
                continue; // la propia página del índice impreso
            }

            $lines = array_values(array_filter(array_map('trim', preg_split('/\R/u', $text)), fn ($l) => $l !== ''));

            // Una lección puede empezar a mitad de página: se revisan todas las líneas,
            // pero solo cuenta una línea que sea entera un encabezado («LECCIÓN DOS»).
            foreach ($lines as $j => $line) {
                $plain = Str::lower(Str::ascii($line));

                if (preg_match('/^(leccion|capitulo|tema|parte)\s+(\d{1,3}|[a-z]+)$/', $plain, $m)) {
                    $number = ctype_digit($m[2]) ? (int) $m[2] : (self::NUMBERS[$m[2]] ?? null);
                    $heading = $lines[$j + 1] ?? '';
                    $title = ($number && isset($contents[$number])) ? $contents[$number] : $this->pretty($heading);
                    $entries[] = ['page' => $i + 1, 'title' => Str::ucfirst($m[1] === 'leccion' ? 'Lección' : $m[1]).($number ? " {$number}" : '').($title !== '' ? " · {$title}" : '')];

                    continue;
                }

                if (in_array(trim($plain, ' .:'), self::SECTIONS, true)) {
                    $entries[] = ['page' => $i + 1, 'title' => $contents[trim($plain, ' .:')] ?? $this->pretty($line)];
                }
            }
        }

        return $entries;
    }

    /** Texto de cada página (pdftotext separa las páginas con un salto de página). */
    private function pagesText(DoctrineBook $book): array
    {
        $binary = config('services.pdftotext') ?: preg_replace('/pdftoppm(\.exe)?$/i', 'pdftotext$1', (string) config('services.pdftoppm'));
        $process = new Process([$binary, '-enc', 'UTF-8', Storage::disk(DoctrineBook::DISK)->path($book->pdf_path), '-']);
        $process->setTimeout(120)->run();

        if (! $process->isSuccessful()) {
            return [];
        }

        $pages = explode("\f", $process->getOutput());

        // Tras el último salto queda una «página» vacía.
        return array_slice($pages, 0, $book->page_count ?: count($pages));
    }

    /**
     * Títulos de la página «CONTENIDO»/«ÍNDICE»: número de lección => título,
     * y las secciones sueltas por su nombre («introduccion» => «Introducción»).
     *
     * @param  list<string>  $pages
     * @return array{0: array<int|string, string>, 1: ?int} títulos y posición (desde 0) de esa página
     */
    private function contents(array $pages): array
    {
        foreach (array_slice($pages, 0, 8, true) as $index => $text) {
            if (! preg_match('/^\s*(contenido|indice|índice)\s*$/miu', $text)) {
                continue;
            }

            $titles = [];
            foreach (preg_split('/\R/u', $text) as $line) {
                $line = trim($line);

                if (preg_match('/^(\d{1,3})[.)]\s+(.+?)(?:\s*\.{2,}\s*\d+)?$/u', $line, $m)) {
                    $titles[(int) $m[1]] = $m[2];
                } elseif (in_array($key = Str::lower(Str::ascii($line)), self::SECTIONS, true)) {
                    $titles[$key] = $line;
                }
            }

            return [$titles, $index];
        }

        return [[], null];
    }

    /** «EL ÚNICO DIOS» → «El único dios» (solo si el título viene todo en mayúsculas). */
    private function pretty(string $text): string
    {
        $text = trim($text);

        return $text !== '' && mb_strtoupper($text) === $text ? Str::ucfirst(mb_strtolower($text)) : $text;
    }
}
