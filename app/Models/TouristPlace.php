<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToEvent;
use App\Models\Concerns\HasPublicImage;
use App\Models\Concerns\Publishable;
use Illuminate\Database\Eloquent\Model;

class TouristPlace extends Model
{
    use Auditable, BelongsToEvent, HasPublicImage, Publishable;

    protected string $imageColumn = 'image_path';

    /** @var array{int, int} Tamaño máximo al optimizar la imagen subida. */
    protected array $imageBounds = [1600, 1200];

    protected $fillable = [
        'name', 'description', 'distance_note', 'image_alt', 'maps_url', 'is_published', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
