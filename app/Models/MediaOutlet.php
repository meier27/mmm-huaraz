<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasPublicImage;
use App\Models\Concerns\Publishable;
use Illuminate\Database\Eloquent\Model;

/**
 * Medio del movimiento (televisión, radio, revista) con su logotipo.
 */
class MediaOutlet extends Model
{
    use Auditable, HasPublicImage, Publishable;

    protected string $imageColumn = 'image_path';

    /** @var array{int, int} Tamaño máximo al optimizar la imagen subida. */
    protected array $imageBounds = [1200, 1200];

    protected $fillable = ['name', 'url', 'image_alt', 'background_color', 'is_published', 'sort_order'];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function isExternal(): bool
    {
        return str_starts_with($this->url, 'https://');
    }

    /** Ruta interna («/bethel-tv») con la base del sitio, o la URL externa tal cual. */
    public function href(): string
    {
        return $this->isExternal() ? $this->url : url($this->url);
    }
}
