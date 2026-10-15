<?php

namespace Tests\Feature;

use App\Enums\AccommodationType;
use App\Enums\DonationMethodType;
use App\Enums\VenueKind;
use App\Models\Accommodation;
use App\Models\DonationMethod;
use App\Models\EventSetting;
use App\Models\Faq;
use App\Models\ProgramDay;
use App\Models\ProgramItem;
use App\Models\TouristPlace;
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
            'programa' => ['/programa'],
            'sede' => ['/sede'],
            'donaciones' => ['/donaciones'],
            'hospedaje' => ['/hospedaje'],
            'turismo' => ['/turismo'],
            'contacto' => ['/contacto'],
        ];
    }

    #[DataProvider('pages')]
    public function test_public_page_renders(string $uri): void
    {
        $this->get($uri)->assertOk()->assertSee('Saltar al contenido');
    }

    public function test_empty_sections_say_information_is_pending_instead_of_inventing_it(): void
    {
        $this->get('/programa')->assertSee('en cuanto la organización lo confirme');
        $this->get('/donaciones')->assertSee('en cuanto la organización los confirme');
        $this->get('/hospedaje')->assertSee('en cuanto la organización las confirme');
        $this->get('/contacto')->assertSee('Los datos de contacto se publicarán aquí');
        $this->get('/sede')->assertSee('Dirección exacta por confirmar.');
    }

    public function test_only_published_program_items_are_shown(): void
    {
        $day = ProgramDay::first();
        ProgramItem::create(['program_day_id' => $day->id, 'starts_at' => '19:00', 'title' => 'Culto de apertura', 'is_published' => true]);
        ProgramItem::create(['program_day_id' => $day->id, 'starts_at' => '21:00', 'title' => 'Actividad en borrador', 'is_published' => false]);

        $this->get('/programa')
            ->assertSee('Culto de apertura')
            ->assertSee('19:00')
            ->assertDontSee('Actividad en borrador');
    }

    public function test_unpublished_content_is_hidden_everywhere(): void
    {
        Faq::create(['question' => 'Pregunta oculta', 'answer' => 'x', 'is_published' => false]);
        DonationMethod::create(['type' => DonationMethodType::Yape, 'label' => 'Cuenta en borrador', 'is_published' => false]);
        Accommodation::create(['type' => AccommodationType::Hotel, 'name' => 'Hotel en borrador', 'is_published' => false]);
        TouristPlace::create(['name' => 'Lugar en borrador', 'is_published' => false]);
        Venue::create(['kind' => VenueKind::HostChurch, 'name' => 'Iglesia en borrador', 'is_published' => false]);

        $this->get('/contacto')->assertDontSee('Pregunta oculta');
        $this->get('/donaciones')->assertDontSee('Cuenta en borrador');
        $this->get('/hospedaje')->assertDontSee('Hotel en borrador');
        $this->get('/turismo')->assertDontSee('Lugar en borrador');
        $this->get('/sede')->assertDontSee('Iglesia en borrador');
    }

    public function test_published_donation_method_is_listed(): void
    {
        DonationMethod::create([
            'type' => DonationMethodType::BankTransfer, 'label' => 'Banco de prueba',
            'holder_name' => 'Titular de prueba', 'account_number' => '000-0000', 'is_published' => true,
        ]);

        $this->get('/donaciones')->assertSee('Banco de prueba')->assertSee('000-0000')->assertSee('Copiar');
    }

    public function test_venue_with_coordinates_offers_map_and_google_maps_link(): void
    {
        Venue::first()->update(['latitude' => -9.1234567, 'longitude' => -77.1234567, 'address' => 'Dirección de prueba']);

        $this->get('/sede')
            ->assertSee('Dirección de prueba')
            ->assertSee('Mostrar mapa')
            ->assertSee('https://www.google.com/maps/search/?api=1&amp;query=-9.1234567,-77.1234567', false);
    }

    public function test_contact_data_and_whatsapp_link_are_rendered(): void
    {
        EventSetting::first()->update(['contact_whatsapp' => '+51 900 000 000', 'contact_email' => 'contacto@example.test']);

        $this->get('/contacto')
            ->assertSee('https://wa.me/51900000000', false)
            ->assertSee('contacto@example.test');
    }

    public function test_content_is_escaped(): void
    {
        Faq::create(['question' => '<script>alert(1)</script>', 'answer' => 'respuesta', 'is_published' => true]);

        $this->get('/contacto')->assertDontSee('<script>alert(1)</script>', false)->assertSee('&lt;script&gt;', false);
    }

    public function test_countdown_label_reflects_current_date(): void
    {
        $settings = EventSetting::current();

        $this->assertSame('Faltan 7 días', $settings->countdownLabel(Carbon::parse('2026-10-15')));
        $this->assertSame('Falta 1 día', $settings->countdownLabel(Carbon::parse('2026-10-21')));
        $this->assertSame('La convención está en curso', $settings->countdownLabel(Carbon::parse('2026-10-23')));
        $this->assertSame('La convención ha finalizado', $settings->countdownLabel(Carbon::parse('2026-10-26')));
    }

    public function test_seeder_is_idempotent_and_does_not_overwrite_admin_changes(): void
    {
        EventSetting::first()->update(['theme' => 'Tema editado']);

        $this->seed(ConventionSeeder::class);

        $this->assertSame(1, EventSetting::count());
        $this->assertSame('Tema editado', EventSetting::first()->theme);
        $this->assertSame(4, ProgramDay::count());
        $this->assertSame(1, Venue::count());
    }
}
