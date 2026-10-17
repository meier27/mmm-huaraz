<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Livewire\Admin\MottoManager;
use App\Models\Motto;
use App\Models\User;
use Database\Seeders\MottoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class MottoTest extends TestCase
{
    use RefreshDatabase;

    public function test_editor_creates_a_motto_and_the_home_shows_it(): void
    {
        Storage::fake('public');

        Livewire::actingAs(User::factory()->role(Role::Editor)->create())
            ->test(MottoManager::class)
            ->call('create')
            ->assertSet('form.year', (int) now()->year)
            ->set('form.year', 2026)
            ->set('form.title', 'Familia')
            ->set('form.description', 'La familia es la base.')
            ->set('form.verse_text', 'Yo y mi casa serviremos a Jehová')
            ->set('form.verse_reference', 'Josué 24:15')
            ->set('form.image', UploadedFile::fake()->image('lema.jpg', 990, 1080))
            ->set('form.image_alt', 'Lema Familia')
            ->call('save')
            ->assertHasNoErrors();

        $this->get('/')
            ->assertOk()
            ->assertSee('id="lema"', false)
            ->assertSee('Familia')
            ->assertSee('La familia es la base.')
            ->assertSee('«Yo y mi casa serviremos a Jehová»', false)
            ->assertSee('Josué 24:15')
            ->assertSee('alt="Lema Familia"', false);
    }

    public function test_home_shows_the_latest_published_motto_only(): void
    {
        Motto::create(['year' => 2025, 'title' => 'Generaciones', 'is_published' => true]);
        Motto::create(['year' => 2026, 'title' => 'Familia', 'is_published' => true]);
        Motto::create(['year' => 2027, 'title' => 'Borrador', 'is_published' => false]);

        $this->assertSame('Familia', Motto::current()->title);
        $this->get('/')->assertSee('Familia')->assertDontSee('Generaciones')->assertDontSee('Borrador');
    }

    public function test_home_has_no_motto_section_without_published_mottos(): void
    {
        $this->get('/')->assertOk()->assertDontSee('id="lema"', false);
    }

    public function test_year_is_unique_and_verse_needs_its_reference(): void
    {
        Motto::create(['year' => 2026, 'title' => 'Familia', 'is_published' => true]);

        $component = Livewire::actingAs(User::factory()->role(Role::Editor)->create())
            ->test(MottoManager::class)
            ->call('create')
            ->set('form.year', 2026)
            ->set('form.title', 'Otro')
            ->set('form.verse_text', 'Un versículo')
            ->call('save')
            ->assertHasErrors(['form.year', 'form.verse_reference']);

        // Editar el mismo lema sin cambiar el año sí se permite.
        $component->call('edit', Motto::sole()->id)->set('form.title', 'Familia unida')->call('save')->assertHasNoErrors();
        $this->assertSame('Familia unida', Motto::sole()->title);
    }

    public function test_seeder_loads_the_official_2026_motto_once(): void
    {
        $this->seed(MottoSeeder::class);
        $this->seed(MottoSeeder::class);

        $motto = Motto::sole();
        $this->assertSame([2026, 'Familia', 'Josué 24:15'], [$motto->year, $motto->title, $motto->verse_reference]);
    }

    public function test_cashier_cannot_manage_mottos(): void
    {
        $this->actingAs(User::factory()->role(Role::Cashier)->create())
            ->get(route('admin.mottos'))
            ->assertForbidden();
    }
}
