<?php

namespace Tests\Feature\Reservations;

use App\Enums\Role;
use App\Livewire\Admin\BannerManager;
use App\Livewire\Admin\DishManager;
use App\Livewire\Admin\DishOfferingManager;
use App\Livewire\Admin\ReservationIndex;
use App\Models\Banner;
use App\Models\Dish;
use App\Models\DishOffering;
use App\Models\EventSetting;
use App\Models\User;
use App\Services\ReservationService;
use Database\Seeders\ConventionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\Concerns\CreatesOfferings;
use Tests\TestCase;

class ReservationAdminTest extends TestCase
{
    use CreatesOfferings, RefreshDatabase;

    public function test_reservation_screens_are_limited_to_reservation_roles(): void
    {
        $reservation = app(ReservationService::class)->create([$this->offering()->id => 1], 'Ana', '900000123', null, (string) Str::uuid());

        foreach ([Role::Admin, Role::Cashier, Role::Delivery] as $role) {
            $user = User::factory()->role($role)->create();
            $this->actingAs($user)->get('/admin/reservas')->assertOk();
            $this->actingAs($user)->get("/admin/reservas/{$reservation->id}")->assertOk()->assertSee($reservation->code);
        }

        $editor = User::factory()->role(Role::Editor)->create();
        $this->actingAs($editor)->get('/admin/reservas')->assertForbidden();
        $this->actingAs($editor)->get("/admin/reservas/{$reservation->id}")->assertForbidden();
    }

    public function test_menu_screens_are_limited_to_content_roles(): void
    {
        $this->actingAs(User::factory()->role(Role::Editor)->create())->get('/admin/platos')->assertOk();
        $this->actingAs(User::factory()->role(Role::Editor)->create())->get('/admin/platos/ofertas')->assertOk();
        $this->actingAs(User::factory()->role(Role::Cashier)->create())->get('/admin/platos')->assertForbidden();
        $this->actingAs(User::factory()->role(Role::Cashier)->create())->get('/admin/platos/ofertas')->assertForbidden();
    }

    public function test_search_by_code_name_and_phone(): void
    {
        $service = app(ReservationService::class);
        $offering = $this->offering(['stock' => 10]);
        $ana = $service->create([$offering->id => 1], 'Ana Quispe', '900111222', null, (string) Str::uuid());
        $luis = $service->create([$offering->id => 1], 'Luis Ramos', '955333444', null, (string) Str::uuid());

        $component = Livewire::actingAs(User::factory()->role(Role::Cashier)->create())->test(ReservationIndex::class);

        $component->set('search', strtolower($ana->code))->assertSee('Ana Quispe')->assertDontSee('Luis Ramos');
        $component->set('search', 'Ramos')->assertSee('Luis Ramos')->assertDontSee('Ana Quispe');
        $component->set('search', '333 444')->assertSee('Luis Ramos')->assertDontSee('Ana Quispe');
    }

    public function test_offering_stock_cannot_drop_below_reserved_portions(): void
    {
        $offering = $this->offering(['stock' => 10]);
        app(ReservationService::class)->create([$offering->id => 6], 'Ana', '900000123', null, (string) Str::uuid());

        Livewire::actingAs(User::factory()->role(Role::Editor)->create())
            ->test(DishOfferingManager::class)
            ->call('edit', $offering->id)
            ->set('form.stock', 5)
            ->call('save')
            ->assertHasErrors(['form.stock' => 'min'])
            ->set('form.stock', 6)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(6, $offering->fresh()->stock);
        $this->assertSame(6, $offering->fresh()->reserved);
    }

    public function test_reserved_counter_cannot_be_set_through_the_form(): void
    {
        $offering = $this->offering();

        Livewire::actingAs(User::factory()->role(Role::Editor)->create())
            ->test(DishOfferingManager::class)
            ->call('edit', $offering->id)
            ->set('form.stock', 20)
            ->call('save');

        $this->assertSame(0, $offering->fresh()->reserved);
        $this->assertNotContains('reserved', (new DishOffering)->getFillable());
    }

    public function test_offerings_and_dishes_with_reservations_cannot_be_deleted(): void
    {
        $offering = $this->offering();
        app(ReservationService::class)->create([$offering->id => 1], 'Ana', '900000123', null, (string) Str::uuid());
        $editor = User::factory()->role(Role::Editor)->create();

        Livewire::actingAs($editor)->test(DishOfferingManager::class)->call('delete', $offering->id)->assertHasErrors('delete');
        Livewire::actingAs($editor)->test(DishManager::class)->call('delete', $offering->dish_id)->assertHasErrors('delete');

        $this->assertModelExists($offering);
    }

    public function test_duplicate_offering_for_same_dish_date_and_time_is_rejected(): void
    {
        $offering = $this->offering();

        Livewire::actingAs(User::factory()->role(Role::Editor)->create())
            ->test(DishOfferingManager::class)
            ->call('create')
            ->set('form.dish_id', $offering->dish_id)
            ->set('form.serve_date', $offering->serve_date->toDateString())
            ->set('form.slot_start', '12:00')
            ->set('form.price', '10')
            ->set('form.stock', 5)
            ->call('save')
            ->assertHasErrors('form.slot_start');
    }

    public function test_banner_is_created_with_image_and_shown_in_the_home_carousel(): void
    {
        Storage::fake('public');

        Livewire::actingAs(User::factory()->role(Role::Editor)->create())
            ->test(BannerManager::class)
            ->call('create')
            ->set('form.title', 'Bienvenidos a Huaraz')
            ->set('form.subtitle', 'Te esperamos')
            ->set('form.image', UploadedFile::fake()->image('banner.jpg', 1920, 900))
            ->set('form.image_alt', 'Vista de la cordillera')
            ->set('form.cta_label', 'Ver programa')
            ->set('form.cta_url', '/programa')
            ->set('form.is_published', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->get('/')
            ->assertSee('Bienvenidos a Huaraz')
            ->assertSee('aria-roledescription="carrusel"', false)
            ->assertSee('fetchpriority="high"', false)
            ->assertSee('Vista de la cordillera');
    }

    public function test_banner_rejects_small_images_and_unsafe_links(): void
    {
        Storage::fake('public');

        Livewire::actingAs(User::factory()->role(Role::Editor)->create())
            ->test(BannerManager::class)
            ->call('create')
            ->set('form.title', 'Banner')
            ->set('form.image', UploadedFile::fake()->image('small.jpg', 600, 300))
            ->set('form.image_alt', 'x')
            ->set('form.cta_label', 'Clic')
            ->set('form.cta_url', 'javascript:alert(1)')
            ->call('save')
            ->assertHasErrors(['form.image', 'form.cta_url']);

        $this->assertSame(0, Banner::count());
    }

    public function test_demo_content_is_created_with_test_images_and_removed_cleanly(): void
    {
        Storage::fake('public');
        $this->seed(ConventionSeeder::class);

        $this->artisan('app:demo-content')->assertSuccessful();

        $this->assertSame(3, Banner::where('title', 'like', '[Ejemplo]%')->count());
        $this->assertSame(3, Dish::where('name', 'like', '[Ejemplo]%')->count());
        $this->assertSame(24, DishOffering::count()); // 3 platos × 4 días × almuerzo y cena
        $this->assertCount(6, Storage::disk('public')->allFiles());
        $this->assertFalse(EventSetting::current()->reservations_open); // no abre reservas por su cuenta

        $this->artisan('app:demo-content')->assertFailed(); // no duplica

        $this->artisan('app:demo-content', ['--remove' => true])->assertSuccessful();
        $this->assertSame(0, Banner::count());
        $this->assertSame(0, DishOffering::count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_home_shows_default_hero_without_banners_and_featured_dishes_only_when_open(): void
    {
        $this->offering([], ['name' => 'Pachamanca de prueba']);

        $this->get('/')->assertOk()->assertSee('Ver programa')->assertDontSee('Pachamanca de prueba');

        $this->openReservations();

        $this->get('/')->assertSee('Reserva tu comida')->assertSee('Pachamanca de prueba');
    }
}
