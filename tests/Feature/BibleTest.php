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
}
