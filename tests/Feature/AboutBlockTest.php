<?php

namespace Tests\Feature;

use App\Enums\AboutBlockType;
use App\Enums\Role;
use App\Livewire\Admin\AboutBlockManager;
use App\Models\AboutBlock;
use App\Models\User;
use Database\Seeders\AboutBlockSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class AboutBlockTest extends TestCase
{
    use RefreshDatabase;

    public function test_editor_creates_a_block_with_photo_and_it_appears_in_about(): void
    {
        Storage::fake('public');

        Livewire::actingAs(User::factory()->role(Role::Editor)->create())
            ->test(AboutBlockManager::class)
            ->call('create')
            ->set('form.type', AboutBlockType::Highlight->value)
            ->set('form.body', "Somos una iglesia **misionera**.\n\nSegundo párrafo.")
            ->set('form.image', UploadedFile::fake()->image('familia.jpg', 1200, 900))
            ->set('form.image_alt', 'Familia de la iglesia')
            ->call('save')
            ->assertHasNoErrors();

        $block = AboutBlock::sole();
        $this->assertStringEndsWith('.webp', $block->image_path);

        $this->get('/nosotros')
            ->assertOk()
            ->assertSee('<strong>misionera</strong>', false)
            ->assertSee('Segundo párrafo.')
            ->assertSee('alt="Familia de la iglesia"', false)
            ->assertSee($block->imageUrl(), false);
    }

    public function test_a_quote_needs_an_author_and_a_photo_needs_a_description(): void
    {
        Storage::fake('public');

        Livewire::actingAs(User::factory()->role(Role::Editor)->create())
            ->test(AboutBlockManager::class)
            ->call('create')
            ->set('form.type', AboutBlockType::Quote->value)
            ->set('form.body', 'Una cita')
            ->set('form.image', UploadedFile::fake()->image('autor.jpg', 800, 800))
            ->call('save')
            ->assertHasErrors(['form.author_name', 'form.image_alt']);

        $this->assertSame(0, AboutBlock::count());
    }

    public function test_only_published_blocks_are_shown_in_order_and_html_is_not_rendered(): void
    {
        AboutBlock::create(['type' => AboutBlockType::Text, 'body' => 'Segundo bloque', 'is_published' => true, 'sort_order' => 2]);
        AboutBlock::create(['type' => AboutBlockType::Quote, 'body' => 'Primera cita <script>alert(1)</script>', 'author_name' => 'Autor de prueba', 'author_role' => 'Fundador', 'is_published' => true, 'sort_order' => 1]);
        AboutBlock::create(['type' => AboutBlockType::Text, 'body' => 'Bloque oculto', 'is_published' => false, 'sort_order' => 3]);

        $this->get('/nosotros')
            ->assertOk()
            ->assertSeeInOrder(['Primera cita', 'Autor de prueba', 'Fundador', 'Segundo bloque'])
            ->assertDontSee('Bloque oculto')
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_seeder_loads_the_official_texts_only_once(): void
    {
        $this->seed(AboutBlockSeeder::class);
        $this->seed(AboutBlockSeeder::class);

        $this->assertSame(3, AboutBlock::count());
        $this->get('/nosotros')
            ->assertSee('Somos una organización que nació en el corazón de Dios')
            ->assertSee('Rev. Luis M. Ortiz')
            ->assertSee('<strong>el Espíritu Santo</strong>', false);
    }

    public function test_cashier_cannot_manage_about_blocks(): void
    {
        $this->actingAs(User::factory()->role(Role::Cashier)->create())
            ->get(route('admin.about'))
            ->assertForbidden();
    }
}
