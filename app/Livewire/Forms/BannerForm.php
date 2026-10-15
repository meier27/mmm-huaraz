<?php

namespace App\Livewire\Forms;

use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class BannerForm extends ContentForm
{
    protected array $except = ['image'];

    public string $title = '';

    public ?string $subtitle = '';

    public ?string $image_alt = '';

    public ?string $cta_label = '';

    public ?string $cta_url = '';

    public bool $is_published = false;

    public int|string $sort_order = 0;

    /** @var TemporaryUploadedFile|null */
    public $image = null;

    protected function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'image_alt' => ['nullable', 'required_with:image', 'string', 'max:255'],
            'cta_label' => ['nullable', 'required_with:cta_url', 'string', 'max:50'],
            // Ruta interna ("/programa") o URL https externa.
            'cta_url' => ['nullable', 'required_with:cta_label', 'string', 'max:500', 'regex:#^(/[A-Za-z0-9/_\-]*|https://\S+)$#'],
            'is_published' => ['boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:65535'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096', 'dimensions:min_width=1200,max_width=4000,max_height=4000'],
        ];
    }

    protected function messages(): array
    {
        return [
            'cta_url.regex' => 'Usa una ruta interna (p. ej. /programa) o una dirección que empiece por https://.',
            'image.dimensions' => 'La imagen debe tener al menos 1200 px de ancho (recomendado 1920 × 900).',
        ];
    }
}
