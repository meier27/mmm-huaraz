<?php

namespace App\Models;

use App\Enums\AboutBlockType;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasPublicImage;
use App\Models\Concerns\Publishable;
use Illuminate\Database\Eloquent\Model;

/**
 * Bloque de la página «Nosotros» (foto + texto, o cita con autor).
 */
class AboutBlock extends Model
{
    use Auditable, HasPublicImage, Publishable;

    protected string $imageColumn = 'image_path';

    /** @var array{int, int} Tamaño máximo al optimizar la imagen subida. */
    protected array $imageBounds = [1400, 1400];

    protected $fillable = ['type', 'body', 'author_name', 'author_role', 'image_alt', 'photo_frame', 'is_published', 'sort_order'];

    protected function casts(): array
    {
        return [
            'type' => AboutBlockType::class,
            'photo_frame' => 'boolean',
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
