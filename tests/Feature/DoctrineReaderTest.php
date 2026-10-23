<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\DoctrineBook;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Symfony\Component\Process\Process;
use Tests\TestCase;

/**
 * Lector propio del Doctrinario: PDF privado → páginas en imagen servidas
 * solo con enlaces firmados y solo al propio sitio.
 */
class DoctrineReaderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        Storage::fake('local');
    }

    /**
     * PDF válido generado a mano (sin dependencias): N páginas «Pagina N», o
     * las líneas de texto indicadas para cada página.
     *
     * @param  int|list<list<string>>  $pages
     */
    private function pdf(int|array $pages = 3): UploadedFile
    {
        $texts = is_int($pages) ? array_map(fn ($n) => ["Pagina {$n}"], range(1, $pages)) : $pages;
        $pages = count($texts);
        $objects = ['<< /Type /Catalog /Pages 2 0 R >>'];
        $kids = [];
        $body = [];

        for ($i = 0; $i < $pages; $i++) {
            $page = 4 + $i * 2;
            $kids[] = "{$page} 0 R";
            $stream = implode(' ', array_map(fn ($line, $n) => 'BT /F1 18 Tf 72 '.(720 - $n * 28).' Td ('.$line.') Tj ET', $texts[$i], array_keys($texts[$i])));
            $body[$page] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 3 0 R >> >> /Contents '.($page + 1).' 0 R >>';
            $body[$page + 1] = '<< /Length '.strlen($stream)." >>\nstream\n{$stream}\nendstream";
        }

        $objects[] = '<< /Type /Pages /Kids ['.implode(' ', $kids)."] /Count {$pages} >>";
        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
        ksort($body);
        $objects = array_merge($objects, array_values($body));

        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $n => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($n + 1)." 0 obj\n{$object}\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= 'xref
0 '.(count($objects) + 1)."\n0000000000 65535 f \n";
        foreach ($offsets as $offset) {
            $pdf .= str_pad((string) $offset, 10, '0', STR_PAD_LEFT)." 00000 n \n";
        }
        $pdf .= 'trailer << /Size '.(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";

        return UploadedFile::fake()->createWithContent('doctrinario.pdf', $pdf);
    }

    private function requirePdftoppm(): void
    {
        $process = new Process([config('services.pdftoppm'), '-v']);
        $process->run();

        if (! str_contains($process->getErrorOutput().$process->getOutput(), 'pdftoppm')) {
            $this->markTestSkipped('pdftoppm (Poppler) no está instalado en este equipo.');
        }
    }

    /** Libro listo con páginas falsas (para probar la entrega sin convertir un PDF). */
    private function readyBook(bool $published = true, int $pages = 3): DoctrineBook
    {
        $book = new DoctrineBook(['title' => 'Instrucciones Bíblicas', 'author' => 'Luis M. Ortiz', 'is_published' => $published]);
        $book->forceFill(['pdf_path' => 'doctrine/x/original.pdf', 'status' => DoctrineBook::READY, 'page_count' => $pages, 'page_width' => 600, 'page_height' => 800])->save();

        foreach (range(1, $pages) as $n) {
            Storage::disk('local')->put($book->pagePath($n), 'webp');
        }

        return $book;
    }

    private function signed(DoctrineBook $book, int $page): string
    {
        return URL::temporarySignedRoute('doctrine.page', now()->addHour(), [$book, $page]);
    }

    public function test_admin_uploads_the_pdf_and_it_becomes_private_watermarked_pages(): void
    {
        $this->requirePdftoppm();
        $editor = User::factory()->role(Role::Editor)->create();

        $this->actingAs($editor)->post(route('admin.doctrine.store'), ['title' => 'Instrucciones Bíblicas', 'author' => 'Luis M. Ortiz', 'pdf' => $this->pdf(3)])
            ->assertRedirect(route('admin.doctrine'))
            ->assertSessionHas('status', fn ($s) => str_contains($s, '3 páginas'));

        $book = DoctrineBook::sole();
        $this->assertTrue($book->isReady());
        $this->assertSame(3, $book->page_count);
        $this->assertFalse($book->is_published); // se publica después de revisar
        Storage::disk('local')->assertExists($book->pdf_path);
        foreach ([1, 2, 3] as $n) {
            Storage::disk('local')->assertExists($book->pagePath($n));
        }
        $this->assertStringStartsWith('RIFF', Storage::disk('local')->get($book->pagePath(1))); // WebP

        // Nada queda en el disco público.
        $this->assertSame([], Storage::disk('public')->allFiles('doctrine'));
    }

    public function test_only_pdfs_are_accepted_and_only_content_roles_can_upload(): void
    {
        $editor = User::factory()->role(Role::Editor)->create();

        $this->actingAs($editor)->post(route('admin.doctrine.store'), ['title' => 'X', 'pdf' => UploadedFile::fake()->image('foto.jpg')])
            ->assertSessionHasErrors('pdf');
        $this->assertSame(0, DoctrineBook::count());

        $this->actingAs(User::factory()->role(Role::Cashier)->create())->get(route('admin.doctrine'))->assertForbidden();
        $this->actingAs(User::factory()->role(Role::Cashier)->create())
            ->post(route('admin.doctrine.store'), ['title' => 'X', 'pdf' => $this->pdf(1)])->assertForbidden();
    }

    public function test_pages_need_a_valid_signature_and_never_expose_the_pdf(): void
    {
        $book = $this->readyBook();

        $this->get($this->signed($book, 1))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/webp')
            ->assertHeader('Cross-Origin-Resource-Policy', 'same-origin')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noimageindex');

        $this->get(route('doctrine.page', [$book, 1]))->assertForbidden();                       // sin firma
        $this->get($this->signed($book, 1).'x')->assertForbidden();                             // firma alterada
        $this->get(URL::temporarySignedRoute('doctrine.page', now()->subMinute(), [$book, 1]))->assertForbidden(); // caducada
        $this->get($this->signed($book, 4))->assertNotFound();                                   // fuera de rango

        $html = $this->get('/doctrina')->assertOk()->getContent();
        $this->assertStringNotContainsString('original.pdf', $html);
        $this->assertStringNotContainsString('fliphtml5', $html); // con libro propio ya no se usa FlipHTML5
        $this->assertStringContainsString('signature=', $html);
    }

    public function test_other_sites_cannot_embed_or_hotlink_the_pages(): void
    {
        $book = $this->readyBook();
        $url = $this->signed($book, 2);

        $this->get($url, ['Sec-Fetch-Site' => 'cross-site'])->assertForbidden();
        $this->get($url, ['Sec-Fetch-Site' => 'same-site'])->assertForbidden();
        $this->get($url, ['Referer' => 'https://otro-sitio.example/pagina'])->assertForbidden();
        $this->get($url, ['Sec-Fetch-Site' => 'same-origin'])->assertOk();
    }

    public function test_unpublished_book_is_only_visible_to_content_managers(): void
    {
        $book = $this->readyBook(published: false);

        $this->get($this->signed($book, 1))->assertNotFound();
        $this->get('/doctrina')->assertSee('fliphtml5', false); // sigue el libro anterior

        $this->actingAs(User::factory()->role(Role::Editor)->create())->get($this->signed($book, 1))->assertOk();
    }

    public function test_publishing_requires_ready_pages_and_deleting_removes_files(): void
    {
        $editor = User::factory()->role(Role::Editor)->create();
        $failed = new DoctrineBook(['title' => 'Roto']);
        $failed->forceFill(['pdf_path' => 'doctrine/1/original.pdf', 'status' => DoctrineBook::FAILED])->save();

        $this->actingAs($editor)->put(route('admin.doctrine.update', $failed), ['title' => 'Roto', 'is_published' => 1])
            ->assertSessionHasErrors('is_published');
        $this->assertFalse($failed->fresh()->is_published);

        $book = $this->readyBook(published: false);
        $this->actingAs($editor)->put(route('admin.doctrine.update', $book), ['title' => 'Doctrinario', 'is_published' => 1])->assertRedirect();
        $this->assertTrue($book->fresh()->is_published);
        $this->get('/doctrina')->assertSee('Doctrinario')->assertSee('doctrineBook(', false)
            ->assertSee('aria-label="Zoom de la página"', false)->assertSee('Acercar')->assertSee('Alejar');

        $this->actingAs($editor)->delete(route('admin.doctrine.destroy', $book))->assertRedirect();
        $this->assertModelMissing($book);
        Storage::disk('local')->assertMissing($book->pagePath(1));
    }

    public function test_the_index_is_detected_from_the_pdf_text_and_shown_in_the_reader(): void
    {
        $this->requirePdftoppm();
        $editor = User::factory()->role(Role::Editor)->create();
        $pdf = $this->pdf([
            ['Portada'],
            ['CONTENIDO', '1. La Biblia', '2. El unico Dios'],
            ['LECCION UNA', 'LA BIBLIA', 'Texto de la leccion.'],
            ['Sigue la leccion uno.', 'LECCION DOS', 'EL UNICO DIOS'],
        ]);

        $this->actingAs($editor)->post(route('admin.doctrine.store'), ['title' => 'Doctrinario', 'pdf' => $pdf])->assertRedirect();

        $book = DoctrineBook::sole();
        // La lección 2 empieza a mitad de la página 4; los títulos salen del «CONTENIDO».
        $this->assertSame([['page' => 3, 'title' => 'Lección 1 · La Biblia'], ['page' => 4, 'title' => 'Lección 2 · El unico Dios']], $book->index());

        $book->update(['is_published' => true]);
        $this->get('/doctrina')->assertOk()->assertSee('La Biblia')->assertSee('El unico Dios')->assertSee('x-ref="index"', false); // el índice va en el JSON del lector
    }

    public function test_the_index_can_be_corrected_in_the_panel(): void
    {
        $book = $this->readyBook(pages: 12);
        $editor = User::factory()->role(Role::Editor)->create();

        $this->actingAs($editor)->put(route('admin.doctrine.update', $book), [
            'title' => 'Doctrinario', 'is_published' => 1,
            'toc' => "10 | Lección 2 · La oración\n\n  3 |  Lección 1 · La Biblia  ",
        ])->assertRedirect(route('admin.doctrine'));

        // Ordenado por página y sin espacios sobrantes.
        $this->assertSame([['page' => 3, 'title' => 'Lección 1 · La Biblia'], ['page' => 10, 'title' => 'Lección 2 · La oración']], $book->fresh()->index());

        // Formato o página inválidos: no se guarda nada.
        foreach (['Lección sin página', '99 | Página que no existe'] as $bad) {
            $this->actingAs($editor)->put(route('admin.doctrine.update', $book), ['title' => 'Doctrinario', 'is_published' => 1, 'toc' => $bad])
                ->assertSessionHasErrors('toc');
        }
        $this->assertCount(2, $book->fresh()->index());
    }
}
