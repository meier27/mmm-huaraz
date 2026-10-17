<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasPublicImage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Lema anual del movimiento (p. ej. 2026 «Familia»).
 */
class Motto extends Model
{
    use Auditable, HasPublicImage;

    /** Laravel pluralizaría «mottoes». */
    protected $table = 'mottos';

    protected string $imageColumn = 'image_path';

    /** @var array{int, int} Tamaño máximo al optimizar la imagen subida. */
    protected array $imageBounds = [1400, 1400];

    protected $fillable = ['year', 'title', 'description', 'verse_text', 'verse_reference', 'image_alt', 'is_published'];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'is_published' => 'boolean',
        ];
    }

    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true);
    }

    /** El lema vigente: el publicado del año más reciente. */
    public static function current(): ?self
    {
        return static::query()->published()->orderByDesc('year')->first();
    }
}
