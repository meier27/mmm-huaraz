<?php

namespace App\Livewire\Forms;

use App\Enums\AboutBlockType;
use Illuminate\Validation\Rule;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class AboutBlockForm extends ContentForm
{
    protected array $except = ['image'];

    public string $type = 'text';

    public string $body = '';

    public ?string $author_name = '';

    public ?string $author_role = '';

    public ?string $image_alt = '';

    public bool $photo_frame = true;

    public bool $is_published = true;

    public int|string $sort_order = 0;

    /** @var TemporaryUploadedFile|null */
    public $image = null;

    protected function rules(): array
    {
        $quote = AboutBlockType::Quote->value;

        return [
            'type' => ['required', Rule::enum(AboutBlockType::class)],
            'body' => ['required', 'string', 'max:5000'],
            'author_name' => ['nullable', "required_if:type,{$quote}", 'string', 'max:150'],
            'author_role' => ['nullable', 'string', 'max:150'],
            'image_alt' => ['nullable', 'required_with:image', 'string', 'max:255'],
            'photo_frame' => ['boolean'],
            'is_published' => ['boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:65535'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096', 'dimensions:min_width=400,max_width=4000,max_height=4000'],
        ];
    }

    protected function messages(): array
    {
        return [
            'author_name.required_if' => 'Indica el autor de la cita.',
            'image.dimensions' => 'La foto debe tener al menos 400 px de ancho.',
        ];
    }
}
