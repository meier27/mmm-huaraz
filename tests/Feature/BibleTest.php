<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\Verse;
use App\Services\BibleApi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BibleTest extends TestCase
{
    use RefreshDatabase;

    private function fakeChapter(): void
    {
        Http::fake([
            'esbiblia.net/api/view/JHN/3/*' => Http::response(['verses' => [
                ['verse' => 16, 'text' => 'Porque de tal manera amó Dios al mundo'],
                ['verse' => 17, 'text' => 'Porque no envió Dios a su Hijo'],
            ]]),
        ]);
    }

    public function test_index_lists_the_66_books_without_calling_the_api(): void
    {
        Http::preventStrayRequests();

        $this->get('/biblia')->assertOk()->assertSee('Génesis')->assertSee('Apocalipsis')->assertSee('Reina-Valera 1909');
        $this->assertCount(66, config('bible.books'));
    }

    public function test_chapter_is_rendered_and_cached_for_the_public_domain_version(): void
    {
        $this->fakeChapter();

        $this->get('/biblia/juan/3')->assertOk()->assertSee('Juan 3')->assertSee('Porque de tal manera amó Dios al mundo');
        $this->get('/biblia/juan/3')->assertOk();

        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'v=rvr'));
    }

    public function test_versions_with_reserved_rights_are_not_cached(): void
    {
        $this->fakeChapter();
        SiteSetting::first()->update(['bible_version' => 'rva']);

        $this->get('/biblia/juan/3')->assertOk()->assertSee('Reina-Valera Actualizada');
        $this->get('/biblia/juan/3')->assertOk();

        Http::assertSentCount(2);
    }

    public function test_navigation_crosses_book_boundaries(): void
    {
        Http::fake(['*' => Http::response(['verses' => [['verse' => 1, 'text' => 'x']]])]);

        $this->get('/biblia/malaquias/4')->assertSee(route('bible.chapter', ['mateo', 1]), false)->assertSee(route('bible.chapter', ['malaquias', 3]), false);
        $this->get('/biblia/mateo/1')->assertSee(route('bible.chapter', ['malaquias', 4]), false);
    }

    public function test_unknown_book_and_out_of_range_chapter_return_404_without_calling_the_api(): void
    {
        Http::preventStrayRequests();

        $this->get('/biblia/libro-inventado/1')->assertNotFound();
        $this->get('/biblia/judas/2')->assertNotFound();
        $this->get('/biblia/genesis/0')->assertNotFound();
    }

    public function test_api_failure_shows_an_error_and_is_not_cached(): void
    {
        $down = true;
        Http::fake(function () use (&$down) {
            return $down ? Http::response('error', 500) : Http::response(['verses' => [['verse' => 16, 'text' => 'Porque de tal manera amó Dios al mundo']]]);
        });

        $this->get('/biblia/juan/3')->assertOk()->assertSee('No pudimos cargar este capítulo');

        $down = false;
        $this->get('/biblia/juan/3')->assertSee('Porque de tal manera amó Dios al mundo');
    }

    public function test_search_links_results_to_the_highlighted_verse(): void
    {
        Http::fake(['esbiblia.net/api/search/*' => Http::response(['results' => [
            ['book_id' => 'JHN', 'chapter' => 3, 'verse' => 16],
            ['book_id' => 'XXX', 'chapter' => 1, 'verse' => 1],
        ]])]);

        $this->get('/biblia/buscar?q=amó')
            ->assertOk()
            ->assertSee('1 versículo encontrado')
            ->assertSee('Juan 3:16')
            ->assertSee(route('bible.chapter', ['juan', 3]).'?v=16#v16', false);
    }

    public function test_short_or_empty_search_does_not_call_the_api(): void
    {
        Http::preventStrayRequests();

        $this->get('/biblia/buscar?q=ab')->assertOk()->assertSee('al menos 3 letras');
        $this->get('/biblia/buscar?q=')->assertRedirect(route('bible.index'));
    }

    public function test_search_failure_is_reported_and_not_cached(): void
    {
        $down = true;
        Http::fake(function () use (&$down) {
            return $down ? Http::response('error', 500) : Http::response(['results' => []]);
        });

        $this->get('/biblia/buscar?q=esperanza')->assertSee('no está disponible');

        $down = false;
        $this->get('/biblia/buscar?q=esperanza')->assertSee('No encontramos versículos');
    }

    public function test_references_are_parsed_to_book_chapter_and_verse(): void
    {
        $this->assertSame(['juan', 3, 16], $this->parsed('Juan 3:16'));
        $this->assertSame(['1-juan', 1, 7], $this->parsed('1 Juan 1:7')); // «1 Juan» antes que «Juan»
        $this->assertSame(['hechos', 16, 31], $this->parsed('[Ejemplo] Hechos 16:31'));
        $this->assertSame(['salmos', 23, null], $this->parsed('Salmos 23'));
        $this->assertSame(['genesis', 1, 1], $this->parsed('génesis 1:1'));
        $this->assertNull(BibleApi::parseReference('Juan 30:1')); // capítulo inexistente
        $this->assertNull(BibleApi::parseReference('Texto sin cita'));
    }

    /** @return array{0: string, 1: int, 2: ?int}|null */
    private function parsed(string $reference): ?array
    {
        $r = BibleApi::parseReference($reference);

        return $r ? [$r['book']['slug'], $r['chapter'], $r['verse']] : null;
    }

    public function test_verse_of_the_day_links_to_its_chapter_and_the_note_is_honest_about_rights(): void
    {
        Http::preventStrayRequests();
        Verse::create(['reference' => 'Juan 3:16', 'text' => 'Porque de tal manera amó Dios al mundo', 'is_published' => true]);

        $this->get('/biblia')
            ->assertSee('Leer ahora')
            ->assertSee(route('bible.chapter', ['juan', 3]).'?v=16#v16', false)
            ->assertSee('de dominio público');

        SiteSetting::first()->update(['bible_version' => 'rva']);

        $this->get('/biblia')->assertDontSee('de dominio público')->assertSee('RVA');
    }

    public function test_reader_has_passage_picker_version_badge_and_selectable_verses(): void
    {
        $this->fakeChapter();

        $this->get('/biblia/juan/3')
            ->assertOk()
            ->assertSee('aria-controls="selector-pasaje"', false)
            ->assertSee('RVR1909')
            ->assertSee('data-verse="16"', false)
            ->assertSee('Apocalipsis'); // libros del selector
    }

    /** Capítulo simulado de N versículos (1..N). */
    private function fakeLongChapter(int $verses = 25): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'esbiblia.net/api/view/SAL/23/*' => Http::response(['verses' => array_map(
                fn (int $n) => ['verse' => $n, 'text' => "Texto del versículo {$n}"], range(1, $verses),
            )]),
        ]);
    }

    /** El <span> de un versículo, para comprobar sus atributos. */
    private function verseTag(string $html, int $n): string
    {
        preg_match('/<span id="v'.$n.'"[^>]*>/', $html, $m);

        return $m[0] ?? '';
    }

    /** Clases que el servidor pone al versículo (sin las de Alpine en «:class»). */
    private function verseClass(string $html, int $n): string
    {
        preg_match('/\sclass="([^"]*)"/', $this->verseTag($html, $n), $m);

        return $m[1] ?? '';
    }

    public function test_linked_verse_is_shaded_and_its_page_is_shown(): void
    {
        $this->fakeLongChapter();

        $html = $this->get('/biblia/salmos/23?v=23')->assertOk()->getContent();

        // Sombreado solo el enlazado.
        $this->assertStringContainsString('verse-focus', $this->verseClass($html, 23));
        $this->assertStringNotContainsString('verse-focus', $this->verseClass($html, 22));

        // Se abre en su página (21–25): esa se ve, la primera queda oculta hasta cambiar de página.
        $this->assertStringNotContainsString('x-cloak', $this->verseTag($html, 21));
        $this->assertStringContainsString('x-cloak', $this->verseTag($html, 1));
    }

    public function test_verse_ranges_are_shaded_and_invalid_verses_ignored(): void
    {
        $this->fakeLongChapter();

        $html = $this->get('/biblia/salmos/23?v=3-5')->getContent();
        foreach ([3, 4, 5] as $n) {
            $this->assertStringContainsString('verse-focus', $this->verseClass($html, $n));
        }
        $this->assertStringNotContainsString('verse-focus', $this->verseClass($html, 6));

        foreach (['99', '5-2', 'abc', '0'] as $bad) {
            $html = $this->get('/biblia/salmos/23?v='.$bad)->assertOk()->getContent();
            $this->assertStringNotContainsString('verse-focus', $this->verseClass($html, 1).$this->verseClass($html, 5), "v={$bad}");
            $this->assertStringNotContainsString('x-cloak', $this->verseTag($html, 1), "v={$bad}: abre en la página 1");
        }
    }

    public function test_chapter_is_paginated_ten_verses_at_a_time(): void
    {
        $this->fakeLongChapter();

        $html = $this->get('/biblia/salmos/23')->assertOk()->assertSee('aria-label="Páginas del capítulo"', false)->getContent();

        $this->assertStringNotContainsString('x-cloak', $this->verseTag($html, 10));
        $this->assertStringContainsString('x-cloak', $this->verseTag($html, 11));
        // Todo el texto sigue en la página (búsqueda del navegador, sin JavaScript).
        $this->assertStringContainsString('Texto del versículo 25', $html);
    }

    public function test_verse_count_endpoint_feeds_the_verse_picker(): void
    {
        $this->fakeLongChapter(6);

        $this->getJson('/biblia/salmos/23/versiculos')->assertOk()->assertExactJson(['count' => 6]);
        $this->getJson('/biblia/salmos/999/versiculos')->assertNotFound();
        $this->getJson('/biblia/no-existe/1/versiculos')->assertNotFound();
    }

    public function test_search_box_understands_books_passages_and_ranges(): void
    {
        $q = fn (string $text) => ($r = BibleApi::parseQuery($text)) ? [$r['book']['slug'], $r['chapter'], $r['verse']] : null;

        $this->assertSame(['juan', null, null], $q('Juan'));
        $this->assertSame(['1-juan', null, null], $q('1 juan'));
        $this->assertSame(['juan', 3, '16'], $q('juan 3:16'));
        $this->assertSame(['genesis', 1, '1'], $q('gen 1:1'));          // comienzo único + capítulo
        $this->assertSame(['1-corintios', 13, '4-7'], $q('1 Co 13:4-7'));
        $this->assertSame(['salmos', 119, null], $q('Salmos 119'));
        $this->assertNull($q('juez'));          // palabra: no se confunde con «Jueces»
        $this->assertNull($q('esperanza'));
        $this->assertNull($q('Juan 99'));       // capítulo inexistente
    }

    public function test_searching_a_passage_or_a_book_opens_it_instead_of_searching_words(): void
    {
        Http::preventStrayRequests();

        $this->get('/biblia/buscar?q=Juan+3:16')->assertRedirect('/biblia/juan/3?v=16#v16');
        $this->get('/biblia/buscar?q=1+co+13:4-7')->assertRedirect('/biblia/1-corintios/13?v=4-7#v4');
        $this->get('/biblia/buscar?q=Romanos')->assertRedirect('/biblia?libro=romanos#libros');
    }

    public function test_index_opens_the_chosen_book_and_book_links_open_its_chapters(): void
    {
        Http::preventStrayRequests();

        $this->get('/biblia?libro=juan')->assertOk()
            ->assertSee('id="libro-dialogo"', false)
            ->assertSee("open: 'juan'", false)
            ->assertSee('Toca un libro para elegir el capítulo y el versículo.')
            ->assertSee("\$dispatch('biblia-libro'", false)
            ->assertSee('placeholder="Libro, cita o palabra', false);

        $this->get('/biblia?libro=no-existe')->assertOk()->assertSee('open: null', false);

        // La ventana recibe los 66 libros (no solo los del último testamento listado).
        preg_match('/<dialog id="libro-dialogo"[^>]*>/', $this->get('/biblia')->getContent(), $dialog);
        $this->assertStringContainsString('salmos', $dialog[0]);
        $this->assertStringContainsString('apocalipsis', $dialog[0]);
    }
}
