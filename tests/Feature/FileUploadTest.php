<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Livewire\Admin\GalleryPhotoManager;
use App\Livewire\Uploads\ShortNameFileUploadController;
use App\Models\GalleryPhoto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;
use Livewire\Features\SupportFileUploads\FileUploadController;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Livewire;
use Tests\TestCase;

class FileUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_livewire_upload_route_uses_the_short_name_controller(): void
    {
        $this->assertInstanceOf(ShortNameFileUploadController::class, app(FileUploadController::class));
    }

    public function test_long_original_names_are_shortened_so_the_temporary_file_can_be_stored(): void
    {
        $disk = FileUploadConfiguration::disk();
        Storage::fake($disk);
        $longName = 'WhatsApp Image 2026-10-16 at 15.58.12 '.str_repeat('foto de la familia en la convención ', 6).'(1).jpeg';

        $upload = UploadedFile::fake()->image($longName, 1200, 900);
        [$signedPath] = app(FileUploadController::class)->validateAndStore([$upload], $disk);

        $path = TemporaryUploadedFile::extractPathFromSignedPath($signedPath);
        $this->assertNotEmpty($path);
        $this->assertLessThan(150, strlen($path));
        Storage::disk($disk)->assertExists(FileUploadConfiguration::path($path, false));

        $file = TemporaryUploadedFile::createFromLivewire($path);
        $this->assertSame('jpeg', $file->getClientOriginalExtension());
        $this->assertStringStartsWith('whatsapp-image-2026-10-16', $file->getClientOriginalName());
    }

    public function test_a_lost_temporary_upload_shows_a_message_instead_of_an_error(): void
    {
        Storage::fake('public');
        Storage::fake(FileUploadConfiguration::disk());

        $component = Livewire::actingAs(User::factory()->role(Role::Editor)->create())
            ->test(GalleryPhotoManager::class)
            ->call('create')
            ->set('form.image_alt', 'Familias');

        // Lo que deja Livewire cuando no pudo guardar el temporal: una ruta vacía.
        $manager = $component->instance();
        $manager->form->image = TemporaryUploadedFile::createFromLivewire('');

        try {
            $manager->save();
            $this->fail('Debía rechazar la subida perdida.');
        } catch (ValidationException $e) {
            $this->assertSame(['form.image' => ['No se pudo recibir el archivo. Vuelve a seleccionarlo.']], $e->errors());
        }

        $this->assertNull($manager->form->image);
        $this->assertSame(0, GalleryPhoto::count());
    }
}
