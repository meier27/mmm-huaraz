<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\TeachingCategory;
use App\Livewire\Admin\ChurchEventManager;
use App\Livewire\Admin\SiteSettingsEditor;
use App\Livewire\Admin\SocialLinksEditor;
use App\Livewire\Admin\TeachingManager;
use App\Livewire\Admin\VerseManager;
use App\Models\AuditLog;
use App\Models\ChurchEvent;
use App\Models\Leader;
use App\Models\SiteSetting;
use App\Models\Teaching;
use App\Models\User;
use App\Models\Verse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SiteContentTest extends TestCase
{
    use RefreshDatabase;

    private function teaching(array $attributes = []): Teaching
    {
        return Teaching::create($attributes + [
            'title' => 'Enseñanza de prueba',
            'slug' => 'ensenanza-de-prueba',
            'category' => TeachingCategory::Sermon,
            'body' => 'Contenido de prueba.',
            'published_on' => today()->subDay()->format('Y-m-d'),
            'is_published' => true,
        ]);
    }

    public function test_migration_creates_the_site_settings_row_with_the_official_bethel_channel(): void
    {
        $site = SiteSetting::sole();

        $this->assertSame('Movimiento Misionero Mundial – Huaraz', $site->church_name);
        $this->assertSame(config('bible.bethel_channel_id'), $site->bethel_channel_id);
    }

    public function test_only_published_teachings_whose_date_has_arrived_are_listed(): void
    {
        $this->teaching(['title' => 'Visible', 'slug' => 'visible']);
        $this->teaching(['title' => 'Borrador', 'slug' => 'borrador', 'is_published' => false]);
        $this->teaching(['title' => 'Programada', 'slug' => 'programada', 'published_on' => today()->addDay()->format('Y-m-d')]);

        $this->get('/ensenanzas')->assertOk()->assertSee('Visible')->assertDontSee('Borrador')->assertDontSee('Programada');
        $this->get('/ensenanzas/borrador')->assertNotFound();
        $this->get('/ensenanzas/programada')->assertNotFound();
        $this->get('/ensenanzas/visible')->assertOk()->assertSee('Visible');
    }

    public function test_teachings_can_be_filtered_by_category_and_unknown_categories_are_ignored(): void
    {
        $this->teaching(['title' => 'Un sermón', 'slug' => 'sermon']);
        $this->teaching(['title' => 'Un devocional', 'slug' => 'devocional', 'category' => TeachingCategory::Devotional]);

        $this->get('/ensenanzas?categoria=devotional')->assertSee('Un devocional')->assertDontSee('Un sermón');
        $this->get('/ensenanzas?categoria=inventada')->assertOk()->assertSee('Un devocional')->assertSee('Un sermón');
    }

    public function test_teaching_markdown_strips_html_and_unsafe_links(): void
    {
        $this->teaching(['body' => "## Subtítulo\n\n<script>alert(1)</script>\n\n[malo](javascript:alert(1)) [bueno](https://example.test)"]);

        $this->get('/ensenanzas/ensenanza-de-prueba')
            ->assertSee('<h2>Subtítulo</h2>', false)
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('javascript:alert', false)
            ->assertSee('href="https://example.test" target="_blank" rel="noopener noreferrer"', false);
    }

    public function test_editor_creates_teaching_with_generated_slug_and_youtube_id(): void
    {
        $component = Livewire::actingAs(User::factory()->role(Role::Editor)->create())
            ->test(TeachingManager::class)
            ->call('create')
            ->assertSet('form.published_on', today()->format('Y-m-d'))
            ->set('form.title', 'La familia según Dios')
            ->set('form.category', TeachingCategory::Family->value)
            ->set('form.body', 'Texto')
            ->set('form.video_url', 'https://youtu.be/dQw4w9WgXcQ')
            ->call('save')
            ->assertHasNoErrors();

        $teaching = Teaching::sole();
        $this->assertSame('la-familia-segun-dios', $teaching->slug);
        $this->assertSame('dQw4w9WgXcQ', $teaching->video_id);
        $this->assertTrue(AuditLog::where('auditable_type', $teaching->getMorphClass())->exists());

        // Cambiar el título no cambia el enlace ya compartido.
        $component->call('edit', $teaching->id)
            ->assertSet('form.video_url', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ')
            ->set('form.title', 'Otro título')
            ->set('form.video_url', '')
            ->call('save');

        $this->assertSame('la-familia-segun-dios', $teaching->fresh()->slug);
        $this->assertNull($teaching->fresh()->video_id);
    }

    public function test_teaching_rejects_non_youtube_video_and_unknown_category(): void
    {
        Livewire::actingAs(User::factory()->role(Role::Editor)->create())
            ->test(TeachingManager::class)
            ->call('create')
            ->set('form.title', 'Prueba')
            ->set('form.category', 'inventada')
            ->set('form.body', 'Texto')
            ->set('form.video_url', 'https://vimeo.com/123')
            ->call('save')
            ->assertHasErrors(['form.category', 'form.video_url']);

        $this->assertSame(0, Teaching::count());
    }

    public function test_duplicate_titles_get_unique_slugs(): void
    {
        $this->teaching(['slug' => 'vigilia']);

        $this->assertSame('vigilia-2', Teaching::uniqueSlug('Vigilia'));
        $this->assertSame('vigilia', Teaching::uniqueSlug('Vigilia', Teaching::first()->id));
    }

    public function test_cashier_cannot_manage_site_content(): void
    {
        $cashier = User::factory()->role(Role::Cashier)->create();

        foreach (['/admin/sitio', '/admin/ensenanzas', '/admin/versiculos', '/admin/lideres', '/admin/eventos'] as $uri) {
            $this->actingAs($cashier)->get($uri)->assertForbidden();
        }

        Livewire::actingAs($cashier)->test(VerseManager::class)->assertForbidden();
    }

    public function test_site_settings_update_contact_and_footer_immediately(): void
    {
        SiteSetting::current(); // calienta la caché

        Livewire::actingAs(User::factory()->role(Role::Editor)->create())
            ->test(SiteSettingsEditor::class)
            ->set('form.contact_email', 'iglesia@ejemplo.test')
            ->set('form.service_schedule', "Domingo · 10:00 a. m.\n\nJueves · 7:00 p. m.")
            ->call('save')
            ->assertHasNoErrors();

        $this->get('/')
            ->assertSee('mailto:iglesia@ejemplo.test', false)
            ->assertSee('Domingo · 10:00 a. m.')
            ->assertSee('Jueves · 7:00 p. m.');
    }

    public function test_site_settings_reject_bad_channel_and_unknown_bible_version(): void
    {
        Livewire::actingAs(User::factory()->role(Role::Editor)->create())
            ->test(SiteSettingsEditor::class)
            ->set('form.bethel_channel_id', 'https://youtube.com/@x')
            ->set('form.bible_version', 'rvr1960')
            ->call('save')
            ->assertHasErrors(['form.bethel_channel_id', 'form.bible_version']);
    }

    public function test_social_links_are_managed_in_their_own_panel_page(): void
    {
        SiteSetting::current(); // calienta la caché

        Livewire::actingAs(User::factory()->role(Role::Editor)->create())
            ->test(SocialLinksEditor::class)
            ->set('form.facebook_url', 'https://www.facebook.com/iglesia-huaraz')
            ->set('form.instagram_url', 'https://www.instagram.com/iglesia_huaraz')
            ->set('form.contact_whatsapp', '+51 900 000 000')
            ->call('save')
            ->assertHasNoErrors()
            ->call('clear', 'instagram_url')
            ->call('save');

        $this->get('/')
            ->assertSee('https://www.facebook.com/iglesia-huaraz', false)
            ->assertDontSee('instagram.com/iglesia_huaraz', false)
            ->assertSee('https://wa.me/51900000000', false);
    }

    public function test_social_links_must_be_https_and_belong_to_their_network(): void
    {
        Livewire::actingAs(User::factory()->role(Role::Editor)->create())
            ->test(SocialLinksEditor::class)
            ->set('form.facebook_url', 'http://facebook.com/x')
            ->set('form.instagram_url', 'https://www.facebook.com/x') // red equivocada
            ->set('form.youtube_url', 'javascript:alert(1)')
            ->set('form.tiktok_url', 'https://tiktok.com.evil.test/@x')
            ->set('form.contact_whatsapp', 'llámame')
            ->call('save')
            ->assertHasErrors(['form.facebook_url', 'form.instagram_url', 'form.youtube_url', 'form.tiktok_url', 'form.contact_whatsapp'])
            ->assertDontSeeHtml('href="javascript:');

        $this->assertNull(SiteSetting::first()?->facebook_url);
    }

    public function test_cashier_cannot_manage_social_links(): void
    {
        $this->actingAs(User::factory()->role(Role::Cashier)->create())
            ->get(route('admin.social'))
            ->assertForbidden();
    }

    public function test_top_bar_shows_only_the_social_networks_configured_in_the_panel(): void
    {
        $this->get('/')->assertOk()->assertDontSee('facebook.com', false)->assertSee(route('login'), false);

        SiteSetting::first()->update(['facebook_url' => 'https://facebook.com/iglesia-prueba', 'contact_whatsapp' => '+51 900 000 000']);

        $this->get('/')
            ->assertSee('https://facebook.com/iglesia-prueba', false)
            ->assertSee('Facebook (pestaña nueva)')
            ->assertSee('https://wa.me/51900000000', false)
            ->assertSee('aria-label="Menú completo"', false);
    }

    public function test_bethel_page_embeds_the_configured_channel(): void
    {
        $this->get('/bethel-tv')
            ->assertOk()
            ->assertSee('https://www.youtube-nocookie.com/embed/live_stream?channel='.config('bible.bethel_channel_id'), false);
    }

    public function test_home_shows_published_verse_and_about_shows_published_leaders(): void
    {
        Verse::create(['reference' => 'Josué 24:15', 'text' => 'Yo y mi casa serviremos a Jehová.', 'is_published' => true]);
        Verse::create(['reference' => 'Oculto 1:1', 'text' => 'No visible', 'is_published' => false, 'sort_order' => 0]);
        Leader::create(['name' => 'Líder visible', 'role' => 'Pastor', 'is_published' => true]);
        Leader::create(['name' => 'Líder oculto', 'role' => 'Pastor', 'is_published' => false]);

        $this->get('/')->assertSee('Josué 24:15')->assertDontSee('Oculto 1:1');
        $this->get('/nosotros')->assertSee('Líder visible')->assertDontSee('Líder oculto');
    }

    public function test_events_are_split_into_upcoming_and_past_and_drafts_are_hidden(): void
    {
        ChurchEvent::create(['title' => 'Vigilia próxima', 'slug' => 'vigilia', 'starts_on' => today()->addWeek(), 'is_published' => true]);
        ChurchEvent::create(['title' => 'Retiro en curso', 'slug' => 'retiro', 'starts_on' => today()->subDay(), 'ends_on' => today()->addDay(), 'is_published' => true]);
        ChurchEvent::create(['title' => 'Campaña pasada', 'slug' => 'campana', 'starts_on' => today()->subMonth(), 'is_published' => true]);
        ChurchEvent::create(['title' => 'Evento en borrador', 'slug' => 'borrador', 'starts_on' => today()->addWeek(), 'is_published' => false]);

        $this->assertSame(['Retiro en curso', 'Vigilia próxima'], ChurchEvent::published()->upcoming()->pluck('title')->all());
        $this->assertSame(['Campaña pasada'], ChurchEvent::published()->past()->pluck('title')->all());

        $this->get('/eventos')->assertOk()->assertSee('Vigilia próxima')->assertSee('Eventos anteriores')->assertDontSee('Evento en borrador');
        $this->get('/eventos/borrador')->assertNotFound();
        $this->get('/eventos/vigilia')->assertOk()->assertSee('Próximo evento');
        $this->get('/eventos/campana')->assertOk()->assertSee('Evento finalizado');
    }

    public function test_featured_event_is_highlighted_and_the_legacy_convention_slug_is_reserved(): void
    {
        $featured = ChurchEvent::create(['title' => 'Gran campaña', 'slug' => 'gran-campana', 'starts_on' => today()->addWeek(), 'is_published' => true, 'is_featured' => true]);

        $this->get('/eventos')->assertSee('Destacado')->assertSee(route('events.show', $featured), false);
        $this->get('/eventos/convencion')->assertRedirect(route('events.show', $featured));

        // «convencion» queda para las URL antiguas: un evento nunca la usa como slug.
        $this->assertNotSame('convencion', ChurchEvent::uniqueSlug('Convención'));
    }

    public function test_editor_creates_event_with_slug_and_rejects_end_before_start(): void
    {
        $component = Livewire::actingAs(User::factory()->role(Role::Editor)->create())
            ->test(ChurchEventManager::class)
            ->call('create')
            ->set('form.title', 'Campaña evangelística')
            ->set('form.starts_on', '2026-11-10')
            ->set('form.ends_on', '2026-11-01')
            ->call('save')
            ->assertHasErrors(['form.ends_on']);

        $component->set('form.ends_on', '')->set('form.external_url', 'http://inseguro.test')->call('save')->assertHasErrors(['form.external_url']);
        $component->set('form.external_url', '')->call('save')->assertHasNoErrors();

        $this->assertSame('campana-evangelistica', ChurchEvent::sole()->slug);
    }
}
