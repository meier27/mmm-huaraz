<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DoctrineBook;
use App\Services\DoctrineIndexer;
use App\Services\DoctrineRenderer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Panel › Doctrinario. El PDF se sube con un formulario normal (no Livewire:
 * sus subidas temporales solo admiten imágenes) y se convierte en páginas.
 */
class DoctrineBookController extends Controller
{
    public function index(): View
    {
        $book = DoctrineBook::query()->latest('id')->first();

        return view('admin.doctrine.index', [
            'book' => $book,
            'preview' => $book?->isReady() ? collect(range(1, min(6, $book->page_count))) : collect(),
        ]);
    }

    /** Sube (o reemplaza) el PDF y lo convierte en páginas. */
    public function store(Request $request, DoctrineRenderer $renderer): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'author' => ['nullable', 'string', 'max:150'],
            // Tipo real comprobado por contenido (mimes), no por la extensión.
            'pdf' => ['required', 'file', 'mimes:pdf', 'max:40960'],
        ], [], ['title' => 'título', 'author' => 'autor', 'pdf' => 'PDF']);

        $book = DoctrineBook::query()->latest('id')->first() ?? new DoctrineBook(['is_published' => false]);
        $book->fill(['title' => $data['title'], 'author' => $data['author'] ?? null]);
        // PDF nuevo: el índice anterior ya no sirve; se detecta del nuevo al procesarlo.
        $book->forceFill(['pdf_path' => $book->pdf_path ?? 'pending', 'toc' => null])->save();

        // Nombre fijo dentro de su carpeta privada: el nombre original no se usa.
        $disk = Storage::disk(DoctrineBook::DISK);
        $disk->deleteDirectory($book->directory());
        $book->forceFill(['pdf_path' => $request->file('pdf')->storeAs($book->directory(), 'original.pdf', DoctrineBook::DISK)])->save();

        set_time_limit(600);
        $renderer->render($book);

        return redirect()->route('admin.doctrine')->with('status', $book->isReady()
            ? "PDF procesado: {$book->page_count} páginas. Revisa la vista previa y publícalo."
            : 'No se pudo procesar el PDF: '.$book->error);
    }

    public function update(Request $request, DoctrineBook $book): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'author' => ['nullable', 'string', 'max:150'],
            'is_published' => ['boolean'],
            'toc' => ['nullable', 'string', 'max:20000'],
        ], [], ['title' => 'título', 'author' => 'autor', 'toc' => 'índice']);

        [$toc, $tocError] = $this->parseToc($data['toc'] ?? '', $book);
        if ($tocError) {
            return back()->withInput()->withErrors(['toc' => $tocError]);
        }

        if (($data['is_published'] ?? false) && ! $book->isReady()) {
            return back()->withErrors(['is_published' => 'Solo se puede publicar cuando sus páginas están listas.']);
        }

        $book->update(['title' => $data['title'], 'author' => $data['author'] ?? null, 'is_published' => (bool) ($data['is_published'] ?? false)]);
        $book->forceFill(['toc' => $toc])->save();

        return redirect()->route('admin.doctrine')->with('status', $book->is_published ? 'Doctrinario publicado en /doctrina.' : 'Cambios guardados (no publicado).');
    }

    public function reprocess(DoctrineBook $book, DoctrineRenderer $renderer): RedirectResponse
    {
        set_time_limit(600);
        $renderer->render($book);

        return redirect()->route('admin.doctrine')->with('status', $book->isReady() ? 'Páginas generadas de nuevo.' : 'No se pudo procesar el PDF: '.$book->error);
    }

    /** Vuelve a detectar el índice desde el texto del PDF (reemplaza el actual). */
    public function detectIndex(DoctrineBook $book, DoctrineIndexer $indexer): RedirectResponse
    {
        $toc = $indexer->detect($book);
        $book->forceFill(['toc' => $toc])->save();

        return redirect()->route('admin.doctrine')->with('status', $toc
            ? 'Índice detectado: '.count($toc).' entradas. Revísalo y corrígelo si hace falta.'
            : 'No se encontraron lecciones en el texto del PDF: escribe el índice a mano.');
    }

    /**
     * «página | título» por línea → [{page, title}]. Devuelve [índice, error].
     *
     * @return array{0: list<array{page: int, title: string}>, 1: ?string}
     */
    private function parseToc(string $text, DoctrineBook $book): array
    {
        $entries = [];

        foreach (preg_split('/\R/u', trim($text)) as $n => $line) {
            if (trim($line) === '') {
                continue;
            }

            if (! preg_match('/^\s*(\d{1,4})\s*\|\s*(.{1,200}?)\s*$/u', $line, $m)) {
                return [[], 'Línea '.($n + 1).': usa el formato «página | título», por ejemplo «10 | Lección 4 · El único Dios».'];
            }

            if ((int) $m[1] < 1 || (int) $m[1] > $book->page_count) {
                return [[], 'Línea '.($n + 1).": la página {$m[1]} no existe (el libro tiene {$book->page_count})."];
            }

            $entries[] = ['page' => (int) $m[1], 'title' => $m[2]];
        }

        return [collect($entries)->sortBy('page')->values()->all(), null];
    }

    public function destroy(DoctrineBook $book): RedirectResponse
    {
        Storage::disk(DoctrineBook::DISK)->deleteDirectory($book->directory());
        $book->delete();

        return redirect()->route('admin.doctrine')->with('status', 'Doctrinario eliminado. La página /doctrina vuelve a mostrar el enlace de FlipHTML5.');
    }
}
