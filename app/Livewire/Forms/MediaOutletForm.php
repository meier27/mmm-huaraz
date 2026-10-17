<?php

namespace App\Livewire\Forms;

use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class MediaOutletForm extends ContentForm
{
    protected array $except = ['image'];

    public string $name = '';

    public string $url = '';

    public ?string $image_alt = '';

    public ?string $background_color = '';

    public bool $is_published = true;

    public int|string $sort_order = 0;

    /** @var TemporaryUploadedFile|null */
    public $image = null;

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            // Ruta interna ("/bethel-tv") o URL https externa.
            'url' => ['required', 'string', 'max:500', 'regex:#^(/[A-Za-z0-9/_\-]*|https://\S+)$#'],
            'image_alt' => ['nullable', 'required_with:image', 'string', 'max:255'],
            'background_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'is_published' => ['boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:65535'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096', 'dimensions:min_width=200,max_width=4000,max_height=4000'],
        ];
    }

    protected function messages(): array
    {
        return [
            'url.regex' => 'Usa una ruta interna (p. ej. /bethel-tv) o una dirección que empiece por https://.',
            'background_color.regex' => 'Usa un color en formato #RRGGBB, p. ej. #7F31D2.',
        ];
    }
}
