<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Livewire\Admin\DonationMethodManager;
use App\Livewire\Admin\EventSettingsEditor;
use App\Livewire\Admin\FaqManager;
use App\Livewire\Admin\ProgramDayManager;
use App\Livewire\Admin\ProgramItemManager;
use App\Livewire\Admin\TouristPlaceManager;
use App\Livewire\Admin\VenueManager;
use App\Models\AuditLog;
use App\Models\EventSetting;
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
            ['/admin/convencion'], ['/admin/programa'], ['/admin/programa/dias'], ['/admin/sedes'],
            ['/admin/donaciones'], ['/admin/hospedaje'], ['/admin/turismo'], ['/admin/preguntas'],
        ];
    }

    #[DataProvider('contentRoutes')]
    public function test_editor_can_open_content_screens(string $uri): void
    {
        $this->actingAs(User::factory()->role(Role::Editor)->create())->get($uri)->assertOk();
    }

    #[DataProvider('contentRoutes')]
    public function test_cashier_and_delivery_cannot_open_content_screens(string $uri): void
    {
        $this->actingAs(User::factory()->role(Role::Cashier)->create())->get($uri)->assertForbidden();
        $this->actingAs(User::factory()->role(Role::Delivery)->create())->get($uri)->assertForbidden();
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
            ->test(VenueManager::class)
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
            ->test(ProgramItemManager::class)
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
            ->test(ProgramDayManager::class)
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

    public function test_event_settings_update_is_reflected_on_the_public_site(): void
    {
        EventSetting::current(); // calienta la caché

        Livewire::actingAs(User::factory()->role(Role::Editor)->create())
            ->test(EventSettingsEditor::class)
            ->set('form.contact_phone', '+51 900 000 000')
            ->set('form.summary', 'Bienvenidos a Huaraz')
            ->call('save')
            ->assertHasNoErrors();

        $this->get('/')->assertSee('Bienvenidos a Huaraz')->assertSee('+51 900 000 000');
    }

    public function test_event_settings_reject_end_date_before_start_and_non_https_social_links(): void
    {
        Livewire::actingAs(User::factory()->role(Role::Editor)->create())
            ->test(EventSettingsEditor::class)
            ->set('form.ends_on', '2026-10-01')
            ->set('form.facebook_url', 'http://facebook.com/x')
            ->call('save')
            ->assertHasErrors(['form.ends_on', 'form.facebook_url']);
    }

    public function test_tourist_place_image_is_stored_with_random_name_and_replaced(): void
    {
        Storage::fake('public');
        $editor = User::factory()->role(Role::Editor)->create();

        $component = Livewire::actingAs($editor)->test(TouristPlaceManager::class)
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

        Livewire::actingAs($editor)->test(TouristPlaceManager::class)
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
            ->test(TouristPlaceManager::class)
            ->call('create')
            ->set('form.name', 'Lugar')
            ->set('form.image_alt', 'Texto')
            ->set('form.image', UploadedFile::fake()->image('grande.jpg', 800, 600)->size(3000))
            ->call('save')
            ->assertHasErrors('form.image');

        $this->assertSame(0, TouristPlace::count());
    }
}
