<?php

namespace Tests\Feature;

use App\Enums\DonationMethodType;
use App\Enums\VenueKind;
use App\Models\ChurchEvent;
use App\Models\DonationMethod;
use App\Models\Faq;
use App\Models\ProgramDay;
use App\Models\ProgramItem;
use App\Models\SiteSetting;
use App\Models\Venue;
use Database\Seeders\ConventionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ConventionSeeder::class);
    }

    public static function pages(): array
    {
        return [
            'inicio' => ['/'],
            'nosotros' => ['/nosotros'],
            'ensenanzas' => ['/ensenanzas'],
            'biblia' => ['/biblia'],
            'eventos' => ['/eventos'],
            'bethel' => ['/bethel-tv'],
            'doctrina' => ['/doctrina'],
            'convencion' => ['/eventos/convencion-nacional-2026'],
            'programa' => ['/eventos/convencion-nacional-2026/programa'],
            'sede' => ['/eventos/convencion-nacional-2026/sede'],
            'donaciones' => ['/eventos/convencion-nacional-2026/donaciones'],
            'contacto' => ['/contacto'],
            'juegos' => ['/juegos'],
            'trivia' => ['/juegos/trivia'],
        ];
    }

    #[DataProvider('pages')]
    public function test_public_page_renders(string $uri): void
    {
        $this->get($uri)->assertOk()->assertSee('Saltar al contenido');
    }

    /** El sitio es solo de contenido cristiano: no quedan comidas, hospedaje ni turismo. */
    #[DataProvider('pages')]
    public function test_pages_have_no_food_lodging_or_tourism_content(string $uri): void
    {
        $html = mb_strtolower(strip_tags($this->get($uri)->getContent()));

        foreach (['reserva', 'platos', 'hospedaje', 'turismo', 'descubre huaraz', 'actividad solidaria'] as $word) {
            $this->assertStringNotContainsString($word, $html, "«{$word}» aparece en {$uri}");
        }
    }

    public function test_empty_sections_say_information_is_pending_instead_of_inventing_it(): void
    {
        $this->get('/eventos/convencion-nacional-2026/programa')->assertSee('en cuanto la organización lo confirme');
        $this->get('/eventos/convencion-nacional-2026/donaciones')->assertSee('en cuanto la organización los confirme');
        $this->get('/contacto')->assertSee('Los datos de contacto se publicarán aquí');
        $this->get('/eventos/convencion-nacional-2026/sede')->assertSee('Dirección exacta por confirmar.');
    }

    public function test_only_published_program_items_are_shown(): void
    {
        $day = ProgramDay::first();
        ProgramItem::create(['program_day_id' => $day->id, 'starts_at' => '19:00', 'title' => 'Culto de apertura', 'is_published' => true]);
        ProgramItem::create(['program_day_id' => $day->id, 'starts_at' => '21:00', 'title' => 'Actividad en borrador', 'is_published' => false]);

        // Ese día explícito: sin «?dia=», la página abre en el día de hoy durante la convención.
        $this->get('/eventos/convencion-nacional-2026/programa?dia='.$day->date->toDateString())
            ->assertSee('Culto de apertura')
            ->assertSee('19:00')
            ->assertDontSee('Actividad en borrador');
    }

    public function test_unpublished_content_is_hidden_everywhere(): void
    {
        Faq::create(['question' => 'Pregunta oculta', 'answer' => 'x', 'is_published' => false]);
        DonationMethod::create(['type' => DonationMethodType::Yape, 'label' => 'Cuenta en borrador', 'is_published' => false]);
        $event = ['church_event_id' => ChurchEvent::featured()->id];
        Venue::query()->forceCreate($event + ['kind' => VenueKind::HostChurch, 'name' => 'Iglesia en borrador', 'is_published' => false]);

        $this->get('/contacto')->assertDontSee('Pregunta oculta');
        $this->get('/eventos/convencion-nacional-2026/donaciones')->assertDontSee('Cuenta en borrador');
        $this->get('/eventos/convencion-nacional-2026/sede')->assertDontSee('Iglesia en borrador');
    }

    public function test_published_donation_method_is_listed(): void
    {
        DonationMethod::create([
            'type' => DonationMethodType::BankTransfer, 'label' => 'Banco de prueba',
            'holder_name' => 'Titular de prueba', 'account_number' => '000-0000', 'is_published' => true,
        ]);

        $this->get('/eventos/convencion-nacional-2026/donaciones')->assertSee('Banco de prueba')->assertSee('000-0000')->assertSee('Copiar');
    }

    public function test_venue_with_coordinates_offers_map_and_google_maps_link(): void
    {
        Venue::first()->update(['latitude' => -9.1234567, 'longitude' => -77.1234567, 'address' => 'Dirección de prueba']);

        $this->get('/eventos/convencion-nacional-2026/sede')
            ->assertSee('Dirección de prueba')
            // Mapa incrustado con el punto exacto y enlace para abrir Google Maps.
            ->assertSee('https://maps.google.com/maps?q=-9.1234567%2C-77.1234567&amp;z=16&amp;output=embed', false)
            ->assertSee('https://www.google.com/maps/search/?api=1&amp;query=-9.1234567%2C-77.1234567', false);
    }

    public function test_venue_without_coordinates_maps_its_address(): void
    {
        Venue::first()->update(['latitude' => null, 'longitude' => null, 'address' => 'Av. Luzuriaga 234, Huaraz', 'maps_url' => null]);

        $this->get('/eventos/convencion-nacional-2026/sede')
            ->assertSee('https://maps.google.com/maps?q=Av.%20Luzuriaga%20234%2C%20Huaraz', false);
    }

    public function test_church_address_with_map_appears_on_home_contact_and_footer(): void
    {
        $this->get('/')->assertDontSee('Dónde estamos');

        $site = SiteSetting::current();
        $site->address = 'Av. Luzuriaga 234, Huaraz';
        $site->save();

        foreach (['/', '/contacto'] as $page) {
            $this->get($page)
                ->assertSee('Dónde estamos')
                ->assertSee('Av. Luzuriaga 234, Huaraz')
                ->assertSee('https://maps.google.com/maps?q=Av.%20Luzuriaga%20234%2C%20Huaraz', false);
        }

        // En el pie de cualquier página, con enlace a Google Maps.
        $this->get('/nosotros')->assertSee('Av. Luzuriaga 234, Huaraz')->assertSee('(Google Maps, pestaña nueva)');
    }

    public function test_contact_data_and_whatsapp_link_are_rendered(): void
    {
        SiteSetting::first()->update(['contact_whatsapp' => '+51 900 000 000', 'contact_email' => 'contacto@example.test']);

        $this->get('/contacto')
            ->assertSee('https://wa.me/51900000000', false)
            ->assertSee('contacto@example.test');
    }

    public function test_old_convention_urls_redirect_permanently(): void
    {
        foreach (['programa', 'sede', 'donaciones'] as $old) {
            // Al evento destacado (la convención sembrada).
            $this->get("/$old")->assertStatus(301)->assertRedirect("/eventos/convencion-nacional-2026/$old");
            $this->get("/eventos/convencion/$old")->assertStatus(301)->assertRedirect("/eventos/convencion-nacional-2026/$old");
        }
    }

    public function test_convention_pages_show_their_submenu_and_site_pages_do_not(): void
    {
        $this->get('/eventos/convencion-nacional-2026/programa')->assertSee('aria-label="Secciones del evento"', false);
        $this->get('/nosotros')->assertDontSee('aria-label="Secciones del evento"', false);
    }

    public function test_content_is_escaped(): void
    {
        Faq::create(['question' => '<script>alert(1)</script>', 'answer' => 'respuesta', 'is_published' => true]);

        $this->get('/contacto')->assertDontSee('<script>alert(1)</script>', false)->assertSee('&lt;script&gt;', false);
    }

    public function test_countdown_label_reflects_current_date(): void
    {
        $event = ChurchEvent::featured();

        $this->assertSame('Faltan 7 días', $event->countdownLabel(Carbon::parse('2026-10-15')));
        $this->assertSame('Falta 1 día', $event->countdownLabel(Carbon::parse('2026-10-21')));
        $this->assertSame('En curso', $event->countdownLabel(Carbon::parse('2026-10-23')));
        $this->assertSame('Finalizado', $event->countdownLabel(Carbon::parse('2026-10-26')));
    }

    public function test_home_features_the_highlighted_event(): void
    {
        $this->get('/')->assertOk()->assertSee('Familia hay esperanza')->assertSee(route('events.show', ChurchEvent::featured()), false);
    }

    public function test_seeder_is_idempotent_and_does_not_overwrite_admin_changes(): void
    {
        ChurchEvent::featured()->update(['theme' => 'Tema editado']);

        $this->seed(ConventionSeeder::class);

        $this->assertSame(1, ChurchEvent::count());
        $this->assertSame('Tema editado', ChurchEvent::first()->theme);
        $this->assertSame(4, ProgramDay::count());
        $this->assertSame(1, Venue::count());
    }
}
