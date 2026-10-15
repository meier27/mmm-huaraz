<?php

namespace App\Console\Commands;

use App\Models\Banner;
use App\Models\Dish;
use App\Models\DishOffering;
use App\Models\EventSetting;
use Carbon\CarbonPeriod;
use Illuminate\Console\Command;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Contenido de EJEMPLO para revisar el diseño mientras llega el material
 * oficial. Todo queda marcado con el prefijo "[Ejemplo]" y se elimina con
 * --remove. No es información oficial.
 */
class DemoContent extends Command
{
    private const PREFIX = '[Ejemplo]';

    protected $signature = 'app:demo-content {--remove : Elimina el contenido de ejemplo}';

    protected $description = 'Crea (o elimina con --remove) banners y platos de ejemplo con imágenes de prueba';

    public function handle(): int
    {
        return $this->option('remove') ? $this->remove() : $this->create();
    }

    private function create(): int
    {
        if (Banner::where('title', 'like', self::PREFIX.'%')->exists() || Dish::where('name', 'like', self::PREFIX.'%')->exists()) {
            $this->warn('Ya existe contenido de ejemplo. Usa --remove para eliminarlo antes de volver a crearlo.');

            return self::FAILURE;
        }

        $banners = [
            ['Bienvenidos a la convención', 'Banner de ejemplo: sustitúyelo por una foto autorizada desde el panel.', 'Ver programa', '/programa', [[18, 52, 92], [196, 120, 60]]],
            ['Reserva tu comida', 'Asegura tu plato y recógelo con tu código QR.', 'Reservar', '/reservas', [[113, 50, 24], [244, 127, 18]]],
            ['Descubre Huaraz', 'Lagunas, nevados y cultura andina durante tu visita.', 'Turismo', '/turismo', [[0, 46, 93], [120, 170, 200]]],
        ];

        foreach ($banners as $i => [$title, $subtitle, $cta, $url, $colors]) {
            $banner = Banner::create([
                'title' => self::PREFIX.' '.$title, 'subtitle' => $subtitle,
                'image_alt' => 'Imagen de prueba', 'cta_label' => $cta, 'cta_url' => $url,
                'is_published' => true, 'sort_order' => $i + 1,
            ]);
            $banner->replaceImage($this->image(1920, 900, $colors[0], $colors[1], 'mountains'));
        }

        $dishes = [
            ['Plato de ejemplo 1', '12.00', [150, 70, 25], [235, 170, 90]],
            ['Plato de ejemplo 2', '10.00', [80, 110, 35], [205, 210, 120]],
            ['Plato de ejemplo 3', '8.00', [120, 35, 35], [235, 130, 100]],
        ];

        $settings = EventSetting::current();
        $days = CarbonPeriod::create($settings->starts_on, $settings->ends_on);

        DB::transaction(function () use ($dishes, $days) {
            foreach ($dishes as $i => [$name, $price, $from, $to]) {
                $dish = Dish::create([
                    'name' => self::PREFIX.' '.$name,
                    'description' => 'Descripción de ejemplo. Reemplázala por la del menú oficial.',
                    'image_alt' => 'Imagen de prueba de un plato',
                    'is_published' => true, 'sort_order' => $i + 1,
                ]);
                $dish->replaceImage($this->image(1200, 750, $from, $to, 'plate'));

                foreach ($days as $day) {
                    foreach ([['12:00', '14:00'], ['19:00', '21:00']] as [$start, $end]) {
                        DishOffering::create([
                            'dish_id' => $dish->id, 'serve_date' => Carbon::parse($day)->toDateString(),
                            'slot_start' => $start, 'slot_end' => $end, 'price' => $price, 'stock' => 50, 'is_active' => true,
                        ]);
                    }
                }
            }
        });

        $this->info('Contenido de ejemplo creado: 3 banners publicados y 3 platos con ofertas.');
        $this->line('Las reservas siguen cerradas: ábrelas en Panel › Convención para ver los platos en la web.');

        return self::SUCCESS;
    }

    private function remove(): int
    {
        $removed = 0;

        foreach (Banner::where('title', 'like', self::PREFIX.'%')->get() as $banner) {
            $banner->delete();
            $removed++;
        }

        foreach (Dish::where('name', 'like', self::PREFIX.'%')->with('offerings')->get() as $dish) {
            if ($dish->offerings->contains(fn ($o) => $o->items()->exists())) {
                $this->warn("«{$dish->name}» tiene reservas: se despublica en lugar de eliminarse.");
                $dish->update(['is_published' => false]);

                continue;
            }

            $dish->offerings->each->delete();
            $dish->delete();
            $removed++;
        }

        $this->info("Elementos de ejemplo eliminados: {$removed}.");

        return self::SUCCESS;
    }

    /**
     * Imagen JPG de prueba generada con GD (degradado, motivo y rótulo).
     *
     * @param  array{int, int, int}  $from
     * @param  array{int, int, int}  $to
     */
    private function image(int $width, int $height, array $from, array $to, string $motif): UploadedFile
    {
        $img = imagecreatetruecolor($width, $height);

        for ($y = 0; $y < $height; $y++) {
            $t = $y / $height;
            $color = imagecolorallocate($img, ...array_map(fn ($a, $b) => (int) round($a + ($b - $a) * $t), $from, $to));
            imageline($img, 0, $y, $width, $y, $color);
        }

        $shade = imagecolorallocatealpha($img, 0, 0, 0, 80);
        $light = imagecolorallocatealpha($img, 255, 255, 255, 70);

        if ($motif === 'mountains') {
            imagefilledpolygon($img, [0, $height, (int) ($width * .22), (int) ($height * .45), (int) ($width * .4), (int) ($height * .7), (int) ($width * .62), (int) ($height * .35), (int) ($width * .85), (int) ($height * .65), $width, (int) ($height * .5), $width, $height], $shade);
            imagefilledpolygon($img, [(int) ($width * .62), (int) ($height * .35), (int) ($width * .58), (int) ($height * .42), (int) ($width * .66), (int) ($height * .42)], $light);
        } else {
            imagefilledellipse($img, (int) ($width / 2), (int) ($height * .55), (int) ($width * .55), (int) ($height * .62), $shade);
            imagefilledellipse($img, (int) ($width / 2), (int) ($height * .52), (int) ($width * .46), (int) ($height * .5), $light);
        }

        $this->label($img, 'IMAGEN DE PRUEBA', $width, $height);

        $path = tempnam(sys_get_temp_dir(), 'demo').'.jpg';
        imagejpeg($img, $path, 82);

        return new UploadedFile($path, 'demo.jpg', 'image/jpeg', null, true);
    }

    private function label(\GdImage $img, string $text, int $width, int $height): void
    {
        $white = imagecolorallocatealpha($img, 255, 255, 255, 30);
        $font = collect(['C:\Windows\Fonts\arialbd.ttf', '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf'])->first(fn ($f) => is_file($f));

        // Esquina superior derecha: no compite con el texto ni con los controles del carrusel.
        if ($font && function_exists('imagettftext')) {
            $size = (int) ($width / 60);
            $box = imagettfbbox($size, 0, $font, $text);
            imagettftext($img, $size, 0, $width - ($box[2] - $box[0]) - (int) ($width * .03), (int) ($height * .08) + $size, $white, $font, $text);

            return;
        }

        imagestring($img, 5, $width - strlen($text) * 9 - 20, 20, $text, $white);
    }
}
