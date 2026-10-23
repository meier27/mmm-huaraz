<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\TeachingCategory;
use App\Livewire\Admin\SiteSettingsEditor;
use App\Models\SiteSetting;
use App\Models\Teaching;
use App\Models\User;
use App\Support\FlipBook;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DoctrineTest extends TestCase
{
    use RefreshDatabase;

    private const OFFICIAL = 'https://online.fliphtml5.com/pscvh/Doctrinario-mmm/';

    public function test_only_fliphtml5_book_links_are_accepted_even_from_pasted_embed_code(): void
    {
        $embed = '<div style="position:relative"><iframe style="position:absolute" src="https://online.fliphtml5.com/pscvh/Doctrinario-mmm/" title="Doctrinario mmm" seamless="seamless" allowfullscreen="true"></iframe></div>';

        $this->assertSame(self::OFFICIAL, FlipBook::url($embed));
        $this->assertSame(self::OFFICIAL, FlipBook::url('https://online.fliphtml5.com/pscvh/Doctrinario-mmm'));
        $this->assertSame(self::OFFICIAL, FlipBook::url('https://online.fliphtml5.com/pscvh/Doctrinario-mmm/#p=3'));

        foreach (['http://online.fliphtml5.com/pscvh/Doctrinario-mmm/', 'https://evil.example/pscvh/x/', 'javascript:alert(1)',
            '<iframe src="https://online.fliphtml5.com.evil.example/a/b/"></iframe>', 'https://online.fliphtml5.com/a/b/"onload="x', ''] as $bad) {
            $this->assertNull(FlipBook::url($bad), $bad);
        }
    }

    public function test_doctrine_page_shows_the_official_book_and_doctrine_teachings(): void
    {
        $this->seed(DatabaseSeeder::class);
        Teaching::query()->forceCreate(['title' => 'La santidad', 'slug' => 'la-santidad', 'category' => TeachingCategory::Doctrine, 'body' => 'Texto.', 'is_published' => true, 'published_on' => today()]);
        Teaching::query()->forceCreate(['title' => 'Un devocional', 'slug' => 'un-devocional', 'category' => TeachingCategory::Devotional, 'body' => 'Texto.', 'is_published' => true, 'published_on' => today()]);

        $this->get('/doctrina')
            ->assertOk()
            ->assertSee('src="'.self::OFFICIAL.'"', false)
            ->assertSee('sandbox="allow-scripts allow-same-origin allow-popups allow-popups-to-escape-sandbox"', false)
            ->assertSee('Abrir en pantalla completa')
            ->assertSee('La santidad')
            ->assertDontSee('Un devocional');

        // En el menú de todas las páginas.
        $this->get('/')->assertSee(route('doctrine'), false);
    }

    public function test_admin_pastes_the_embed_code_and_only_the_link_is_stored(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::factory()->role(Role::Admin)->create();

        Livewire::actingAs($admin)->test(SiteSettingsEditor::class)
            ->set('form.doctrine_book_url', '<iframe src="https://online.fliphtml5.com/abcde/Otro-libro/" title="x"></iframe>')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('https://online.fliphtml5.com/abcde/Otro-libro/', SiteSetting::current()->doctrine_book_url);
        $this->get('/doctrina')->assertSee('src="https://online.fliphtml5.com/abcde/Otro-libro/"', false);

        Livewire::actingAs($admin)->test(SiteSettingsEditor::class)
            ->set('form.doctrine_book_url', '<iframe src="https://sitio-raro.example/x/"></iframe>')
            ->call('save')
            ->assertHasErrors('form.doctrine_book_url');

        $this->assertSame('https://online.fliphtml5.com/abcde/Otro-libro/', SiteSetting::current()->doctrine_book_url);
    }
}
