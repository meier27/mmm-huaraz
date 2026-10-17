<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Livewire\Admin\MediaOutletManager;
use App\Models\MediaOutlet;
use App\Models\User;
use Database\Seeders\MediaOutletSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class MediaOutletTest extends TestCase
{
    use RefreshDatabase;

    public function test_editor_creates_a_medium_and_the_home_shows_its_logo(): void
    {
        Storage::fake('public');

        Livewire::actingAs(User::factory()->role(Role::Editor)->create())
            ->test(MediaOutletManager::class)
            ->call('create')
            ->set('form.name', 'Bethel Radio')
            ->set('form.url', 'https://bethelradio.fm/')
            ->set('form.background_color', '#01AE5E')
            ->set('form.image', UploadedFile::fake()->image('radio.jpg', 250, 250))
            ->set('form.image_alt', 'Logotipo de Bethel Radio')
            ->call('save')
            ->assertHasNoErrors();

        $this->get('/')
            ->assertOk()
            ->assertSee('Bethel Radio')
            ->assertSee('href="https://bethelradio.fm/"', false)
            ->assertSee('alt="Logotipo de Bethel Radio"', false)
            ->assertSee('background-color: #01AE5E', false);
    }

    public function test_internal_links_use_the_site_base_and_open_in_the_same_tab(): void
    {
        MediaOutlet::create(['name' => 'Bethel Televisión', 'url' => '/bethel-tv', 'is_published' => true]);
        MediaOutlet::create(['name' => 'Medio oculto', 'url' => '/x', 'is_published' => false]);

        $this->get('/')
            ->assertSee('href="'.url('/bethel-tv').'"', false)
            ->assertDontSee('Medio oculto');
    }

    public function test_unsafe_links_and_colors_are_rejected(): void
    {
        Livewire::actingAs(User::factory()->role(Role::Editor)->create())
            ->test(MediaOutletManager::class)
            ->call('create')
            ->set('form.name', 'Malicioso')
            ->set('form.url', 'javascript:alert(1)')
            ->set('form.background_color', 'red;background:url(x)')
            ->call('save')
            ->assertHasErrors(['form.url', 'form.background_color']);

        $this->assertSame(0, MediaOutlet::count());
    }

    public function test_seeder_creates_the_three_official_media_once(): void
    {
        $this->seed(MediaOutletSeeder::class);
        $this->seed(MediaOutletSeeder::class);

        $this->assertSame(['Bethel Televisión', 'Bethel Radio', 'Impacto Evangelístico'], MediaOutlet::query()->ordered()->pluck('name')->all());
    }

    public function test_cashier_cannot_manage_media(): void
    {
        $this->actingAs(User::factory()->role(Role::Cashier)->create())
            ->get(route('admin.media'))
            ->assertForbidden();
    }
}
