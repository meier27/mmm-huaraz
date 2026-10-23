<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

/**
 * Doctrinario mostrado en el lector propio de /doctrina. El PDF nunca se
 * publica: sus páginas se convierten en imágenes privadas (DoctrineRenderer)
 * que se sirven solo con enlaces firmados (DoctrinePageController).
 */
class DoctrineBook extends Model
{
    use Auditable;

    public const DISK = 'local';

    public const READY = 'ready';

    public const PROCESSING = 'processing';

    public const FAILED = 'failed';

    protected $fillable = ['title', 'author', 'is_published'];

    protected $hidden = ['pdf_path'];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'page_count' => 'integer',
            'page_width' => 'integer',
            'page_height' => 'integer',
            'toc' => 'array',
        ];
    }

    /** El que se muestra en /doctrina: publicado y con sus páginas listas. */
    public static function current(): ?self
    {
        return static::query()->where('is_published', true)->where('status', self::READY)->latest('id')->first();
    }

    public function isReady(): bool
    {
        return $this->status === self::READY;
    }

    public function directory(): string
    {
        return 'doctrine/'.$this->id;
    }

    /**
     * Índice ordenado y válido (páginas dentro del libro).
     *
     * @return list<array{page: int, title: string}>
     */
    public function index(): array
    {
        return collect($this->toc ?? [])
            ->filter(fn ($e) => is_array($e) && filled($e['title'] ?? null) && (int) ($e['page'] ?? 0) >= 1 && (int) $e['page'] <= $this->page_count)
            ->map(fn ($e) => ['page' => (int) $e['page'], 'title' => (string) $e['title']])
            ->sortBy('page')->values()->all();
    }

    public function pagePath(int $page): string
    {
        return $this->directory().'/pages/'.str_pad((string) $page, 3, '0', STR_PAD_LEFT).'.webp';
    }
}
