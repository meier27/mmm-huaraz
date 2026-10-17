<?php

namespace App\Livewire\Forms;

use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class GalleryPhotoForm extends ContentForm
{
    protected array $except = ['image'];

    public string $image_alt = '';

    public bool $is_published = true;

    public int|string $sort_order = 0;

    /** @var TemporaryUploadedFile|null */
    public $image = null;

    protected function rules(): array
    {
        return [
            'image_alt' => ['required', 'string', 'max:255'],
            'is_published' => ['boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:65535'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096', 'dimensions:min_width=600,max_width=4000,max_height=4000'],
        ];
    }

    protected function messages(): array
    {
        return [
            'image.dimensions' => 'La foto debe tener al menos 600 px de ancho (recomendado 1200 px).',
        ];
    }
}
