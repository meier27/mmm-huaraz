<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Livewire\Admin\DonationMethodManager;
use App\Livewire\Admin\EventEditor;
use App\Livewire\Admin\FaqManager;
use App\Livewire\Admin\ProgramDayManager;
use App\Livewire\Admin\ProgramItemManager;
use App\Livewire\Admin\TouristPlaceManager;
use App\Livewire\Admin\VenueManager;
use App\Models\AuditLog;
use App\Models\ChurchEvent;
use App\Models\Faq;
use App\Models\ProgramDay;
use App\Models\TouristPlace;
use App\Models\User;
use Database\Seeders\ConventionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ContentManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ConventionSeeder::class);
    }

    public static function contentRoutes(): array
    {
        return [
            // {event}: la convención sembrada (evento con todos los módulos).
            ['/admin/eventos'], ['/admin/eventos/{event}'], ['/admin/eventos/{event}/programa'], ['/admin/eventos/{event}/programa/dias'],
            ['/admin/eventos/{event}/sedes'], ['/admin/eventos/{event}/platos'], ['/admin/eventos/{event}/platos/ofertas'],
            ['/admin/eventos/{event}/hospedaje'], ['/admin/eventos/{event}/turismo'], ['/admin/donaciones'], ['/admin/preguntas'],
        ];
    }

    #[DataProvider('contentRoutes')]
    public function test_editor_can_open_content_screens(string $uri): void
    {
        $this->actingAs(User::factory()->role(Role::Editor)->create())->get($this->uri($uri))->assertOk();
    }

    #[DataProvider('contentRoutes')]
    public function test_cashier_and_delivery_cannot_open_content_screens(string $uri): void
    {
        $this->actingAs(User::factory()->role(Role::Cashier)->create())->get($this->uri($uri))->assertForbidden();
        $this->actingAs(User::factory()->role(Role::Delivery)->create())->get($this->uri($uri))->assertForbidden();
    }

    private function convention(): ChurchEvent
    {
        return ChurchEvent::featured();
    }

    private function uri(string $uri): string
    {
        return str_replace('{event}', (string) $this->convention()->id, $uri);
    }

    public function test_livewire_actions_are_authorized_on_every_request(): void
    {
        $faq = Faq::create(['question' => 'P', 'answer' => 'R', 'is_published' => true]);

        Livewire::actingAs(User::factory()->role(Role::Cashier)->create())
            ->test(FaqManager::class)
            ->assertForbidden();

        $this->assertDatabaseHas('faqs', ['id' => $faq->id]);
    }

    public function test_editor_creates_edits_publishes_and_deletes_a_faq_with_audit_trail(): void
    {
        $editor = User::factory()->role(Role::Editor)->create();

        $component = Livewire::actingAs($editor)->test(FaqManager::class)
            ->call('create')
            ->set('form.question', '¿Hay estacionamiento?')
            ->set('form.answer', 'Por confirmar.')
            ->set('form.sort_order', 1)
            ->call('save')
            ->assertHasNoErrors();

        $faq = Faq::sole();
        $this->assertFalse($faq->is_published);

        $component->call('edit', $faq->id)
            ->assertSet('form.question', '¿Hay estacionamiento?')
            ->set('form.answer', 'Sí, limitado.')
            ->call('save');
        $this->assertSame('Sí, limitado.', $faq->fresh()->answer);

        $component->call('togglePublished', $faq->id);
        $this->assertTrue($faq->fresh()->is_published);

        $component->call('delete', $faq->id);
        $this->assertModelMissing($faq);

        $actions = AuditLog::where('auditable_type', $faq->getMorphClass())->where('auditable_id', $faq->id)->pluck('action')->all();
        $this->assertSame(['created', 'updated', 'updated', 'deleted'], $actions);
        $this->assertSame($editor->id, AuditLog::latest('id')->first()->user_id);
    }

    public function test_validation_errors_are_shown_and_nothing_is_saved(): void
    {
        Livewire::actingAs(User::factory()->role(Role::Editor)->create())
            ->test(FaqManager::class)
            ->call('create')
            ->set('form.question', '')
            ->set('form.answer', '')
            ->call('save')
            ->assertHasErrors(['form.question' => 'required', 'form.answer' => 'required'])
            ->assertSee('El campo pregunta es obligatorio.');

        $this->assertSame(0, Faq::count());
    }

    public function test_venue_rejects_javascript_urls_and_out_of_range_coordinates(): void
    {
        Livewire::actingAs(User::factory()->role(Role::Editor)->create())
            ->test(VenueManager::class, ['event' => $this->convention()])
            ->call('create')
            ->set('form.name', 'Sede')
            ->set('form.maps_url', 'javascript:alert(1)')
            ->set('form.latitude', '120')
            ->set('form.longitude', '-77.5')
            ->call('save')
            ->assertHasErrors(['form.maps_url', 'form.latitude']);
    }

    public function test_program_item_requires_existing_day_and_end_after_start(): void
    {
        $day = ProgramDay::first();

        Livewire::actingAs(User::factory()->role(Role::Editor)->create())
            ->test(ProgramItemManager::class, ['event' => $this->convention()])
            ->call('create')
            ->set('form.program_day_id', 99999)
            ->set('form.starts_at', '20:00')
            ->set('form.ends_at', '19:00')
            ->set('form.title', 'Culto')
            ->call('save')
            ->assertHasErrors(['form.program_day_id', 'form.ends_at'])
            ->set('form.program_day_id', $day->id)
            ->set('form.ends_at', '21:30')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('20:00 – 21:30', $day->items()->first()->timeLabel());
    }

    public function test_program_day_dates_must_be_unique(): void
    {
        Livewire::actingAs(User::factory()->role(Role::Editor)->create())
            ->test(ProgramDayManager::class, ['event' => $this->convention()])
            ->call('create')
            ->set('form.date', '2026-10-22')
            ->call('save')
            ->assertHasErrors(['form.date' => 'unique']);
    }

    public function test_editing_a_record_id_cannot_be_tampered_from_the_client(): void
    {
        $faq = Faq::create(['question' => 'P', 'answer' => 'R']);

        $this->expectException(CannotUpdateLockedPropertyException::class);

        Livewire::actingAs(User::factory()->role(Role::Editor)->create())
            ->test(FaqManager::class)
            ->set('editingId', $faq->id);
    }

    public function test_event_data_update_is_reflected_on_the_public_site(): void
    {
        Livewire::actingAs(User::factory()->role(Role::Editor)->create())
            ->test(EventEditor::class, ['event' => $this->convention()])
            ->set('form.body', 'Bienvenidos a Huaraz')
            ->call('save')
            ->assertHasNoErrors();

        $this->get(route('events.show', $this->convention()))->assertSee('Bienvenidos a Huaraz');
    }

    public function test_event_hero_uses_the_panel_image_optional_text_and_button(): void
    {
        Storage::fake('public');

        Livewire::actingAs(User::factory()->role(Role::Editor)->create())
            ->test(EventEditor::class, ['event' => $this->convention()])
            ->set('form.image', UploadedFile::fake()->image('portada.jpg', 1920, 900))
            ->set('form.image_alt', 'Estadio de Huaraz')
            ->set('form.hero_text', 'Te esperamos con tu familia')
            ->set('form.hero_button_label', 'Ver en vivo')
            ->set('form.hero_button_url', '/bethel-tv')
            ->call('save')
            ->assertHasNoErrors();

        $event = $this->convention()->fresh();
        $this->get(route('events.show', $event))
            ->assertSee($event->imageUrl(), false)
            ->assertSee('alt="Estadio de Huaraz"', false)
            ->assertSee('Te esperamos con tu familia')
            ->assertSee('href="'.url('/bethel-tv').'"', false)
            ->assertSee('Ver programa');

        // Fechas y lugar no se repiten sobre la imagen: solo en la barra de datos.
        $this->get(route('events.show', $event))
            ->assertDontSee('<p class="max-w-2xl text-lg text-cream/90', false)
            ->assertSee('aria-label="Datos clave"', false);

        // Afiche con su propio texto: solo la imagen y el botón; el título queda para lectores de pantalla.
        $event->update(['hero_show_title' => false, 'hero_show_buttons' => false, 'hero_text' => null]);
        $this->get(route('events.show', $event))
            ->assertDontSee('Te esperamos con tu familia')
            ->assertDontSee('Ver programa')
            ->assertSee('Ver en vivo')
            ->assertSee('<h1 class="sr-only">', false);
    }

    public function test_event_hero_button_rejects_unsafe_links_and_needs_both_fields(): void
    {
        Livewire::actingAs(User::factory()->role(Role::Editor)->create())
            ->test(EventEditor::class, ['event' => $this->convention()])
            ->set('form.hero_button_label', 'Clic')
            ->set('form.hero_button_url', 'javascript:alert(1)')
            ->call('save')
            ->assertHasErrors(['form.hero_button_url'])
            ->set('form.hero_button_url', '')
            ->call('save')
            ->assertHasErrors(['form.hero_button_url']);
    }

    public function test_event_data_rejects_end_date_before_start(): void
    {
        Livewire::actingAs(User::factory()->role(Role::Editor)->create())
            ->test(EventEditor::class, ['event' => $this->convention()])
            ->set('form.ends_on', '2026-10-01')
            ->call('save')
            ->assertHasErrors(['form.ends_on']);
    }

    public function test_tourist_place_image_is_stored_with_random_name_and_replaced(): void
    {
        Storage::fake('public');
        $editor = User::factory()->role(Role::Editor)->create();

        $component = Livewire::actingAs($editor)->test(TouristPlaceManager::class, ['event' => $this->convention()])
            ->call('create')
            ->set('form.name', 'Laguna de prueba')
            ->set('form.image', UploadedFile::fake()->image('foto.jpg', 800, 600))
            ->set('form.image_alt', 'Vista de la laguna')
            ->call('save')
            ->assertHasNoErrors();

        $place = TouristPlace::sole();
        $first = $place->image_path;
        $this->assertStringStartsWith('tourist_places/', $first);
        $this->assertStringNotContainsString('foto', $first);
        Storage::disk('public')->assertExists($first);

        $component->call('edit', $place->id)
            ->set('form.image', UploadedFile::fake()->image('otra.png', 400, 300))
            ->call('save');

        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists($place->fresh()->image_path);

        $component->call('delete', $place->id);
        Storage::disk('public')->assertMissing($place->image_path);
    }

    public function test_donation_method_form_shows_and_keeps_only_the_fields_of_its_type(): void
    {
        Storage::fake('public');
        $editor = User::factory()->role(Role::Editor)->create();

        // Yape: titular, celular y QR (sin cuenta ni CCI); el nombre visible se sugiere.
        $component = Livewire::actingAs($editor)->test(DonationMethodManager::class)
            ->call('create')
            ->set('form.type', 'yape')
            ->assertSet('form.label', 'Yape')
            ->assertSee('Celular de Yape')->assertSee('Imagen del QR oficial')
            ->assertDontSee('CCI (cuenta interbancaria)')
            ->set('form.holder_name', 'Iglesia de prueba')
            ->call('save')
            ->assertHasErrors(['form.phone' => 'Indica el número de celular asociado.'])
            ->set('form.phone', '999 999 999')
            ->set('form.qr_image', UploadedFile::fake()->image('qr.png', 400, 400))
            ->call('save')
            ->assertHasNoErrors();

        $yape = \App\Models\DonationMethod::sole();
        $this->assertSame(['Yape', 'Iglesia de prueba', '999 999 999', null], [$yape->label, $yape->holder_name, $yape->phone, $yape->account_number]);
        $this->assertNotNull($yape->qr_image_path);

        // Cambiado a transferencia: banco, titular, cuenta y CCI; el celular y el QR se retiran.
        $component->call('edit', $yape->id)
            ->set('form.type', 'bank_transfer')
            ->assertSet('form.label', '')
            ->assertSee('CCI (cuenta interbancaria)')->assertDontSee('Imagen del QR oficial')
            ->set('form.label', 'BCP')
            ->call('save')
            ->assertHasErrors(['form.account_number'])
            ->set('form.cci', '00212300000000000000')
            ->call('save')
            ->assertHasNoErrors();

        $bank = $yape->fresh();
        $this->assertSame(['BCP', '00212300000000000000', null, null], [$bank->label, $bank->cci, $bank->phone, $bank->qr_image_path]);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_non_image_and_svg_uploads_are_rejected_on_upload_and_never_stored(): void
    {
        Storage::fake('public');
        $editor = User::factory()->role(Role::Editor)->create();

        // Las reglas de subida temporal (config/livewire.php) rechazan el archivo al subirlo.
        Livewire::actingAs($editor)->test(DonationMethodManager::class)
            ->call('create')
            ->set('form.label', 'Yape')
            ->set('form.qr_image', UploadedFile::fake()->create('qr.svg', 10, 'image/svg+xml'))
            ->assertHasErrors('form.qr_image')
            ->assertSet('form.qr_image', null);

        Livewire::actingAs($editor)->test(TouristPlaceManager::class, ['event' => $this->convention()])
            ->call('create')
            ->set('form.name', 'Lugar')
            ->set('form.image', UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf'))
            ->assertHasErrors('form.image')
            ->assertSet('form.image', null);

        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_oversized_images_are_rejected_by_form_rules(): void
    {
        Storage::fake('public');

        Livewire::actingAs(User::factory()->role(Role::Editor)->create())
            ->test(TouristPlaceManager::class, ['event' => $this->convention()])
            ->call('create')
            ->set('form.name', 'Lugar')
            ->set('form.image_alt', 'Texto')
            ->set('form.image', UploadedFile::fake()->image('grande.jpg', 800, 600)->size(3000))
            ->call('save')
            ->assertHasErrors('form.image');

        $this->assertSame(0, TouristPlace::count());
    }
}
