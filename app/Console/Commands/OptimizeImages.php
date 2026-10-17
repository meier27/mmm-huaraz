<?php

namespace App\Console\Commands;

use App\Models\Banner;
use App\Models\ChurchEvent;
use App\Models\Dish;
use App\Models\DonationMethod;
use App\Models\GalleryPhoto;
use App\Models\Leader;
use App\Models\Teaching;
use App\Models\TouristPlace;
use Illuminate\Console\Command;

/**
 * Optimiza las imágenes subidas antes de que existiera la optimización
 * automática. Solo reemplaza una imagen si la nueva versión pesa menos.
 */
class OptimizeImages extends Command
{
    protected $signature = 'app:optimize-images';

    protected $description = 'Reduce el peso de las imágenes ya subidas (WebP de alta calidad, tamaño máximo por sección)';

    public function handle(): int
    {
        $models = [Banner::class, GalleryPhoto::class, Teaching::class, ChurchEvent::class, Leader::class, Dish::class, TouristPlace::class, DonationMethod::class];
        $optimized = 0;
        $saved = 0;

        foreach ($models as $model) {
            foreach ($model::query()->lazyById() as $record) {
                $bytes = $record->optimizeStoredImage();

                if ($bytes !== null) {
                    $optimized++;
                    $saved += $bytes;
                }
            }
        }

        $this->info(sprintf('Imágenes optimizadas: %d. Espacio ahorrado: %.1f MB.', $optimized, $saved / 1048576));

        return self::SUCCESS;
    }
}
