<?php

namespace Tests\Feature;

use App\Enums\DonationMethodType;
use App\Models\Banner;
use App\Models\DonationMethod;
use App\Models\GalleryPhoto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageOptimizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_uploaded_photos_are_resized_and_saved_as_lighter_webp(): void
    {
        Storage::fake('public');
        $original = $this->photo(3600, 1800);

        $banner = Banner::create(['title' => 'Portada', 'is_published' => true]);
        $banner->replaceImage($original);

        $this->assertStringEndsWith('.webp', $banner->image_path);
        $stored = Storage::disk('public')->path($banner->image_path);
        [$width, $height, $type] = getimagesize($stored);

        $this->assertSame(IMAGETYPE_WEBP, $type);
        $this->assertSame([2400, 1200], [$width, $height]); // límite del banner, misma proporción
        $this->assertLessThan($original->getSize(), filesize($stored));
    }

    public function test_small_images_are_not_enlarged(): void
    {
        Storage::fake('public');

        $photo = GalleryPhoto::create(['image_alt' => 'Familia', 'is_published' => true]);
        $photo->replaceImage($this->photo(800, 600));

        $this->assertSame([800, 600], array_slice(getimagesize(Storage::disk('public')->path($photo->image_path)), 0, 2));
    }

    public function test_qr_codes_are_stored_lossless(): void
    {
        Storage::fake('public');

        // Imagen de dos colores con bordes duros: cualquier pérdida cambiaría píxeles.
        $img = imagecreatetruecolor(400, 400);
        imagefill($img, 0, 0, imagecolorallocate($img, 255, 255, 255));
        for ($i = 0; $i < 400; $i += 20) {
            imagefilledrectangle($img, $i, $i, $i + 9, $i + 9, imagecolorallocate($img, 0, 0, 0));
        }
        $file = UploadedFile::fake()->createWithContent('qr.png', $this->encode($img, 'png'));

        $method = DonationMethod::create(['label' => 'Yape', 'type' => DonationMethodType::Yape, 'is_published' => false]);
        $method->replaceImage($file);

        $stored = imagecreatefromwebp(Storage::disk('public')->path($method->qr_image_path));
        $this->assertSame(0x000000, imagecolorat($stored, 5, 5) & 0xFFFFFF);
        $this->assertSame(0xFFFFFF, imagecolorat($stored, 15, 5) & 0xFFFFFF);
    }

    public function test_command_optimizes_existing_heavy_images_and_removes_the_old_file(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('banners/antigua.jpg', $this->photo(3600, 1800)->getContent());
        $banner = Banner::create(['title' => 'Antigua', 'is_published' => true]);
        $banner->forceFill(['image_path' => 'banners/antigua.jpg'])->save();

        $this->artisan('app:optimize-images')->assertSuccessful();

        $banner->refresh();
        $this->assertStringEndsWith('.webp', $banner->image_path);
        Storage::disk('public')->assertMissing('banners/antigua.jpg');
        Storage::disk('public')->assertExists($banner->image_path);
    }

    /** Foto con detalle (ruido y degradado), como una real: comprime como un JPEG de cámara. */
    private function photo(int $width, int $height): UploadedFile
    {
        $img = imagecreatetruecolor($width, $height);
        for ($y = 0; $y < $height; $y += 12) {
            for ($x = 0; $x < $width; $x += 12) {
                $c = imagecolorallocate($img, ($x * 255 / $width + random_int(0, 40)) % 256, ($y * 255 / $height + random_int(0, 40)) % 256, random_int(60, 120));
                imagefilledrectangle($img, $x, $y, $x + 11, $y + 11, $c);
            }
        }

        return UploadedFile::fake()->createWithContent('foto.jpg', $this->encode($img, 'jpeg'));
    }

    private function encode(\GdImage $img, string $format): string
    {
        ob_start();
        $format === 'png' ? imagepng($img) : imagejpeg($img, null, 95);

        return (string) ob_get_clean();
    }
}
