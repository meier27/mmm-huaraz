<?php

namespace App\Livewire\Forms;

use App\Enums\TeachingCategory;
use App\Models\Teaching;
use App\Support\YouTube;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class TeachingForm extends ContentForm
{
    protected array $except = ['image', 'video_url'];

    public string $title = '';

    public string $category = '';

    public ?string $excerpt = '';

    public string $body = '';

    public ?string $author_name = '';

    public string $published_on = '';

    public ?string $image_alt = '';

    public bool $is_published = false;

    /** Enlace de YouTube; se guarda solo el identificador del vídeo. */
    public ?string $video_url = '';

    /** @var TemporaryUploadedFile|null */
    public $image = null;

    /** @param Teaching $record */
    public function fillFrom(Model $record): void
    {
        parent::fillFrom($record);
        $this->video_url = $record->videoWatchUrl() ?? '';
    }

    public function videoId(): ?string
    {
        return YouTube::videoId($this->video_url);
    }

    protected function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'category' => ['required', Rule::enum(TeachingCategory::class)],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'body' => ['required', 'string', 'max:50000'],
            'author_name' => ['nullable', 'string', 'max:150'],
            'published_on' => ['required', 'date_format:Y-m-d'],
            'image_alt' => ['nullable', 'required_with:image', 'string', 'max:255'],
            'is_published' => ['boolean'],
            'video_url' => ['nullable', 'string', 'max:500', function (string $attribute, mixed $value, Closure $fail) {
                if (filled($value) && YouTube::videoId($value) === null) {
                    $fail('Pega el enlace de un vídeo de YouTube (p. ej. https://www.youtube.com/watch?v=…).');
                }
            }],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=4000,max_height=4000'],
        ];
    }
}
