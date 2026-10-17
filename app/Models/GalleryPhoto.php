<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasPublicImage;
use App\Models\Concerns\Publishable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Foto del collage «Nuestra familia» de la portada.
 */
class GalleryPhoto extends Model
{
    use Auditable, HasPublicImage, Publishable;

    /** Huecos del collage de la portada (la primera foto es la grande). */
    public const HOME_SLOTS = 4;

    protected string $imageColumn = 'image_path';

    /** @var array{int, int} Tamaño máximo al optimizar la imagen subida. */
    protected array $imageBounds = [1400, 1400];

    protected $fillable = ['image_alt', 'is_published', 'sort_order'];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** Fotos que aparecen en la portada, en orden. */
    public function scopeForHome(Builder $query): void
    {
        $query->published()->whereNotNull('image_path')->ordered()->limit(self::HOME_SLOTS);
    }
}
