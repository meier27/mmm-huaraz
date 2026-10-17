<?php

namespace App\Models;

use App\Enums\TeachingCategory;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasPublicImage;
use App\Support\Markdown;
use App\Support\YouTube;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class Teaching extends Model
{
    use Auditable, HasPublicImage;

    protected string $imageColumn = 'image_path';

    /** @var array{int, int} Tamaño máximo al optimizar la imagen subida. */
    protected array $imageBounds = [1600, 1200];

    protected $fillable = [
        'title', 'slug', 'category', 'excerpt', 'body', 'video_id', 'image_alt', 'author_name', 'published_on', 'is_published',
    ];

    protected function casts(): array
    {
        return [
            'category' => TeachingCategory::class,
            'published_on' => 'date:Y-m-d',
            'is_published' => 'boolean',
        ];
    }

    /** Publicadas y cuya fecha ya llegó, de la más reciente a la más antigua. */
    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true)->whereDate('published_on', '<=', today());
    }

    public function scopeLatestFirst(Builder $query): void
    {
        $query->orderByDesc('published_on')->orderByDesc('id');
    }

    public static function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'ensenanza';
        $slug = $base;

        for ($i = 2; static::query()->where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists(); $i++) {
            $slug = "$base-$i";
        }

        return $slug;
    }

    public function renderedBody(): HtmlString
    {
        return Markdown::render($this->body);
    }

    public function summary(): string
    {
        return $this->excerpt ?: Markdown::plain($this->body);
    }

    public function videoEmbedUrl(): ?string
    {
        return $this->video_id ? YouTube::embedUrl($this->video_id) : null;
    }

    public function videoWatchUrl(): ?string
    {
        return $this->video_id ? YouTube::watchUrl($this->video_id) : null;
    }
}
