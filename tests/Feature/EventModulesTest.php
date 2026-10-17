<?php

namespace Tests\Feature;

use App\Enums\DonationMethodType;
use App\Enums\Role;
use App\Enums\VenueKind;
use App\Livewire\Admin\ChurchEventManager;
use App\Livewire\Admin\DishOfferingManager;
use App\Livewire\Admin\EventEditor;
use App\Livewire\Admin\ProgramDayManager;
use App\Livewire\Admin\ProgramItemManager;
use App\Livewire\Admin\ReservationIndex;
use App\Livewire\Admin\VenueManager;
use App\Livewire\DonationReportForm;
use App\Livewire\ReservationForm;
use App\Models\ChurchEvent;
use App\Models\DonationMethod;
use App\Models\ProgramDay;
use App\Models\User;
use App\Models\Venue;
use App\Services\ReservationService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesOfferings;
use Tests\TestCase;

class EventModulesTest extends TestCase
{
    use CreatesOfferings, RefreshDatabase;

    private function event(string $title, array $attributes = []): ChurchEvent
    {
        return ChurchEvent::create($attributes + [
            'title' => $title,
            'slug' => Str::slug($title),
            'starts_on' => today()->addDay()->toDateString(),
            'ends_on' => today()->addDays(3)->toDateString(),
            'is_published' => true,
        ]);
    }

    private function editor(): User
    {
        return User::factory()->role(Role::Editor)->create();
    }

    // ------------------------------------------------------------------
    // Web pública
    // ------------------------------------------------------------------

    public function test_an_event_can_be_created_without_dates_and_shows_no_date_text(): void
    {
        Livewire::actingAs($this->editor())->test(ChurchEventManager::class)
            ->call('create')->set('form.title', 'Vigilia sin fecha')->set('form.is_published', true)
            ->call('save')
            ->assertHasNoErrors();

        $event = ChurchEvent::where('title', 'Vigilia sin fecha')->sole();
        $this->assertFalse($event->hasDates());
        $this->assertFalse($event->isPast());
        // Sin fecha no se muestra ningún texto de fecha.
        $this->assertSame('', $event->dateLabel());
        $this->assertSame('', $event->countdownLabel());

        // Cuenta como próximo, después de los eventos con fecha.
        $dated = $this->event('Retiro con fecha');
        $this->assertSame([$dated->id, $event->id], ChurchEvent::query()->published()->upcoming()->pluck('id')->intersect([$dated->id, $event->id])->values()->all());

        $this->get(route('events.index'))->assertOk()->assertSee('Vigilia sin fecha')->assertDontSee('Fecha por confirmar');
        $this->get(route('events.show', $event))->assertOk()->assertDontSee('Fecha por confirmar')->assertSee('content="Vigilia sin fecha"', false);
    }

    public function test_program_shows_one_day_per_page_with_day_links(): void
    {
        $event = $this->event('Retiro de tres días', ['has_program' => true]);
        foreach ([1, 2, 3] as $offset) {
            $day = ProgramDay::query()->forceCreate(['church_event_id' => $event->id, 'date' => today()->addDays($offset)->toDateString()]);
            $day->items()->create(['starts_at' => '09:00', 'ends_at' => '10:00', 'title' => "Actividad del día {$offset}", 'is_published' => true]);
        }
        $url = route('events.program', $event);

        // Por defecto, el próximo día; solo sus actividades.
        $this->get($url)->assertOk()
            ->assertSee('Actividad del día 1')->assertDontSee('Actividad del día 2')
            ->assertSee('aria-current="page"', false)
            ->assertSee('Día 1 de 3')
            ->assertSee('?dia='.today()->addDays(2)->toDateString(), false);

        $this->get($url.'?dia='.today()->addDays(3)->toDateString())
            ->assertSee('Actividad del día 3')->assertDontSee('Actividad del día 1')
            ->assertSee('Día anterior')->assertDontSee('Día siguiente');

        // Una fecha que no es del programa vuelve al día por defecto.
        $this->get($url.'?dia=2000-01-01')->assertSee('Actividad del día 1');
    }

    public function test_long_event_lists_are_paginated(): void
    {
        foreach (range(1, 10) as $i) {
            $this->event("Campaña número {$i}", ['starts_on' => today()->addDays($i)->toDateString(), 'ends_on' => null]);
        }

        $this->get(route('events.index'))->assertOk()
            ->assertSee('aria-label="Paginación"', false)
            ->assertSee('proximos=2', false);

        $this->get(route('events.index', ['proximos' => 2]))->assertOk()->assertSee('Página <span class="font-semibold text-primary">2</span>', false);
    }

    public function test_end_date_requires_a_start_date(): void
    {
        Livewire::actingAs($this->editor())->test(ChurchEventManager::class)
            ->call('create')->set('form.title', 'Solo fin')->set('form.ends_on', today()->addWeek()->toDateString())
            ->call('save')
            ->assertHasErrors(['form.ends_on' => 'Indica primero la fecha de inicio.']);
    }

    public function test_a_simple_event_has_no_module_pages(): void
    {
        $event = $this->event('Vigilia');

        $this->get(route('events.show', $event))->assertOk()->assertDontSee('aria-label="Secciones del evento"', false);

        foreach (['program', 'venue', 'reservations', 'donations', 'accommodations', 'tourism'] as $page) {
            $this->get(route("events.$page", $event))->assertNotFound();
        }
    }

    public function test_event_submenu_lists_only_enabled_modules(): void
    {
        $event = $this->event('Campaña', ['has_program' => true, 'has_donations' => true]);

        $this->get(route('events.show', $event))
            ->assertOk()
            ->assertSee('aria-label="Secciones del evento"', false)
            ->assertSee(route('events.program', $event), false)
            ->assertSee(route('events.donations', $event), false)
            ->assertDontSee(route('events.reservations', $event), false)
            ->assertDontSee(route('events.tourism', $event), false);

        $this->get(route('events.program', $event))->assertOk();
        $this->get(route('events.tourism', $event))->assertNotFound();
    }

    public function test_unpublished_events_and_their_modules_are_hidden(): void
    {
        $event = $this->event('Borrador', ['is_published' => false, 'has_program' => true]);

        $this->get(route('events.show', $event))->assertNotFound();
        $this->get(route('events.program', $event))->assertNotFound();
    }

    public function test_module_content_belongs_to_its_event(): void
    {
        $a = $this->event('Retiro A', ['has_venues' => true]);
        $b = $this->event('Retiro B', ['has_venues' => true]);
        Venue::query()->forceCreate(['church_event_id' => $a->id, 'kind' => VenueKind::Main, 'name' => 'Sede del retiro A', 'is_published' => true]);

        $this->get(route('events.venue', $a))->assertSee('Sede del retiro A');
        $this->get(route('events.venue', $b))->assertDontSee('Sede del retiro A');
    }

    // ------------------------------------------------------------------
    // Reservas: una reserva pertenece a un único evento
    // ------------------------------------------------------------------

    public function test_reservation_form_only_offers_dishes_of_its_event(): void
    {
        $a = $this->event('Evento A', ['has_food' => true, 'reservations_open' => true]);
        $b = $this->event('Evento B', ['has_food' => true, 'reservations_open' => true]);
        $this->offering([], ['name' => 'Plato de A'], $a);
        $this->offering([], ['name' => 'Plato de B'], $b);

        $this->get(route('events.reservations', $a))->assertSee('Plato de A')->assertDontSee('Plato de B');
    }

    public function test_a_reservation_cannot_mix_or_smuggle_dishes_from_another_event(): void
    {
        $a = $this->event('Evento A', ['has_food' => true, 'reservations_open' => true]);
        $b = $this->event('Evento B', ['has_food' => true, 'reservations_open' => true]);
        $fromA = $this->offering([], ['name' => 'Plato de A'], $a);
        $fromB = $this->offering([], ['name' => 'Plato de B'], $b);
        $service = app(ReservationService::class);

        try {
            $service->create([$fromA->id => 1, $fromB->id => 1], 'Ana', '900000001', null, (string) Str::uuid());
            $this->fail('Se esperaba ValidationException al mezclar eventos.');
        } catch (ValidationException) {
        }

        // Desde la página del evento A no se puede reservar un plato de B.
        Livewire::test(ReservationForm::class, ['event' => $a])
            ->set("quantities.{$fromB->id}", 1)
            ->set('customer_name', 'Ana Prueba')
            ->set('customer_phone', '900000001')
            ->call('submit')
            ->assertHasErrors('quantities');

        $reservation = $service->create([$fromA->id => 1], 'Ana', '900000001', null, (string) Str::uuid(), $a);
        $this->assertSame($a->id, $reservation->church_event_id);
        $this->assertSame(0, $fromB->fresh()->reserved);
    }

    public function test_reservations_are_closed_per_event(): void
    {
        $open = $this->event('Abierto', ['has_food' => true, 'reservations_open' => true]);
        $closed = $this->event('Cerrado', ['has_food' => true, 'reservations_open' => false]);
        $offering = $this->offering([], [], $closed);

        $this->get(route('events.reservations', $open))->assertDontSee('Las reservas aún no están abiertas');
        $this->get(route('events.reservations', $closed))->assertSee('Las reservas aún no están abiertas');

        Livewire::test(ReservationForm::class, ['event' => $closed])
            ->set("quantities.{$offering->id}", 1)->set('customer_name', 'Ana Prueba')->set('customer_phone', '900000001')
            ->call('submit')
            ->assertHasErrors('quantities');
    }

    public function test_the_event_of_the_reservation_form_cannot_be_changed_from_the_browser(): void
    {
        $a = $this->event('Evento A', ['has_food' => true]);

        $this->expectException(CannotUpdateLockedPropertyException::class);
        Livewire::test(ReservationForm::class, ['event' => $a])->set('event', $this->event('Evento B', ['has_food' => true])->id);
    }

    // ------------------------------------------------------------------
    // Panel: cada gestor trabaja dentro de su evento
    // ------------------------------------------------------------------

    public function test_module_screens_require_the_module(): void
    {
        $event = $this->event('Vigilia');

        $this->actingAs($this->editor())->get(route('admin.events.edit', $event))->assertOk();
        $this->actingAs($this->editor())->get(route('admin.events.program', $event))->assertNotFound();
        $this->actingAs($this->editor())->get(route('admin.events.dishes', $event))->assertNotFound();
    }

    public function test_venue_takes_coordinates_from_a_pasted_google_maps_link_and_stores_a_photo(): void
    {
        Storage::fake('public');
        $event = $this->event('Retiro con sede', ['has_venues' => true]);

        Livewire::actingAs($this->editor())->test(VenueManager::class, ['event' => $event])
            ->call('create')
            ->set('form.kind', VenueKind::Main->value)
            ->set('form.name', 'Estadio')
            ->set('form.address', 'Av. Principal 1, Huaraz')
            ->set('form.maps_url', 'https://www.google.com/maps/place/Estadio/@-9.5100000,-77.5300000,17z/data=!3m1!4b1!4m6!3m5!1s0x0:0x0!8m2!3d-9.5277123!4d-77.5278456')
            ->set('form.image', UploadedFile::fake()->image('estadio.jpg', 1600, 900))
            ->set('form.image_alt', 'Entrada del estadio')
            ->call('save')
            ->assertHasNoErrors();

        $venue = Venue::where('name', 'Estadio')->sole();
        // «!3d…!4d…» es el punto exacto del lugar (más preciso que «@», que es el centro de la vista).
        $this->assertSame(['-9.5277123', '-77.5278456'], [$venue->latitude, $venue->longitude]);
        $this->assertStringEndsWith('.webp', $venue->image_path);
    }

    public function test_managers_create_records_in_their_event_and_cannot_touch_another_event(): void
    {
        $a = $this->event('Retiro A', ['has_venues' => true]);
        $b = $this->event('Retiro B', ['has_venues' => true]);
        $venueOfB = Venue::query()->forceCreate(['church_event_id' => $b->id, 'kind' => VenueKind::Main, 'name' => 'Sede de B']);

        Livewire::actingAs($this->editor())->test(VenueManager::class, ['event' => $a])
            ->call('create')->set('form.name', 'Sede nueva')->set('form.kind', VenueKind::Main->value)->call('save')
            ->assertHasNoErrors()
            ->assertSee('Sede nueva')
            ->assertDontSee('Sede de B');

        $this->assertSame($a->id, Venue::where('name', 'Sede nueva')->value('church_event_id'));

        $this->expectException(ModelNotFoundException::class);
        Livewire::actingAs($this->editor())->test(VenueManager::class, ['event' => $a])->call('delete', $venueOfB->id);
    }

    public function test_program_and_offering_forms_reject_days_and_dishes_of_another_event(): void
    {
        $a = $this->event('Evento A', ['has_program' => true, 'has_food' => true]);
        $b = $this->event('Evento B', ['has_program' => true, 'has_food' => true]);
        $dayOfB = ProgramDay::query()->forceCreate(['church_event_id' => $b->id, 'date' => today()->addDay()->toDateString()]);
        $dishOfB = $this->offering([], [], $b)->dish;

        Livewire::actingAs($this->editor())->test(ProgramItemManager::class, ['event' => $a])
            ->call('create')->set('form.program_day_id', $dayOfB->id)->set('form.starts_at', '19:00')->set('form.title', 'Culto')
            ->call('save')->assertHasErrors('form.program_day_id');

        Livewire::actingAs($this->editor())->test(DishOfferingManager::class, ['event' => $a])
            ->call('create')->set('form.dish_id', $dishOfB->id)->set('form.serve_date', today()->addDay()->toDateString())
            ->set('form.slot_start', '12:00')->set('form.price', '10')->set('form.stock', 10)
            ->call('save')->assertHasErrors('form.dish_id');
    }

    public function test_two_events_can_have_program_on_the_same_day_but_not_twice_in_one_event(): void
    {
        $a = $this->event('Evento A', ['has_program' => true]);
        $b = $this->event('Evento B', ['has_program' => true]);
        $date = today()->addDay()->toDateString();

        foreach ([$a, $b] as $event) {
            Livewire::actingAs($this->editor())->test(ProgramDayManager::class, ['event' => $event])
                ->call('create')->set('form.date', $date)->call('save')->assertHasNoErrors();
        }

        Livewire::actingAs($this->editor())->test(ProgramDayManager::class, ['event' => $a])
            ->call('create')->set('form.date', $date)->call('save')->assertHasErrors(['form.date' => 'unique']);
    }

    public function test_event_editor_enables_modules_and_keeps_a_single_featured_event(): void
    {
        $old = $this->event('Antiguo destacado', ['is_featured' => true]);
        $event = $this->event('Nuevo');

        Livewire::actingAs($this->editor())->test(EventEditor::class, ['event' => $event])
            ->set('form.reservations_open', true)
            ->call('save')
            ->assertHasErrors('form.reservations_open') // sin el módulo de comidas
            ->set('form.has_food', true)
            ->set('form.has_tourism', true)
            ->set('form.is_featured', true)
            ->call('save')
            ->assertHasNoErrors();

        $event->refresh();
        $this->assertTrue($event->has_food && $event->has_tourism && $event->reservations_open);
        $this->assertTrue($event->is_featured);
        $this->assertFalse($old->fresh()->is_featured);
        $this->assertSame($event->id, ChurchEvent::featured()->id);
    }

    public function test_event_with_content_cannot_be_deleted(): void
    {
        $event = $this->event('Con platos', ['has_food' => true]);
        $this->offering([], [], $event);
        $empty = $this->event('Vacío');

        Livewire::actingAs($this->editor())->test(ChurchEventManager::class)
            ->call('delete', $event->id)->assertHasErrors('delete')
            ->call('delete', $empty->id)->assertHasNoErrors();

        $this->assertModelExists($event);
        $this->assertModelMissing($empty);
    }

    public function test_creating_an_event_redirects_to_its_page_with_a_safe_slug(): void
    {
        Livewire::actingAs($this->editor())->test(ChurchEventManager::class)
            ->call('create')->set('form.title', 'Convención')->set('form.starts_on', today()->addMonth()->toDateString())
            ->call('save')
            ->assertRedirect(route('admin.events.edit', ChurchEvent::sole()));

        $this->assertNotSame('convencion', ChurchEvent::sole()->slug);
    }

    public function test_reservations_and_donations_can_be_filtered_by_event(): void
    {
        $a = $this->event('Evento A', ['has_food' => true]);
        $b = $this->event('Evento B', ['has_food' => true]);
        $service = app(ReservationService::class);
        $service->create([$this->offering([], [], $a)->id => 1], 'Cliente de A', '900000001', null, (string) Str::uuid());
        $service->create([$this->offering([], [], $b)->id => 1], 'Cliente de B', '900000002', null, (string) Str::uuid());

        Livewire::actingAs(User::factory()->role(Role::Cashier)->create())->test(ReservationIndex::class)
            ->assertSee('Cliente de A')->assertSee('Cliente de B')
            ->set('event', (string) $a->id)
            ->assertSee('Cliente de A')->assertDontSee('Cliente de B');
    }

    public function test_donations_reported_from_an_event_page_are_linked_to_it(): void
    {
        $event = $this->event('Campaña', ['has_donations' => true, 'donation_reports_enabled' => true]);
        $method = DonationMethod::create(['type' => DonationMethodType::Yape, 'label' => 'Yape', 'is_published' => true]);

        Livewire::test(DonationReportForm::class, ['event' => $event])
            ->set('donation_method_id', (string) $method->id)->set('declared_amount', '20')->set('operation_number', '123')
            ->set('consent', true)->call('submit')->assertHasNoErrors();

        $this->assertSame($event->id, $event->donations()->sole()->church_event_id);
    }
}
