<?php

namespace Tests\Feature;

use App\Enums\DonationMethodType;
use App\Enums\Role;
use App\Enums\VenueKind;
use App\Livewire\Admin\ChurchEventManager;
use App\Livewire\Admin\EventEditor;
use App\Livewire\Admin\ProgramDayManager;
use App\Livewire\Admin\ProgramItemManager;
use App\Livewire\Admin\VenueManager;
use App\Livewire\DonationReportForm;
use App\Models\ChurchEvent;
use App\Models\DonationMethod;
use App\Models\ProgramDay;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class EventModulesTest extends TestCase
{
    use RefreshDatabase;

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

        foreach (['program', 'venue', 'donations'] as $page) {
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
            ->assertDontSee(route('events.venue', $event), false);

        $this->get(route('events.program', $event))->assertOk();
        $this->get(route('events.venue', $event))->assertNotFound();
    }

    public function test_retired_sections_redirect_to_the_event_page(): void
    {
        $event = $this->event('Convención', ['has_program' => true]);

        foreach (['reservas', 'hospedaje', 'turismo'] as $retired) {
            $this->get("/eventos/{$event->slug}/{$retired}")->assertRedirect(route('events.show', $event))->assertStatus(301);
        }

        $this->get('/eventos/borrador/reservas')->assertNotFound();
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
    // Panel: cada gestor trabaja dentro de su evento
    // ------------------------------------------------------------------

    public function test_module_screens_require_the_module(): void
    {
        $event = $this->event('Vigilia');

        $this->actingAs($this->editor())->get(route('admin.events.edit', $event))->assertOk();
        $this->actingAs($this->editor())->get(route('admin.events.program', $event))->assertNotFound();
        $this->actingAs($this->editor())->get(route('admin.events.venues', $event))->assertNotFound();
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

    public function test_program_form_rejects_days_of_another_event(): void
    {
        $a = $this->event('Evento A', ['has_program' => true]);
        $b = $this->event('Evento B', ['has_program' => true]);
        $dayOfB = ProgramDay::query()->forceCreate(['church_event_id' => $b->id, 'date' => today()->addDay()->toDateString()]);

        Livewire::actingAs($this->editor())->test(ProgramItemManager::class, ['event' => $a])
            ->call('create')->set('form.program_day_id', $dayOfB->id)->set('form.starts_at', '19:00')->set('form.title', 'Culto')
            ->call('save')->assertHasErrors('form.program_day_id');
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
            ->set('form.has_venues', true)
            ->set('form.has_donations', true)
            ->set('form.is_featured', true)
            ->call('save')
            ->assertHasNoErrors();

        $event->refresh();
        $this->assertTrue($event->has_venues && $event->has_donations);
        $this->assertTrue($event->is_featured);
        $this->assertFalse($old->fresh()->is_featured);
        $this->assertSame($event->id, ChurchEvent::featured()->id);
    }

    public function test_event_with_content_cannot_be_deleted(): void
    {
        $event = $this->event('Con programa', ['has_program' => true]);
        ProgramDay::query()->forceCreate(['church_event_id' => $event->id, 'date' => today()->addDay()->toDateString()]);
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
