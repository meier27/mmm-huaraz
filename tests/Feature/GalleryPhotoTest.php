<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Livewire\Admin\GalleryPhotoManager;
use App\Models\GalleryPhoto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class GalleryPhotoTest extends TestCase
{
    use RefreshDatabase;

    public function test_editor_uploads_a_photo_and_it_appears_in_the_home_collage(): void
    {
        Storage::fake('public');

        Livewire::actingAs(User::factory()->role(Role::Editor)->create())
            ->test(GalleryPhotoManager::class)
            ->call('create')
            ->set('form.image', UploadedFile::fake()->image('familia.jpg', 1200, 1200))
            ->set('form.image_alt', 'Familias en el culto dominical')
            ->call('save')
            ->assertHasNoErrors();

        $photo = GalleryPhoto::sole();
        Storage::disk('public')->assertExists($photo->image_path);

        $this->get('/')->assertSee('Familias en el culto dominical')->assertSee($photo->imageUrl());
    }

    public function test_replacing_a_photo_deletes_the_previous_file(): void
    {
        Storage::fake('public');
        $photo = GalleryPhoto::create(['image_alt' => 'Jóvenes', 'is_published' => true]);
        $photo->replaceImage(UploadedFile::fake()->image('a.jpg', 800, 800));
        $old = $photo->image_path;

        Livewire::actingAs(User::factory()->role(Role::Editor)->create())
            ->test(GalleryPhotoManager::class)
            ->call('edit', $photo->id)
            ->set('form.image', UploadedFile::fake()->image('b.jpg', 800, 800))
            ->call('save')
            ->assertHasNoErrors();

        Storage::disk('public')->assertMissing($old);
        Storage::disk('public')->assertExists($photo->fresh()->image_path);
    }

    public function test_a_new_photo_requires_an_image_of_valid_size(): void
    {
        Storage::fake('public');
        $component = Livewire::actingAs(User::factory()->role(Role::Editor)->create())
            ->test(GalleryPhotoManager::class)
            ->call('create')
            ->set('form.image_alt', 'Sin foto')
            ->call('save')
            ->assertHasErrors('form.image');

        $component->set('form.image', UploadedFile::fake()->image('chica.jpg', 300, 300))
            ->call('save')
            ->assertHasErrors('form.image');

        $this->assertSame(0, GalleryPhoto::count());
    }

    public function test_only_published_photos_up_to_four_are_shown_in_order(): void
    {
        Storage::fake('public');

        foreach (range(1, 6) as $i) {
            $photo = GalleryPhoto::create(['image_alt' => "Foto número {$i}", 'is_published' => $i !== 2, 'sort_order' => $i]);
            $photo->replaceImage(UploadedFile::fake()->image("{$i}.jpg", 800, 800));
        }

        $this->get('/')
            ->assertSeeInOrder(['Foto número 1', 'Foto número 3', 'Foto número 4', 'Foto número 5'])
            ->assertDontSee('Foto número 2')
            ->assertDontSee('Foto número 6');
    }

    public function test_cashier_cannot_manage_photos(): void
    {
        $this->actingAs(User::factory()->role(Role::Cashier)->create())
            ->get(route('admin.gallery'))
            ->assertForbidden();
    }
}
